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
        <button class="btn btn-outline-primary btn-sm" type="button" data-preview-design><i
                class="ti ti-eye me-1"></i>معاينة التصميم</button>
        <button class="btn btn-outline-primary btn-sm" type="button" data-open-template-samples><i
                class="ti ti-layout-grid me-1"></i>نماذج جاهزة</button>
    </div>
    <div class="d-flex flex-wrap gap-2 my-3 align-items-center">
        <label class="btn btn-outline-primary btn-sm">
            <i class="ti ti-brush me-1"></i>لون الخلفية
            <input class="form-control form-control-color d-none" data-background-color type="color" value="#ffffff">
        </label>
        <label class="btn btn-outline-primary btn-sm"><i class="ti ti-photo me-1"></i>صورة الخلفية
            <input type="file" class="d-none" accept="image/png,image/jpeg,image/webp" data-background-image>
        </label>
    </div>
    <div class="d-flex flex-wrap gap-2 my-3 align-items-center">
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
    <div class="template-designer__workspace" data-designer-workspace>
        <div class="template-designer__canvas template-canvas" data-canvas></div>
    </div>
</div>
