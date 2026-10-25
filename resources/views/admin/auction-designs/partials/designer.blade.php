<div class="auction-design-surface" data-designer-surface>
    <div class="d-flex flex-wrap gap-2 my-3 align-items-center">
        <button class="btn btn-outline-primary btn-sm" type="button" data-add-page><i class="ti ti-plus me-1"></i>إضافة
            صفحة</button>
        <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="text"><i
                class="ti ti-typography me-1"></i>إضافة نص</button>
        <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="variable"><i
                class="ti ti-variable me-1"></i>إضافة متغير</button>
        <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="image"><i
                class="ti ti-photo me-1"></i>إضافة صورة</button>
        <label class="d-flex align-items-center gap-2 ms-auto mb-0">لون الخلفية
            <input class="form-control form-control-color" data-background-color type="color" value="#ffffff">
        </label>
    </div>
    <div class="d-flex flex-wrap gap-2 my-3 align-items-center">
        <label class="btn btn-outline-primary btn-sm mb-0">خلفية الصفحة
            <input type="file" class="d-none" accept="image/png,image/jpeg,image/webp" data-background-image>
        </label>
        <label class="d-flex align-items-center gap-2 mb-0">
            <select class="form-select form-select-sm" data-background-fit>
                <option disabled selected>ملاءمة الصورة</option>
                <option value="cover">تغطية</option>
                <option value="contain">احتواء</option>
                <option value="fill">تمديد</option>
            </select>
        </label>
    </div>
    <div class="template-designer__canvas template-canvas" data-canvas></div>
</div>
