<div class="card assignment-properties-table-card h-100">
    <div class="card-body d-flex flex-column gap-3">
        <div class="d-flex align-items-center justify-content-between">
            <label class="form-label fw-semibold mb-0 fs-6">
                صورة العقار <span class="text-danger">*</span>
            </label>
            <span class="badge bg-label-primary">كروكي مساحي / جوي</span>
        </div>
        <input type="file" id="property-image-upload" class="d-none" accept="image/png,image/jpeg,image/webp">

        {{-- لوحة الرسم التفاعلية --}}
        <div class="flex-grow-1 position-relative d-flex flex-column">
            {{-- مربع رسالة الانتظار عند عدم وجود صورة (قابل للنقر لاختيار صورة أو إفلاتها) --}}
            <div id="property-image-empty-placeholder"
                class="property-image-dropzone rounded-3 p-4 text-center d-flex flex-column align-items-center justify-content-center flex-grow-1"
                role="button"
                tabindex="0"
                aria-label="اضغط لاختيار صورة العقار أو اسحب الصورة هنا"
                style="min-height: 340px;">
                <div class="property-image-upload-icon-box mb-3">
                    <i class="ti ti-photo-up text-primary"></i>
                </div>
                <h6 class="fw-bold mb-1 text-dark">اضغط هنا لاختيار صورة العقار</h6>
                <p class="text-muted small mb-3">أو اسحب ملف الصورة وأفلته داخل هذا المربع</p>
                <span class="btn btn-sm btn-label-primary px-3 py-1 mb-2 d-inline-flex align-items-center gap-1 shadow-none">
                    <i class="ti ti-upload fs-5"></i>
                    <span>استعراض من الجهاز</span>
                </span>
                <div class="text-muted d-flex flex-column align-items-center gap-1 small mt-2">
                    <span class="badge bg-label-secondary font-monospace">PNG, JPG, WebP</span>
                    <span class="text-xs text-muted">صورة جوية أو كروكي مساحي لتحديد أضلاع العقار عليها</span>
                </div>
            </div>

            {{-- حاوية الرسم التفاعلية مع الكانفاس --}}
            <div id="property-image-canvas-wrapper"
                class="position-relative border rounded-3 overflow-hidden bg-label-dark text-dark text-center d-none flex-grow-1 d-flex align-items-center justify-content-center"
                style="min-height: 340px;">
                <canvas id="property-drawing-canvas" class="img-fluid"
                    style="cursor: crosshair; max-height: 480px;"></canvas>
            </div>

            {{-- شريط حالة التحديد --}}
            <div id="property-image-status-bar" class="d-none justify-content-between align-items-center mt-2 px-1">
                <span id="points-count-badge" class="badge bg-info">0 نقاط محددة</span>
                <small id="polygon-status-text" class="text-muted">انقر على زوايا العقار لوضع نقاط الحدود، واسحب النقاط
                    لتعديلها</small>
            </div>
        </div>

        {{-- شريط أزرار التحكم بالرسم والتحميل --}}
        <div class="py-2 bg-white border-top d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 d-none"
                    id="btn-download-annotated" disabled title="تحميل الصورة المحددة إلى جهازك">
                    <i class="ti ti-download fs-5"></i>
                    <span>تحميل الصورة المحددة</span>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 d-none"
                    id="btn-change-image" title="اختيار صورة أخرى للعقار">
                    <i class="ti ti-photo-edit fs-5"></i>
                    <span>تغيير الصورة</span>
                </button>
            </div>
            <button type="button" class="btn btn-danger btn-sm d-flex align-items-center gap-1 d-none"
                id="btn-clear-boundary" title="مسح التحديد وحذف الشكل المضلع">
                <i class="ti ti-trash fs-5"></i>
                <span>مسح التحديد</span>
            </button>
        </div>

        {{-- حقول تخزين البيانات المخفية المرتبطة بالفورم ونظام المسودات --}}
        <div data-form-field data-field-name="aerial_image">
            <input type="hidden" name="aerial_image" id="property-aerial-image" data-form-control
                value="{{ old('aerial_image', isset($property) && $property->aerial_image ? (str_starts_with($property->aerial_image, 'data:image/') || str_starts_with($property->aerial_image, 'http') ? $property->aerial_image : tasset($property->aerial_image)) : '') }}">
            <div data-form-error class="invalid-feedback d-block text-danger mt-1" style="display: none;" hidden></div>
        </div>

        <div data-form-field data-field-name="boundary_geojson">
            <input type="hidden" name="boundary_geojson" id="property-boundary-geojson" data-form-control
                value="{{ old('boundary_geojson', isset($property) && $property->boundary_geojson ? (is_array($property->boundary_geojson) ? json_encode($property->boundary_geojson) : $property->boundary_geojson) : '') }}">
            <div data-form-error class="invalid-feedback d-block text-danger mt-1" style="display: none;" hidden></div>
        </div>
    </div>
</div>
