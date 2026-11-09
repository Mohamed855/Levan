<?php

declare(strict_types=1);

namespace App\Actions\Tenants\AuctionEstablishments;

use App\Http\Requests\Tenants\AuctionEstablishments\ApproveAuctionEstablishmentRequest;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class ApproveAuctionEstablishmentRequestAction
{
    public function execute(Auction $auction, string $approvalNumber, ?int $actorId): AuctionEstablishmentRequest
    {
        $approvalNumber = trim($approvalNumber);
        Validator::make(['approval_number' => $approvalNumber], (new ApproveAuctionEstablishmentRequest)->rules())->validate();
        $request = DB::transaction(function () use ($auction, $approvalNumber, $actorId): AuctionEstablishmentRequest {
            $auction = Auction::query()->lockForUpdate()->findOrFail($auction->id);
            $request = AuctionEstablishmentRequest::query()->where('auction_id', $auction->id)->lockForUpdate()->first();
            if ($auction->isClosed() || ! $request || $request->isApproved()) {
                throw ValidationException::withMessages(['approval_number' => 'يجب حفظ طلب قيد المراجعة قبل اعتماده ولا يمكن إعادة اعتماد طلب معتمد أو مزاد مقفل.']);
            }
            $snapshotIds = array_column($request->data['properties'] ?? [], 'property_id');
            $actualIds = $auction->properties()->pluck('properties.id')->all();
            if ($actualIds === [] || array_diff($actualIds, $snapshotIds) || array_diff($snapshotIds, $actualIds)) {
                throw ValidationException::withMessages(['approval_number' => 'تغيرت عقارات المزاد. حدّث الطلب قبل اعتماده.']);
            }
            $request->approval_number = $approvalNumber;
            $request->approved_at = now();
            $request->approved_by = $actorId;
            $request->updated_by = $actorId;
            $request->save();

            return $request->fresh();
        });
        app(AuctionRegulatoryFormService::class)->requestGeneration($auction->fresh(), 'establishment');

        return $request;
    }
}
