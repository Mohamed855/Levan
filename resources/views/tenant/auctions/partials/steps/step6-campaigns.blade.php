@php
    $isEdit = isset($auction) && $auction->exists;
    $marketing = old('marketing', $isEdit ? $auction->marketing_data ?? [] : []);
    $campaigns = $marketing['campaigns'] ?? [];
    if (empty($campaigns)) {
        $campaigns = [[]]; // حملة أساسية واحدة على الأقل مضافة مسبقاً
    }
@endphp

<div class="row g-4">
    <div class="col-12">
        <section class="assignment-properties-table-card mb-4">
            <header
                class="assignment-properties-table-card__header d-flex justify-content-between align-items-center p-3 border-bottom">
                <div>
                    <h6 class="mb-0 fw-bold"><i class="ti ti-speakerphone me-1"></i>الحملات التسويقية للمزاد</h6>
                    <small class="text-muted">توثيق الحملات والمؤشرات الإعلانية للتقرير التسويقي.</small>
                </div>
                <button type="button" class="btn btn-sm btn-primary" id="add-campaign-btn">
                    <i class="ti ti-plus me-1"></i>إضافة حملة
                </button>
            </header>

            <div class="p-3" id="campaigns-container">
                @foreach ($campaigns as $index => $campaign)
                    @include('tenant.auctions.partials.report-campaign-row', [
                        'index' => $index,
                        'campaign' => $campaign,
                        'isFirst' => (int) $index === 0,
                    ])
                @endforeach
            </div>
        </section>
    </div>

    {{-- أدلة ومستندات الحملات التسويقية --}}
    <div class="col-12 border-top pt-3">
        <h6 class="fw-bold mb-3"><i class="ti ti-paperclip me-1"></i>أدلة التسويق والمستندات الداعمة</h6>
        <x-form.controls.file name="marketing_evidence[]" id="marketing_evidence"
            label="رفع أدلة ومستندات التسويق (لقطات شاشة أو ملفات PDF)" multiple accept=".pdf,.png,.jpg,.jpeg,.webp"
            help="يمكنك رفع لقطات شاشة أو تقارير منصات التواصل لإثبات نتائج الحملات." col="col-12" />
        <div id="new-evidence-metadata" class="mt-3" aria-live="polite"></div>
        @if ($isEdit)
            @foreach ($auction->attachments->whereNotNull('report_section') as $attachment)
                @include('tenant.auctions.partials.report-evidence-row', [
                    'index' => $attachment->id,
                    'prefix' => 'report_attachments[' . $attachment->id . ']',
                    'filename' => $attachment->file_name,
                    'meta' => array_replace($attachment->report_metadata ?? [], [
                        'section' => $attachment->report_section,
                        'display_order' => $attachment->display_order,
                    ]),
                ])
            @endforeach
        @endif
    </div>
</div>

<template id="report-campaign-template">
    @include('tenant.auctions.partials.report-campaign-row', [
        'index' => '__INDEX__',
        'campaign' => [],
        'isFirst' => false,
    ])
</template>

<template id="report-evidence-template">
    @include('tenant.auctions.partials.report-evidence-row', [
        'index' => '__INDEX__',
        'prefix' => 'marketing_evidence_meta[__INDEX__]',
        'meta' => [],
    ])
</template>
