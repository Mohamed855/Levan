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
        <div class="row g-3 flex-grow-1 d-none" data-background-position-controls>
            <label class="col-md-6 mb-0">
                <span class="d-flex justify-content-between small mb-1">
                    <span>موضع الخلفية أفقيًا</span>
                    <output data-background-position-value="x">50%</output>
                </span>
                <input type="range" class="form-range" min="0" max="100" step="1" value="50"
                    data-background-position="x">
            </label>
            <label class="col-md-6 mb-0">
                <span class="d-flex justify-content-between small mb-1">
                    <span>موضع الخلفية رأسيًا</span>
                    <output data-background-position-value="y">50%</output>
                </span>
                <input type="range" class="form-range" min="0" max="100" step="1" value="50"
                    data-background-position="y">
            </label>
        </div>
    </div>
    <div class="template-designer__canvas template-canvas" data-canvas></div>
</div>
