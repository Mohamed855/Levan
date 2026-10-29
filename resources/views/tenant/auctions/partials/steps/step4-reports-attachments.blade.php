@php
    $isEdit = isset($auction) && $auction->exists;
    $marketing = old('marketing', $isEdit ? ($auction->marketing_data ?? []) : []);
@endphp

<div class="row g-4">
    {{-- 1. مقدمة التقرير --}}
    <x-form.controls.textarea
        name="marketing[introduction]"
        id="marketing_introduction"
        label="مقدمة التقرير"
        placeholder="أدخل مقدمة تعريفية وموجزة للتقرير..."
        :value="$marketing['introduction'] ?? null"
        col="col-12"
        rows="3"
    />

    {{-- 2. رقم التواصل --}}
    <x-form.controls.input
        name="marketing[contacts][phone]"
        id="marketing_contacts_phone"
        label="رقم التواصل"
        placeholder="مثال: 0500000000"
        :value="$marketing['contacts']['phone'] ?? null"
        col="col-md-4"
    />

    {{-- 3. الرقم الموحد --}}
    <x-form.controls.input
        name="marketing[contacts][unified_phone]"
        id="marketing_contacts_unified_phone"
        label="الرقم الموحد"
        placeholder="مثال: 920000000"
        :value="$marketing['contacts']['unified_phone'] ?? null"
        col="col-md-4"
    />

    {{-- 4. حساب التواصل الاجتماعي --}}
    <x-form.controls.input
        name="marketing[contacts][social]"
        id="marketing_contacts_social"
        label="حساب التواصل الاجتماعي"
        placeholder="مثال: @levan_sa"
        :value="$marketing['contacts']['social'] ?? null"
        col="col-md-4"
    />

    {{-- 5. تاريخ حملة الواتساب --}}
    <x-form.controls.input
        name="marketing[whatsapp_date]"
        id="marketing_whatsapp_date"
        label="تاريخ حملة الواتساب"
        type="date"
        :value="$marketing['whatsapp_date'] ?? null"
        col="col-md-6"
    />

    <div class="col-12 border-top pt-2"></div>

    {{-- 6. صورة غلاف التقرير --}}
    <x-form.controls.image-upload
        name="report_image"
        id="report_image"
        label="صورة غلاف التقرير"
        :value="$isEdit && $auction->report_image ? tasset($auction->report_image) : null"
        accept="image/jpeg,image/png,image/webp"
        help="اختيارية؛ عند عدم رفع صورة تُستخدم صورة المثال الأصلية. اتركها دون تغيير للاحتفاظ بالصورة المحفوظة."
        col="col-md-6"
    />

    {{-- 7. صورة غلاف المزاد --}}
    <x-form.controls.image-upload
        name="cover_image"
        id="cover_image"
        label="صورة غلاف المزاد"
        :value="$isEdit && $auction->cover_image ? tasset($auction->cover_image) : null"
        accept="image/jpeg,image/png,image/webp"
        help="اختيارية؛ خلفية للصفحة الأولى من المخرجات فقط. بدونها يظل الغلاف الافتراضي الحالي."
        col="col-md-6"
    />

    {{-- 8. ملفات إضافية --}}
    <div class="col-12 border-top pt-3">
        <h6 class="fw-bold mb-3"><i class="ti ti-files me-1"></i>ملفات إضافية ووثائق</h6>
        @if ($isEdit && $auction->attachments->where('category', 'auction_doc')->isNotEmpty())
            <div class="assignment-file-grid mb-3">
                @foreach ($auction->attachments->where('category', 'auction_doc') as $attachment)
                    <x-ui.file-card :file="$attachment" />
                @endforeach
            </div>
        @endif
        <x-form.controls.file
            name="attachments[]"
            id="attachments"
            label="ملفات إضافية"
            multiple
            accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx"
            help="الحد الأقصى لكل ملف: 10MB"
            col="col-12"
        />
    </div>
</div>
