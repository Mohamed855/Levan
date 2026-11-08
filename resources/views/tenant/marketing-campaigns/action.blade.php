@php
    $targetAuction = $auction ?? $campaign?->auction ?? \App\Models\Tenants\Auctions\Auction::find($campaign?->auction_id);
    $locked = $targetAuction?->is_finalized ?? $targetAuction?->closing()->whereNotNull('finalized_snapshot')->exists();
@endphp

<x-actions.row class="justify-content-center gap-2">
    @if($targetAuction)
        <x-actions.show :url="tenant_route_name('auctions.show', $targetAuction->id)" title="تفاصيل المزاد" permission="auctions.view" />
        @if(!$locked)
            <x-actions.edit :url="tenant_route_name('marketing-campaigns.edit', $targetAuction->id)" title="تعديل الحملات التسويقية" permission="marketing_campaigns.edit" />
            <x-actions.delete :id="$targetAuction->id" :url="tenant_route_name('marketing-campaigns.destroy', $targetAuction->id)" title="حذف جميع حملات المزاد" permission="marketing_campaigns.delete" />
        @endif
    @endif
</x-actions.row>
