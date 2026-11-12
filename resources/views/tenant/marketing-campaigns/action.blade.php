@php
    $targetAuction =
        $row ?? $auction ?? ($campaign?->auction ?? \App\Models\Tenants\Auctions\Auction::find($campaign?->auction_id));
    $isClosed = $targetAuction?->isClosed() ?? false;
    $locked = $isClosed;
    $hasCampaigns =
        ($targetAuction?->marketing_campaigns_count ?? 0) > 0 ||
        ($targetAuction?->marketingCampaigns && $targetAuction->marketingCampaigns->isNotEmpty());
@endphp

<x-actions.row class="justify-content-center gap-2">
    @if ($targetAuction)
        <x-actions.show :url="tenant_route_name('auctions.show', $targetAuction->id)" title="تفاصيل المزاد" permission="auctions.view" />
        @if (!$locked)
            <x-actions.edit :url="tenant_route_name('marketing-campaigns.edit', $targetAuction->id)" :title="$hasCampaigns ? 'تعديل الحملات التسويقية' : 'إضافة الحملات التسويقية'" :icon="$hasCampaigns ? 'ti ti-edit' : 'ti ti-file-plus'" :permission="$hasCampaigns ? 'marketing_campaigns.edit' : 'marketing_campaigns.create'" />
            @if ($hasCampaigns)
                <x-actions.delete :id="$targetAuction->id" :url="tenant_route_name('marketing-campaigns.destroy', $targetAuction->id)" title="حذف جميع حملات المزاد"
                    permission="marketing_campaigns.delete" />
            @endif
        @endif
    @endif
</x-actions.row>
