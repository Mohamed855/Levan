@php
    $isEdit = isset($auction) && $auction->exists;
    $initialCount = $isEdit ? $auction->properties->count() : 0;
@endphp

<div data-form-field data-field-name="property_ids">
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">العقارات المرتبطة بالإسناد المختار</h5>
            <small class="text-muted">حدد العقارات التي سيتم إدراجها رسمياً في هذا المزاد.</small>
        </div>
        <div class="d-flex gap-3 text-start">
            <div class="badge bg-primary fs-6 p-2">المحددة: <span
                    id="selected-props-count">{{ $initialCount }}</span></div>
            <div class="badge bg-info fs-6 p-2">إجمالي المساحة: <span
                    id="selected-props-area">0 م²</span></div>
            <div class="badge bg-success fs-6 p-2">إجمالي القيمة: <span
                    id="selected-props-val">0 ر.س</span></div>
        </div>
    </div>

    <div class="table-responsive border rounded mb-4">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 2.5rem;">
                        <input type="checkbox" id="select-all-properties"
                            class="form-check-input">
                    </th>
                    <th>رقم العقار</th>
                    <th>اسم العقار</th>
                    <th>المدينة / الحي</th>
                    <th>المساحة</th>
                    <th>القيمة التقديرية</th>
                </tr>
            </thead>
            <tbody id="properties-container">
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        {{ $isEdit ? 'جاري تحميل عقارات الإسناد...' : 'يرجى اختيار الإسناد في الخطوة السابقة لعرض عقاراته التابعة' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div id="property_ids-error" class="invalid-feedback d-block" data-form-error role="alert" hidden></div>
</div>

<section class="assignment-selection mt-4" aria-live="polite">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h5 class="mb-0">العقارات المحددة</h5>
        <span class="badge bg-primary"><span id="selected-properties-count">{{ $initialCount }}</span> عقار</span>
    </div>
    <div id="selected-properties-cards" class="assignment-selection__items">
        <p class="text-muted mb-0" data-empty {{ $initialCount > 0 ? 'hidden' : '' }}>لم يتم اختيار عقارات بعد.</p>
    </div>
</section>
