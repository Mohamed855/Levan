<div class="card assignment-properties-table-card h-100">
    <div class="card-body d-flex flex-column gap-3">
        <div>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label fw-medium mb-0">
                    صورة العقار <span class="text-danger">*</span>
                </label>
                <span class="text-muted small">بحد أقصى 10MB</span>
            </div>
            <input type="file" id="property-image-upload" class="d-none" accept="image/png,image/jpeg,image/webp"
                data-max-size-mb="10">
        </div>

        {{-- لوحة الرسم التفاعلية --}}
        <div class="flex-grow-1 position-relative d-flex flex-column">
            {{-- مربع رسالة الانتظار عند عدم وجود صورة (قابل للنقر لاختيار صورة) --}}
            <div id="property-image-empty-placeholder"
                class="border rounded-3 p-4 text-center text-primary bg-light d-flex flex-column align-items-center justify-content-center flex-grow-1"
                role="button" tabindex="0" aria-label="اضغط لاختيار صورة العقار" style="min-height: 320px;">
                <i class="ti ti-photo-up fs-1 text-primary mb-2"></i>
                <span class="fw-semibold">اضغط لاختيار صورة العقار</span>
                <small class="mt-1">ارفع صورة جوية أو كروكي مساحي لتحديد العقار عليها (بحد أقصى 10MB)</small>
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
        <div data-form-field data-field-name="boundary_geojson,aerial_image">
            <input type="hidden" name="aerial_image" id="property-aerial-image" data-form-control
                value="{{ old('aerial_image', isset($property) && $property->aerial_image ? (str_starts_with($property->aerial_image, 'data:image/') || str_starts_with($property->aerial_image, 'http') ? $property->aerial_image : tasset($property->aerial_image)) : '') }}">
            <input type="hidden" name="raw_aerial_image" id="property-raw-aerial-image" data-form-control
                value="{{ old('raw_aerial_image', '') }}">
            <input type="hidden" name="boundary_geojson" id="property-boundary-geojson" data-form-control
                value="{{ old('boundary_geojson', isset($property) && $property->boundary_geojson ? (is_array($property->boundary_geojson) ? json_encode($property->boundary_geojson) : $property->boundary_geojson) : '') }}">
            <div data-form-error class="invalid-feedback d-block text-danger mt-1" style="display: none;" hidden></div>
        </div>
    </div>
</div>
