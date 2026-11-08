<x-actions.row class="justify-content-center gap-2">
    <x-actions.show :url="tenant_route_name('auctions.show', $auction)" title="تفاصيل المزاد والتقارير" permission="auctions.view" />
    @if($auction->publishedReportGeneration?->isReady())
        <x-actions.show :url="tenant_route_name('auctions.reports.download', ['auction' => $auction->id, 'type' => $auction->report_kind])"
            title="تنزيل التقرير" icon="ti ti-download" class="btn-icon btn-label-success" permission="auctions.view" />
    @endif
</x-actions.row>