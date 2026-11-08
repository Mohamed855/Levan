<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\AuctionClosings;

use App\Actions\Tenants\AuctionClosings\SaveAuctionClosingAction;
use App\DataTables\Tenants\AuctionClosings\AuctionClosingsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\AuctionClosings\SaveAuctionClosingRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\Reports\AuctionReportLock;
use App\Services\Tenants\Auctions\Reports\AuctionReportManagementService;

final class AuctionClosingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:tenant');
        $this->middleware('can:auction_closings.view')->only(['index', 'edit']);
    }

    public function index(AuctionClosingsDataTable $table, AuctionReportManagementService $options)
    {
        return $table->render('tenant.auction-closings.index', ['statistics' => $options->closingStatistics()]);
    }

    public function edit(Auction $auction)
    {
        app(AuctionReportLock::class)->assertEditable($auction);
        abort_unless(auth('tenant')->user()->can($auction->closing ? 'auction_closings.edit' : 'auction_closings.create'), 403);

        return view('tenant.auction-closings.edit', ['auction' => $auction->load(['properties', 'closing', 'attachments' => fn ($query) => $query->where('report_section', 'board_after')])]);
    }

    public function update(SaveAuctionClosingRequest $request, Auction $auction, SaveAuctionClosingAction $action)
    {
        $action->execute($auction, $request->validated(), $request->user('tenant')->id);

        return redirect(tenant_route_name('auctions.show', $auction))->with('success', 'تم حفظ إقفال المزاد وطلب تحديث التقريرين.');
    }
}
