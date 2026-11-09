@php($targetAuction = $row ?? $auction)
<x-actions.row class="justify-content-center gap-2">
    <x-actions.show :url="tenant_route_name('auctions.show', $targetAuction)" title="تفاصيل المزاد" permission="auction_establishment_requests.view" />
    @if (!$targetAuction->establishmentRequest?->isApproved() && !$targetAuction->isClosed())
        <x-actions.edit :url="tenant_route_name('auction-establishment-requests.edit', $targetAuction)" :title="$targetAuction->establishmentRequest ? 'تعديل طلب إقامة المزاد' : 'إضافة طلب إقامة المزاد'" :icon="$targetAuction->establishmentRequest ? 'ti ti-edit' : 'ti ti-file-plus'" :permission="$targetAuction->establishmentRequest ? 'auction_establishment_requests.edit' : 'auction_establishment_requests.create'" />
    @endif
</x-actions.row>
