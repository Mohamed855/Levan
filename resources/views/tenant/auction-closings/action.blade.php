@php
    $targetAuction = $row ?? $auction;
@endphp

<x-actions.row class="justify-content-center gap-2">
    <x-actions.show :url="tenant_route_name('auctions.show', $targetAuction)" title="تفاصيل المزاد" permission="auctions.view" />
    @if (!$targetAuction->closing?->is_finalized)
        <x-actions.edit :url="tenant_route_name('auction-closings.edit', $targetAuction)" :title="$targetAuction->closing ? 'تعديل إقفال المزاد' : 'إضافة إقفال المزاد'" :icon="$targetAuction->closing ? 'ti ti-edit' : 'ti ti-lock'" :permission="$targetAuction->closing ? 'auction_closings.edit' : 'auction_closings.create'" />
    @endif
</x-actions.row>
