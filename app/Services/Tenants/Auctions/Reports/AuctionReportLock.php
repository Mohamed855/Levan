<?php

declare(strict_types=1);

namespace App\Services\Tenants\Auctions\Reports;

use App\Models\Tenants\Auctions\Auction;
use Illuminate\Validation\ValidationException;

final class AuctionReportLock
{
    public function assertEditable(Auction $auction): void
    {
        $auction->unsetRelation('properties')->unsetRelation('establishmentRequest');
        if ($auction->isClosed() || $auction->closing()->whereNotNull('finalized_snapshot')->exists()) {
            throw ValidationException::withMessages(['auction' => 'المزاد منتهٍ ولا يمكن تعديل بياناته أو حملاته أو إقفاله. يمكنك إعادة توليد المخرجات من التفاصيل.']);
        }
    }

    public function assertClosingAllowed(Auction $auction): void
    {
        $this->assertEditable($auction);
        if (! $auction->establishmentRequest?->isApproved()) {
            throw ValidationException::withMessages(['auction' => 'يجب اعتماد طلب إقامة المزاد وتسجيل رقم الموافقة قبل الإقفال.']);
        }
        $savedIds = array_column($auction->establishmentRequest->data['properties'] ?? [], 'property_id');
        $actualIds = $auction->properties->modelKeys();
        if ($actualIds === [] || count($savedIds) !== count(array_unique($savedIds))
            || array_diff($actualIds, $savedIds) !== [] || array_diff($savedIds, $actualIds) !== []) {
            throw ValidationException::withMessages(['auction' => 'عقارات المزاد لا تطابق طلب الإقامة المعتمد. لا يمكن حفظ الإقفال.']);
        }
    }
}
