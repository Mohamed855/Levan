@php
    $isEdit = isset($auction) && $auction->exists;
    $marketing = old('marketing', $isEdit ? ($auction->marketing_data ?? []) : []);
@endphp
<div class="row g-4">
    <div class="col-12"><h6 class="fw-bold mb-0"><i class="ti ti-photo me-1"></i>صور التقرير والمزاد</h6></div>
    <x-form.controls.file name="report_image" label="صورة غلاف التقرير" :value="$isEdit && $auction->report_image ? tasset($auction->report_image) : null" :required="!$isEdit || !$auction->report_image" accept="image/jpeg,image/png,image/webp" help="مطلوبة للتقرير؛ اتركها دون تغيير للاحتفاظ بالصورة الحالية." col="col-md-6" />
    <x-form.controls.file name="cover_image" label="صورة غلاف المزاد" :value="$isEdit && $auction->cover_image ? tasset($auction->cover_image) : null" accept="image/jpeg,image/png,image/webp" help="اختيارية؛ تظهر في مخرجات المزاد." col="col-md-6" />
    <div class="col-12 border-top pt-3">
        <h6 class="fw-bold mb-3">نتائج عقارات المزاد واللوحات</h6>
        <div id="property-results-container">
            @if ($isEdit)
                @foreach ($auction->properties as $prop)
                    @php
                        $board = $prop->pivot->board_data ?? [];
                        $board = is_string($board) ? (json_decode($board, true) ?: []) : $board;
                        $result = ['status' => $prop->pivot->result_status] + collect($prop->pivot->getAttributes())->only(['opening_price', 'sale_price', 'participants_count', 'bids_count', 'unsold_reason'])->all();
                        foreach (['count', 'dimensions', 'location', 'notes'] as $key) { $result['board_'.$key] = $board[$key] ?? null; }
                        $result = old('property_results.'.$prop->id, $result);
                    @endphp
                    @include('tenant.auctions.partials.report-property-result', ['propertyId' => $prop->id, 'propertyName' => $prop->name.' — '.$prop->property_number, 'result' => $result])
                @endforeach
            @endif
            <p class="text-muted" id="results-empty-msg">اختر العقارات في الخطوة السابقة لإدخال نتائجها.</p>
        </div>
    </div>
    <div class="col-12 border-top pt-3"><h6 class="fw-bold mb-0">نصوص التقرير والتواصل</h6></div>
    <x-form.controls.textarea name="marketing[introduction]" label="مقدمة التقرير" :value="$marketing['introduction'] ?? null" col="col-md-6" />
    <x-form.controls.textarea name="marketing[results_narrative]" label="تفاصيل نتائج المزاد" :value="$marketing['results_narrative'] ?? null" col="col-md-6" />
    <x-form.controls.input name="marketing[contacts][phone]" label="رقم التواصل" :value="$marketing['contacts']['phone'] ?? null" col="col-md-4" />
    <x-form.controls.input name="marketing[contacts][unified_phone]" label="الرقم الموحد" :value="$marketing['contacts']['unified_phone'] ?? null" col="col-md-4" />
    <x-form.controls.input name="marketing[contacts][social]" label="حساب التواصل الاجتماعي" :value="$marketing['contacts']['social'] ?? null" col="col-md-4" />
    <x-form.controls.input name="marketing[whatsapp_date]" label="تاريخ حملة الواتساب" type="date" :value="$marketing['whatsapp_date'] ?? null" col="col-md-6" />
    <div class="col-12">
        <section class="assignment-properties-table-card mb-4">
            <header class="assignment-properties-table-card__header"><h6 class="mb-0">حملات التسويق</h6><button type="button" class="btn btn-sm btn-primary" id="add-campaign-btn"><i class="ti ti-plus me-1"></i>إضافة حملة</button></header>
            <div class="p-3" id="campaigns-container">
                <p class="text-muted" id="campaigns-empty">لا توجد حملات مضافة بعد. اترك الإحصاء فارغًا إن لم يكن متاحًا؛ الصفر قيمة فعلية.</p>
                @foreach ($marketing['campaigns'] ?? [] as $index => $campaign)
                    @include('tenant.auctions.partials.report-campaign-row', compact('index', 'campaign'))
                @endforeach
            </div>
        </section>
        <section class="assignment-properties-table-card mb-4">
            <header class="assignment-properties-table-card__header"><h6 class="mb-0">روابط التسويق</h6><button type="button" class="btn btn-sm btn-primary" id="add-link-btn"><i class="ti ti-plus me-1"></i>إضافة رابط</button></header>
            <div class="p-3" id="links-container">
                <p class="text-muted" id="links-empty">لا توجد روابط مضافة بعد.</p>
                @foreach ($marketing['links'] ?? [] as $index => $link)
                    @include('tenant.auctions.partials.report-link-row', compact('index', 'link'))
                @endforeach
            </div>
        </section>
        <x-form.controls.file name="marketing_evidence[]" label="أدلة التسويق والمستندات" multiple accept=".pdf,.png,.jpg,.jpeg,.webp" help="حدد قسم وقناة كل ملف أدناه. يمكن تغيير تصنيف الملفات المحفوظة دون رفعها مجددًا." col="col-12" />
        <div id="new-evidence-metadata" class="mt-3" aria-live="polite"></div>
        @if ($isEdit)
            @foreach ($auction->attachments->whereNotNull('report_section') as $attachment)
                @include('tenant.auctions.partials.report-evidence-row', ['index' => $attachment->id, 'prefix' => 'report_attachments['.$attachment->id.']', 'filename' => $attachment->file_name, 'meta' => array_replace($attachment->report_metadata ?? [], ['section' => $attachment->report_section, 'display_order' => $attachment->display_order])])
            @endforeach
        @endif
    </div>
    <div class="col-12 border-top pt-3">
        <h6 class="fw-bold mb-3">صور اللوحات الإعلانية</h6>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">صور اللوحات قبل المزاد</label><div id="board-before-container"><p class="text-muted small">اختر العقارات أولًا لإضافة صور لوحاتها.</p></div></div>
            <div class="col-md-6"><label class="form-label">صور اللوحات بعد المزاد</label><div id="board-after-container"><p class="text-muted small">اختر العقارات أولًا لإضافة صور لوحاتها.</p></div></div>
        </div>
    </div>
    <div class="col-12 border-top pt-3">
        <h6 class="fw-bold mb-3">مرفقات ووثائق المزاد</h6>
        @if ($isEdit)
            <ul>@foreach ($auction->attachments->where('category', 'auction_doc') as $attachment)<li>{{ $attachment->file_name }}</li>@endforeach</ul>
        @endif
        <x-form.controls.file name="attachments[]" label="ملفات إضافية" multiple accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" help="الحد الأقصى لكل ملف: 10MB" col="col-12" />
    </div>
</div>
<template id="report-property-template">@include('tenant.auctions.partials.report-property-result', ['propertyId' => '__INDEX__', 'propertyName' => '', 'result' => []])</template>
<template id="report-campaign-template">@include('tenant.auctions.partials.report-campaign-row', ['index' => '__INDEX__', 'campaign' => []])</template>
<template id="report-link-template">@include('tenant.auctions.partials.report-link-row', ['index' => '__INDEX__', 'link' => []])</template>
<template id="report-evidence-template">@include('tenant.auctions.partials.report-evidence-row', ['index' => '__INDEX__', 'prefix' => 'marketing_evidence_meta[__INDEX__]', 'meta' => []])</template>
