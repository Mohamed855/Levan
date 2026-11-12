<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\AuctionClosings;

use App\Actions\Tenants\AuctionClosings\SaveAuctionClosingAction;
use App\DataTables\Tenants\AuctionClosings\AuctionClosingsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\AuctionClosings\SaveAuctionClosingRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Auctions\Reports\AuctionReportLock;
use App\Services\Tenants\Auctions\Reports\AuctionReportManagementService;
use Illuminate\Http\JsonResponse;

final class AuctionClosingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:tenant');
        $this->middleware('can:auction_closings.view')->only(['index', 'edit', 'propertyData']);
    }

    public function index(AuctionClosingsDataTable $table, AuctionReportManagementService $options)
    {
        return $table->render('tenant.auction-closings.index', ['statistics' => $options->closingStatistics()]);
    }

    public function edit(Auction $auction)
    {
        app(AuctionReportLock::class)->assertClosingAllowed($auction);
        abort_unless(auth('tenant')->user()->can($auction->closing ? 'auction_closings.edit' : 'auction_closings.create'), 403);

        return view('tenant.auction-closings.edit', ['auction' => $auction->load(['properties', 'closing', 'attachments' => fn ($query) => $query->where('report_section', 'board_after')])]);
    }

    public function update(SaveAuctionClosingRequest $request, Auction $auction, SaveAuctionClosingAction $action)
    {
        $action->execute($auction, $request->validated(), $request->user('tenant')->id);

        return redirect(tenant_route_name('auctions.show', $auction))->with('success', 'تم حفظ إقفال المزاد وطلب تحديث التقريرين.');
    }

    public function propertyData(Auction $auction, Property $property): JsonResponse
    {
        abort_unless(auth('tenant')->user()->can($auction->closing ? 'auction_closings.edit' : 'auction_closings.create'), 403);
        $auctionProperty = $auction->properties()->where('properties.id', $property->id)->firstOrFail();
        $pivot = $auctionProperty->pivot;
        $attachments = $auction->attachments()
            ->where('report_section', 'board_after')
            ->where('property_id', $property->id)
            ->get();

        return response()->json([
            'id' => $property->id,
            'name' => $property->name,
            'property_number' => $property->property_number,
            'type_label' => $property->type_label,
            'city' => $property->city,
            'district' => $property->district,
            'area' => $property->area,
            'estimated_value' => $property->estimated_value,
            'opening_price' => $pivot->opening_price !== null ? (string) $pivot->opening_price : '',
            'participants_count' => $pivot->participants_count !== null ? (string) $pivot->participants_count : '',
            'bids_count' => $pivot->bids_count !== null ? (string) $pivot->bids_count : '',
            'result_status' => $pivot->result_status === 'pending' ? '' : (string) ($pivot->result_status ?? ''),
            'buyer_name' => (string) ($pivot->buyer_name ?? ''),
            'sale_price' => $pivot->sale_price !== null ? (string) $pivot->sale_price : '',
            'unsold_reason' => (string) ($pivot->unsold_reason ?? ''),
            'result_notes' => (string) ($pivot->result_notes ?? ''),
            'attachments' => $attachments->map(fn ($file) => [
                'id' => $file->id,
                'file_name' => $file->file_name,
                'file_url' => $file->file_url,
                'file_size' => $file->file_size_formatted,
                'file_type' => $file->file_type,
            ]),
        ]);
    }
}
