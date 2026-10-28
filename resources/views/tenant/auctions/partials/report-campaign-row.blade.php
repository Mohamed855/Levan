@php
    $campaign = $campaign ?? [];
    $channel = \App\Services\Tenants\Auctions\Reports\AuctionReportChannels::key($campaign['platform'] ?? '');
    $prefix = 'marketing[campaigns]['.$index.']';
@endphp
<div class="row g-3 mb-3 border-bottom pb-3 campaign-row" data-index="{{ $index }}">
    <x-form.controls.select :name="$prefix.'[platform]'" label="القناة" :options="__('auction_reports.channels', [], 'ar')" :value="$channel" :select2="false" col="col-md-4" />
    <x-form.controls.input :name="$prefix.'[platform_label]'" label="اسم القناة الأخرى (اختياري)" :value="$campaign['platform_label'] ?? ($channel === 'other' ? ($campaign['platform'] ?? '') : '')" col="col-md-4" />
    <x-form.controls.input :name="$prefix.'[url]'" type="url" label="رابط الحملة" :value="$campaign['url'] ?? null" col="col-md-4" />
    @foreach (array_merge(config('auction-report-inputs.integer_metrics'), config('auction-report-inputs.decimal_metrics'), config('auction-report-inputs.signed_metrics')) as $metric)
        <x-form.controls.input :name="$prefix.'['.$metric.']'" type="number" :label="__('auction_reports.metrics.'.$metric, [], 'ar')" :value="$campaign[$metric] ?? null" :step="in_array($metric, config('auction-report-inputs.integer_metrics'), true) ? '1' : 'any'" col="col-md-3" />
    @endforeach
    <div class="col-12"><button type="button" class="btn btn-icon btn-sm btn-danger remove-campaign" aria-label="حذف الحملة"><i class="ti ti-trash" aria-hidden="true"></i></button></div>
</div>
