@php
    $typeOptions = [
        ['value' => 'billboard', 'label' => 'قالب اللوحة الإعلانية (16:9)'],
        ['value' => 'social', 'label' => 'قالب منصات التواصل الاجتماعي (1:1)'],
    ];

    $selectedType = old('template_type', 'billboard');
    $selectedTemplateId = old('auction_template_id');
    $selectedAuction = old('auction_id', $selectedAuctionId ?? null);
@endphp

<div class="row g-4" id="promotional-ad-form-card" data-templates-map="{{ json_encode($templatesGrouped, JSON_UNESCAPED_UNICODE) }}">
    <div class="col-12">
        <div class="alert alert-primary d-flex align-items-center gap-2 mb-0" role="alert">
            <i class="ti ti-info-circle fs-4"></i>
            <div>
                يتم استبدال المتغيرات المعتمدة في القالب المختار (مثل اسم المزاد، التواريخ، المنصة، المدينة، وغيرها) تلقائياً بالبيانات الخاصة بالمزاد المحدد لتوليد الصورة الترويجية.
            </div>
        </div>
    </div>

    {{-- 1. اختيار نوع القالب --}}
    <div class="col-md-6">
        <x-form.controls.select
            name="template_type"
            id="template_type"
            label="نوع القالب"
            :options="$typeOptions"
            :value="$selectedType"
            placeholder="اختر نوع القالب"
            required
            col="col-12"
        />
    </div>

    {{-- 2. اختيار القالب النشط بناءً على النوع --}}
    <div class="col-md-6">
        <x-form.controls.select
            name="auction_template_id"
            id="auction_template_id"
            label="القالب النشط"
            :options="[]"
            :value="$selectedTemplateId"
            placeholder="اختر القالب"
            required
            col="col-12"
        />
    </div>

    {{-- 3. اختيار المزاد --}}
    <div class="col-12">
        <x-form.controls.select
            name="auction_id"
            id="auction_id"
            label="المزاد المرتبط"
            :options="$auctionOptions"
            :value="$selectedAuction"
            placeholder="اختر المزاد المطلوب استخراج الإعلان له"
            required
            col="col-12"
        />
    </div>
</div>

<div class="mt-4">
    <x-form.wizard.actions>
        <x-form.wizard.previous-button :href="tenant_route_name('auctions.index')" />
        <div class="d-flex gap-2">
            <x-form.wizard.save-button />
        </div>
    </x-form.wizard.actions>
</div>
