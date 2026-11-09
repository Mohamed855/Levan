<x-actions.row class="justify-content-center gap-2">
    <x-actions.show :url="tenant_route_name('auctions.show', $auction)" title="تفاصيل المزاد" permission="auctions.view" />
    @if (!$auction->closing?->is_finalized)
        <x-actions.edit :url="tenant_route_name('auction-closings.edit', $auction)" :title="$auction->closing ? 'تعديل إقفال المزاد' : 'إضافة إقفال المزاد'" :icon="$auction->closing ? 'ti ti-edit' : 'ti ti-lock'" :permission="$auction->closing ? 'auction_closings.edit' : 'auction_closings.create'" />
    @endif
</x-actions.row>
