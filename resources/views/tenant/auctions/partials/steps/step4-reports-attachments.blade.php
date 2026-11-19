@php
    $isEdit = isset($auction) && $auction->exists;
    $marketing = old('marketing', $isEdit ? $auction->marketing_data ?? [] : []);
    $estDisclosures = $isEdit ? ($auction->establishmentRequest?->data['disclosures'] ?? []) : [];
    $dispositionRights = [
        'none' => 'لا يوجد',
        'preemption' => 'شفعة',
        'session_option' => 'خيار المجلس',
        'condition_option' => 'خيار الشرط',
        'earnest_sale' => 'بيع العربون',
        'other' => 'أخرى',
    ];
    $selectedRights = old('disclosures.disposition_rights', $estDisclosures['disposition_rights'] ?? ['none']);
    if (!is_array($selectedRights)) {
        $selectedRights = [$selectedRights];
    }
@endphp

<div class="row g-4">
    {{-- 1. مقدمة التقرير --}}
    <x-form.controls.textarea name="marketing[introduction]" id="marketing_introduction" label="مقدمة التقرير"
        placeholder="أدخل مقدمة تعريفية وموجزة للتقرير..." :value="$marketing['introduction'] ?? null" required col="col-12" rows="3" />
    <x-form.controls.textarea name="marketing[report_summary]" id="marketing_report_summary" label="ملخص التقرير"
        placeholder="أدخل ملخص التقرير..." :value="$marketing['report_summary'] ?? null" col="col-12" rows="3" />

    <div class="col-12 border-top pt-2"></div>

    {{-- صور الأغلفة --}}
    <x-form.controls.image-upload name="report_image" id="report_image" label="صورة غلاف التقرير" :value="$isEdit && $auction->report_image ? tasset($auction->report_image) : null"
        accept="image/jpeg,image/png,image/webp"
        help="عند عدم رفع صورة تُستخدم صورة المثال الأصلية. اتركها دون تغيير للاحتفاظ بالصورة المحفوظة."
        col="col-md-6" />

    <x-form.controls.image-upload name="cover_image" id="cover_image" label="صورة غلاف المزاد" :value="$isEdit && $auction->cover_image ? tasset($auction->cover_image) : null"
        accept="image/jpeg,image/png,image/webp"
        help="خلفية للصفحة الأولى من المخرجات فقط. بدونها يظل الغلاف الافتراضي الحالي." col="col-md-6" />

    {{-- الإفصاحات والتعهدات النظامية --}}
    <div class="col-12 border-top pt-3">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="ti ti-shield-check fs-4 text-primary"></i>
            <h6 class="fw-bold mb-0">الإفصاحات والتعهدات النظامية</h6>
        </div>
        <p class="text-muted small mb-0">الإقرارات والتعهدات المطلوبة نظامياً لطلب إقامة المزاد وفق ضوابط إنفاذ</p>
    </div>

    <x-form.controls.select name="disclosures[disposition_rights][]" id="disclosures_disposition_rights" label="حقوق التصرف"
        :options="$dispositionRights" :value="$selectedRights" required multiple col="col-md-6" />

    <x-form.controls.input name="disclosures[disposition_right_other]" id="disclosures_disposition_right_other"
        label="تفاصيل حقوق التصرف الأخرى" placeholder="في حال اختيار أخرى"
        :value="old('disclosures.disposition_right_other', $estDisclosures['disposition_right_other'] ?? '')" col="col-md-6" />

    <div class="col-12">
        <div class="card bg-light-subtle border p-3">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="disclosures[no_conflict_of_interest]" id="no_conflict_of_interest" value="1"
                    @checked(old('disclosures.no_conflict_of_interest', $estDisclosures['no_conflict_of_interest'] ?? true)) required>
                <label class="form-check-label fw-medium" for="no_conflict_of_interest">
                    أقر بعدم وجود أي تضارب مصالح مباشر أو غير مباشر في إقامة وإدارة هذا المزاد.
                </label>
            </div>
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="disclosures[information_accuracy]" id="information_accuracy" value="1"
                    @checked(old('disclosures.information_accuracy', $estDisclosures['information_accuracy'] ?? true)) required>
                <label class="form-check-label fw-medium" for="information_accuracy">
                    أتعهد بصحة واكتمال كافة البيانات والمستندات والأسعار المرفقة وتحمل المسؤولية النظامية حيالها.
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="disclosures[adherence_to_regulations]" id="adherence_to_regulations" value="1"
                    @checked(old('disclosures.adherence_to_regulations', $estDisclosures['adherence_to_regulations'] ?? true)) required>
                <label class="form-check-label fw-medium" for="adherence_to_regulations">
                    ألتزم بكافة الضوابط والقواعد المنظمة للمزادات العقارية والتعليمات الصادرة عن الجهات المختصة.
                </label>
            </div>
        </div>
    </div>

    {{-- وثائق ومرفقات إضافية --}}
    <div class="col-12 border-top pt-3">
        @php
            $existingAuctionAttachments = $isEdit
                ? $auction->attachments->where('category', 'auction_doc')->values()
                : collect();
        @endphp
        <x-form.controls.file name="attachments[]" id="attachments" label="ملفات ووثائق المزاد الإضافية" multiple
            :value="$existingAuctionAttachments->isNotEmpty() ? $existingAuctionAttachments : null"
            accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" help="الحد الأقصى لكل ملف: 10MB" col="col-12" />
    </div>
</div>
