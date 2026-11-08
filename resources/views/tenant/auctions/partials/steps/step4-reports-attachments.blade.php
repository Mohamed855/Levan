@php
    $isEdit = isset($auction) && $auction->exists;
    $marketing = old('marketing', $isEdit ? $auction->marketing_data ?? [] : []);
@endphp

<div class="row g-4">
    {{-- 1. مقدمة التقرير --}}
    <x-form.controls.textarea name="marketing[introduction]" id="marketing_introduction" label="مقدمة التقرير"
        placeholder="أدخل مقدمة تعريفية وموجزة للتقرير..." :value="$marketing['introduction'] ?? null" col="col-12" rows="3" />

    <div class="col-12 border-top pt-2"></div>

    {{-- 6. صورة غلاف التقرير --}}
    <x-form.controls.image-upload name="report_image" id="report_image" label="صورة غلاف التقرير" :value="$isEdit && $auction->report_image ? tasset($auction->report_image) : null"
        accept="image/jpeg,image/png,image/webp"
        help="عند عدم رفع صورة تُستخدم صورة المثال الأصلية. اتركها دون تغيير للاحتفاظ بالصورة المحفوظة."
        col="col-md-6" />

    {{-- 7. صورة غلاف المزاد --}}
    <x-form.controls.image-upload name="cover_image" id="cover_image" label="صورة غلاف المزاد" :value="$isEdit && $auction->cover_image ? tasset($auction->cover_image) : null"
        accept="image/jpeg,image/png,image/webp"
        help="خلفية للصفحة الأولى من المخرجات فقط. بدونها يظل الغلاف الافتراضي الحالي." col="col-md-6" />

    {{-- 8. ملفات إضافية --}}
    <div class="col-12 border-top pt-3">
        @if ($isEdit && $auction->attachments->where('category', 'auction_doc')->isNotEmpty())
            <div class="assignment-file-list mb-3">
                @foreach ($auction->attachments->where('category', 'auction_doc') as $attachment)
                    <x-ui.file-card :file="$attachment" />
                @endforeach
            </div>
        @endif
        <x-form.controls.file name="attachments[]" id="attachments" label="ملفات إضافية" multiple
            accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" help="الحد الأقصى لكل ملف: 10MB" col="col-12" />
    </div>
</div>
