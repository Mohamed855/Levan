@php
    $campaign = $campaign ?? [];
    $isFirst = $isFirst ?? ((int) ($index ?? 0) === 0);
    $channel = \App\Services\Tenants\Auctions\Reports\AuctionReportChannels::key($campaign['platform'] ?? '');
    $prefix = 'marketing[campaigns]['.$index.']';
    $containerClass = $isFirst ? 'campaign-row mb-4' : 'campaign-row border rounded p-3 mb-4 position-relative';
@endphp

<div class="{{ $containerClass }}" data-index="{{ $index }}">
    @if (! $isFirst)
        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
            <h6 class="fw-bold mb-0 text-primary">
                <i class="ti ti-speakerphone me-1"></i>حملة إضافية #{{ ((int) $index) + 1 }}
            </h6>
            <button type="button" class="btn btn-sm btn-icon btn-danger remove-campaign" aria-label="حذف الحملة">
                <i class="ti ti-trash"></i>
            </button>
        </div>
    @endif

    <div class="row g-3">
        <x-form.controls.select
            :name="$prefix.'[platform]'"
            label="القناة الإعلانية"
            placeholder="اختر القناة..."
            :options="__('auction_reports.channels', [], 'ar')"
            :value="$channel"
            :select2="true"
            col="col-md-4"
        />

        <x-form.controls.input
            :name="$prefix.'[platform_label]'"
            label="اسم القناة الأخرى"
            placeholder="مثال: إنستغرام أو قناة خاصة"
            :value="$campaign['platform_label'] ?? ($channel === 'other' ? ($campaign['platform'] ?? '') : '')"
            col="col-md-4"
        />

        <x-form.controls.input
            :name="$prefix.'[url]'"
            type="url"
            label="رابط الحملة"
            placeholder="https://..."
            :value="$campaign['url'] ?? null"
            col="col-md-4"
        />

        {{-- حقول روابط التسويق المدمجة --}}
        <x-form.controls.input
            :name="$prefix.'[link_label]'"
            label="تسمية الرابط"
            placeholder="مثال: إعلان X الرئيسي"
            :value="$campaign['link_label'] ?? ($campaign['label'] ?? null)"
            col="col-md-6"
        />

        <x-form.controls.select
            :name="$prefix.'[link_role]'"
            label="استخدام / دور الرابط"
            placeholder="اختر دور الرابط..."
            :options="__('auction_reports.link_roles', [], 'ar')"
            :value="$campaign['link_role'] ?? ($campaign['role'] ?? 'other')"
            :select2="true"
            col="col-md-6"
        />

        {{-- إحصائيات ومؤشرات الحملة --}}
        @foreach (array_merge(config('auction-report-inputs.integer_metrics'), config('auction-report-inputs.decimal_metrics'), config('auction-report-inputs.signed_metrics')) as $metric)
            <x-form.controls.input
                :name="$prefix.'['.$metric.']'"
                type="number"
                :label="__('auction_reports.metrics.'.$metric, [], 'ar')"
                placeholder="0"
                :value="$campaign[$metric] ?? null"
                :step="in_array($metric, config('auction-report-inputs.integer_metrics'), true) ? '1' : 'any'"
                col="col-md-3"
            />
        @endforeach
    </div>
</div>
