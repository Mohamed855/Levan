@php
    $targetAuction = $row ?? $auction;
@endphp

<x-actions.row class="justify-content-center gap-2">
    <x-actions.show :url="tenant_route_name('auctions.show', $targetAuction)" title="تفاصيل المزاد والتقارير" permission="auctions.view" />
    @if (!$targetAuction->latestReportGeneration?->isProcessing() && $targetAuction->publishedReportGeneration?->isReady())
        <x-actions.show :url="tenant_route_name('auctions.reports.download', [
            'auction' => $targetAuction->id,
            'type' => $targetAuction->report_kind,
        ])" title="تحميل التقرير" icon="ti ti-download" class="btn-icon btn-label-primary"
            permission="auctions.view" />
    @endif
</x-actions.row>
