<x-actions.row class="justify-content-center gap-2">
    <x-actions.show :url="tenant_route_name('auctions.show', $campaign->auction_id)" title="تفاصيل المزاد" permission="auctions.view" />
    @php($locked = $campaign->auction?->is_finalized ?? $campaign->auction?->closing()->whereNotNull('finalized_snapshot')->exists())
    @if(!$locked)
        <x-actions.edit :url="tenant_route_name('marketing-campaigns.edit', $campaign)" title="تعديل الحملة" permission="marketing_campaigns.edit" />
        <x-actions.delete :id="$campaign->id" :url="tenant_route_name('marketing-campaigns.destroy', $campaign)" title="حذف الحملة" permission="marketing_campaigns.delete" />
    @endif
</x-actions.row>