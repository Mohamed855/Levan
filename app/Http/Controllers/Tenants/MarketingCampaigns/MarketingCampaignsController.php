<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\MarketingCampaigns;

use App\Actions\Tenants\MarketingCampaigns\DeleteMarketingCampaignAction;
use App\Actions\Tenants\MarketingCampaigns\SaveMarketingCampaignAction;
use App\DataTables\Tenants\MarketingCampaigns\MarketingCampaignsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\MarketingCampaigns\SaveMarketingCampaignRequest;
use App\Models\Tenants\MarketingCampaigns\MarketingCampaign;
use App\Services\Tenants\Auctions\Reports\AuctionReportLock;
use App\Services\Tenants\Auctions\Reports\AuctionReportManagementService;

final class MarketingCampaignsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:tenant');
        $this->middleware('can:marketing_campaigns.view')->only('index');
        $this->middleware('can:marketing_campaigns.create')->only(['create', 'store']);
        $this->middleware('can:marketing_campaigns.edit')->only(['edit', 'update']);
        $this->middleware('can:marketing_campaigns.delete')->only('destroy');
    }

    public function index(MarketingCampaignsDataTable $table, AuctionReportManagementService $options)
    {
        return $table->render('tenant.marketing-campaigns.index', ['statistics' => $options->campaignStatistics(), 'auctionOptions' => $options->auctionOptions()]);
    }

    public function create(AuctionReportManagementService $options)
    {
        return view('tenant.marketing-campaigns.form', ['campaign' => new MarketingCampaign, 'auctionOptions' => $options->editableAuctions(), 'propertyOptions' => $options->campaignPropertyOptions()]);
    }

    public function edit(MarketingCampaign $campaign, AuctionReportManagementService $options)
    {
        app(AuctionReportLock::class)->assertEditable($campaign->auction);

        return view('tenant.marketing-campaigns.form', ['campaign' => $campaign->load('evidence'), 'auctionOptions' => $options->editableAuctions(), 'propertyOptions' => $options->campaignPropertyOptions()]);
    }

    public function store(SaveMarketingCampaignRequest $request, SaveMarketingCampaignAction $action)
    {
        $campaign = $action->execute(null, $request->validated(), $request->user('tenant')->id, $request->file('evidence', []));

        return redirect(tenant_route_name('auctions.show', $campaign->auction_id))->with('success', 'تم حفظ الحملة وطلب تحديث التقريرين.');
    }

    public function update(SaveMarketingCampaignRequest $request, MarketingCampaign $campaign, SaveMarketingCampaignAction $action)
    {
        $campaign = $action->execute($campaign, $request->validated(), $request->user('tenant')->id, $request->file('evidence', []), $request->validated('remove_evidence', []));

        return redirect(tenant_route_name('auctions.show', $campaign->auction_id))->with('success', 'تم تحديث الحملة وطلب تحديث التقريرين.');
    }

    public function destroy(MarketingCampaign $campaign, DeleteMarketingCampaignAction $action)
    {
        $auctionId = $campaign->auction_id;
        $action->execute($campaign);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف الحملة وطلب تحديث التقريرين.']);
        }

        return redirect(tenant_route_name('auctions.show', $auctionId))->with('success', 'تم حذف الحملة وطلب تحديث التقريرين.');
    }
}
