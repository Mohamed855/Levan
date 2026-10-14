<div class="card assignment-properties-table-card h-100">
    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="card-title mb-1 text-primary fw-bold">
                <i class="ti ti-photo-edit me-1"></i> تحديد العقار على الصورة
            </h6>
            <small class="text-muted">
                <i class="ti ti-hand-finger me-1"></i> ارفع صورة العقار (تصوير جوي / مخطط) وانقر عليها لتحديد مضلع الحدود.
            </small>
        </div>
    </div>
    <div class="card-body d-flex flex-column gap-3">
        {{-- منطقة رفع صورة العقار --}}
        <div>
            <label class="form-label fw-medium mb-1">
                صورة العقار للتحديد <span class="text-danger">*</span>
            </label>
            <div class="input-group input-group-merge">
                <span class="input-group-text"><i class="ti ti-upload"></i></span>
                <input type="file" id="property-image-upload" class="form-control" accept="image/png,image/jpeg,image/webp">
            </div>
            <small class="text-muted">اختر صورة بصيغة PNG أو JPG أو WebP للبدء في تحديد حدود العقار عليها.</small>
        </div>

        {{-- لوحة الرسم التفاعلية --}}
        <div class="flex-grow-1 position-relative d-flex flex-column">
            {{-- مربع رسالة الانتظار عند عدم وجود صورة --}}
            <div id="property-image-empty-placeholder"
                class="border rounded-3 p-4 text-center text-muted bg-light d-flex flex-column align-items-center justify-content-center flex-grow-1"
                style="min-height: 320px;">
                <i class="ti ti-photo-scan fs-1 text-primary mb-2"></i>
                <span class="fw-semibold">لم يتم رفع صورة للعقار بعد</span>
                <small class="text-muted mt-1">ارفع صورة جوية أو كروكي مساحي لتحديد العقار عليها</small>
            </div>

            {{-- حاوية الرسم التفاعلية مع الكانفاس --}}
            <div id="property-image-canvas-wrapper"
                class="position-relative border rounded-3 overflow-hidden bg-dark text-center d-none flex-grow-1 d-flex align-items-center justify-content-center"
                style="min-height: 340px;">
                <canvas id="property-drawing-canvas" class="img-fluid" style="cursor: crosshair; max-height: 480px;"></canvas>
            </div>

            {{-- شريط حالة التحديد --}}
            <div id="property-image-status-bar" class="d-none justify-content-between align-items-center mt-2 px-1">
                <span id="points-count-badge" class="badge bg-label-info">0 نقاط محددة</span>
                <small id="polygon-status-text" class="text-muted">انقر على زوايا العقار لوضع نقاط الحدود، واسحب النقاط لتعديلها</small>
            </div>
        </div>

        {{-- شريط أزرار التحكم بالرسم والتحميل --}}
        <div class="py-2 bg-white border-top d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1"
                    id="btn-download-annotated" disabled title="تحميل الصورة المحددة إلى جهازك">
                    <i class="ti ti-download fs-5"></i>
                    <span>تحميل الصورة المحددة</span>
                </button>
            </div>
            <button type="button" class="btn btn-label-danger btn-sm d-flex align-items-center gap-1"
                id="btn-clear-boundary" title="مسح التحديد وحذف الشكل المضلع">
                <i class="ti ti-trash fs-5"></i>
                <span>مسح التحديد</span>
            </button>
        </div>

        {{-- حقول تخزين البيانات المخفية المرتبطة بالفورم ونظام المسودات --}}
        <div data-form-field data-field-name="aerial_image" class="d-none">
            <input type="hidden" name="aerial_image" id="property-aerial-image" data-form-control
                value="{{ old('aerial_image', isset($property) && $property->aerial_image ? (str_starts_with($property->aerial_image, 'data:image/') || str_starts_with($property->aerial_image, 'http') ? $property->aerial_image : tasset($property->aerial_image)) : '') }}">
            <div class="invalid-feedback d-block"></div>
        </div>

        <div data-form-field data-field-name="boundary_geojson" class="d-none">
            <input type="hidden" name="boundary_geojson" id="property-boundary-geojson" data-form-control
                value="{{ old('boundary_geojson', isset($property) && $property->boundary_geojson ? (is_array($property->boundary_geojson) ? json_encode($property->boundary_geojson) : $property->boundary_geojson) : '') }}">
            <div class="invalid-feedback d-block"></div>
        </div>
    </div>
</div>
