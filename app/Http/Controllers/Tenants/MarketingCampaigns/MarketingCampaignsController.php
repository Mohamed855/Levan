<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\MarketingCampaigns;

use App\Actions\Tenants\MarketingCampaigns\DeleteMarketingCampaignAction;
use App\Actions\Tenants\MarketingCampaigns\SaveMarketingCampaignAction;
use App\DataTables\Tenants\MarketingCampaigns\MarketingCampaignsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\MarketingCampaigns\SaveMarketingCampaignRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\MarketingCampaigns\MarketingCampaign;
use App\Services\Tenants\Auctions\Reports\AuctionReportLock;
use App\Services\Tenants\Auctions\Reports\AuctionReportManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MarketingCampaignsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:tenant');
        $this->middleware('can:marketing_campaigns.view')->only('index');
        $this->middleware('can:marketing_campaigns.create')->only('store');
        $this->middleware('can:marketing_campaigns.edit')->only(['edit', 'update']);
        $this->middleware('can:marketing_campaigns.delete')->only(['destroy', 'massDelete']);
    }

    public function index(MarketingCampaignsDataTable $table, AuctionReportManagementService $options)
    {
        return $table->render('tenant.marketing-campaigns.index', [
            'statistics' => $options->campaignStatistics(),
            'auctionOptions' => $options->auctionOptions(),
        ]);
    }

    public function edit($target, AuctionReportManagementService $options)
    {
        $auction = $this->resolveAuction($target);
        app(AuctionReportLock::class)->assertEditable($auction);

        $auction->load(['marketingCampaigns.evidence', 'properties']);

        return view('tenant.marketing-campaigns.form', [
            'auction' => $auction,
            'is_edit' => true,
            'auctionOptions' => $options->editableAuctions(),
            'propertyOptions' => $options->campaignPropertyOptions(),
            'existingCampaigns' => $auction->marketingCampaigns->keyBy('channel'),
            'selectedChannels' => $auction->marketingCampaigns->pluck('channel')->all(),
        ]);
    }

    public function store(SaveMarketingCampaignRequest $request, SaveMarketingCampaignAction $action)
    {
        $validated = $request->validated();
        $auctionId = (int) $validated['auction_id'];
        $auction = Auction::findOrFail($auctionId);

        if ($request->has('channels') || $request->has('campaigns')) {
            $action->executeBatch(
                $auction,
                $validated,
                $request->user('tenant')?->id,
                $request->file('campaigns', [])
            );
        } else {
            $action->execute(
                null,
                $validated,
                $request->user('tenant')?->id,
                $request->file('evidence', [])
            );
        }

        return redirect(tenant_route_name('auctions.show', $auctionId))
            ->with('success', 'تم حفظ الحملات التسويقية وطلب تحديث التقريرين.');
    }

    public function update(SaveMarketingCampaignRequest $request, $target, SaveMarketingCampaignAction $action)
    {
        $auction = $this->resolveAuction($target);
        app(AuctionReportLock::class)->assertEditable($auction);

        $validated = $request->validated();
        $validated['auction_id'] = $auction->id;

        if ($request->has('channels') || $request->has('campaigns')) {
            $action->executeBatch(
                $auction,
                $validated,
                $request->user('tenant')?->id,
                $request->file('campaigns', [])
            );
        } else {
            $existingCampaign = $auction->marketingCampaigns()->where('channel', $validated['channel'] ?? '')->first()
                ?? $auction->marketingCampaigns()->first();

            $action->execute(
                $existingCampaign,
                $validated,
                $request->user('tenant')?->id,
                $request->file('evidence', []),
                $validated['remove_evidence'] ?? []
            );
        }

        return redirect(tenant_route_name('auctions.show', $auction->id))
            ->with('success', 'تم تحديث الحملات التسويقية وطلب تحديث التقريرين.');
    }

    public function destroy($target, DeleteMarketingCampaignAction $action)
    {
        if ($target instanceof MarketingCampaign) {
            $auctionId = $target->auction_id;
            $action->execute($target);
        } else {
            $auction = $this->resolveAuction($target);
            $auctionId = $auction->id;
            $action->executeForAuction($auction);
        }

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الحملات التسويقية للمزاد وطلب تحديث التقريرين.',
            ]);
        }

        return redirect(tenant_route_name('auctions.show', $auctionId))
            ->with('success', 'تم حذف الحملات التسويقية للمزاد وطلب تحديث التقريرين.');
    }

    public function massDelete(Request $request, DeleteMarketingCampaignAction $action)
    {
        $rawIds = $request->input('ids');
        if (is_string($rawIds)) {
            $rawIds = array_map('trim', explode(',', $rawIds));
        }

        $request->merge(['ids' => $rawIds]);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ], [
            'ids.required' => 'حدد مزاداً واحداً على الأقل.',
            'ids.array' => 'يجب إرسال معرفات المزادات في قائمة.',
            'ids.max' => 'يمكن حذف حملات مائة مزاد كحد أقصى في العملية الواحدة.',
            'ids.*.integer' => 'أحد معرفات المزادات غير صالح.',
        ]);

        $auctionIds = array_map('intval', $validated['ids']);

        $auctions = Auction::query()->whereIn('id', $auctionIds)->get();

        if ($auctions->count() !== count($auctionIds)) {
            throw ValidationException::withMessages(['ids' => 'أحد المزادات المحددة غير متاح لحذف حملاته. لم يتم حذف أي سجل.']);
        }

        $lock = app(AuctionReportLock::class);
        foreach ($auctions as $auction) {
            $lock->assertEditable($auction);
        }

        $deletedCount = 0;
        DB::transaction(function () use ($auctions, $action, &$deletedCount) {
            $auctions = Auction::query()->whereKey($auctions->modelKeys())->orderBy('id')->lockForUpdate()->get();
            foreach ($auctions as $auction) {
                app(AuctionReportLock::class)->assertEditable($auction);
            }
            foreach ($auctions as $auction) {
                $campaignsCount = $auction->marketingCampaigns()->count();
                $action->executeForAuction($auction);
                $deletedCount += $campaignsCount;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "تم حذف حملات {$auctions->count()} مزاد بنجاح ({$deletedCount} حملة).",
        ]);
    }

    private function resolveAuction($target): Auction
    {
        if ($target instanceof Auction) {
            return $target;
        }

        if ($target instanceof MarketingCampaign) {
            return $target->auction ?? Auction::findOrFail($target->auction_id);
        }

        if (is_numeric($target)) {
            $auction = Auction::find((int) $target);
            if ($auction) {
                return $auction;
            }

            $campaign = MarketingCampaign::find((int) $target);
            if ($campaign) {
                return $campaign->auction ?? Auction::findOrFail($campaign->auction_id);
            }
        }

        return Auction::findOrFail($target);
    }
}
