<div class="card assignment-properties-table-card property-map-card h-100">
    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="card-title mb-1 text-primary fw-bold">
                <i class="ti ti-map-2 me-1"></i> موقع العقار على الخريطة
            </h6>
            <small class="text-muted">
                <i class="ti ti-hand-finger me-1"></i> حرك المؤشر أو زوايا الحدود لتعديل الشكل لحظياً.
            </small>
        </div>
    </div>
    <div class="card-body p-0 d-flex flex-column">
        {{-- منطقة الخريطة مع حقل البحث العائم --}}
        <div class="position-relative flex-grow-1" style="min-height: 420px;">
            {{-- شريط البحث العائم أعلى الخريطة --}}
            <div class="property-map-search-wrapper position-absolute" style="top: 14px; right: 14px; z-index: 1000; width: min(340px, calc(100% - 70px));">
                <div class="property-map-search-box shadow-sm bg-white rounded-3 d-flex align-items-center border px-2 py-1">
                    <input type="text"
                           id="property-map-search-input"
                           class="form-control form-control-sm border-0 shadow-none px-2"
                           placeholder="البحث عن موقع..."
                           autocomplete="off">
                    <span class="property-map-search-icon text-primary p-1" id="property-map-search-btn" role="button" title="بحث">
                        <i class="ti ti-map-pin fs-5"></i>
                    </span>
                    <span id="property-map-search-spinner" class="spinner-border spinner-border-sm text-primary d-none ms-1" role="status">
                        <span class="visually-hidden">جاري البحث...</span>
                    </span>
                </div>
                {{-- قائمة نتائج البحث التلقائي --}}
                <div id="property-map-search-results"
                     class="list-group shadow position-absolute w-100 mt-1 d-none"
                     style="z-index: 1050; max-height: 240px; overflow-y: auto;">
                </div>
            </div>

            {{-- خريطة Leaflet --}}
            <div id="property-leaflet-map"
                 class="leaflet-map-container h-100 w-100"
                 style="min-height: 420px;"
                 data-search-url="{{ tenant_route_name('properties.search-location') }}">
            </div>
        </div>

        {{-- شريط أزرار التحكم أسفل الخريطة مطابق للتصميم --}}
        <div class="property-map-toolbar p-2 px-3 bg-white border-top d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-1 px-3 py-2 fw-semibold" id="btn-draw-boundary" title="رسم حدود حرة أو تفعيل تعديل الشكل">
                    <i class="ti ti-pencil fs-5"></i>
                    <span>رسم الحدود على الخريطة</span>
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 px-3 py-2 fw-medium" id="btn-reset-boundary" title="تحديد أو إعادة تعيين الحدود بناءً على الأطوال والإحداثيات المدخلة">
                    <i class="ti ti-crosshair fs-5"></i>
                    <span>تحديد عبر الإحداثيات</span>
                </button>
            </div>
            <div>
                <button type="button" class="btn btn-label-danger btn-sm d-flex align-items-center gap-1 px-3 py-2 fw-medium text-danger" id="btn-clear-boundary" title="مسح التحديد وحذف الشكل الجغرافي">
                    <i class="ti ti-trash fs-5"></i>
                    <span>مسح التحديد</span>
                </button>
            </div>
        </div>

        {{-- حقل تخزين GeoJSON المرتبط بنظام التحقق والمسودات --}}
        <div data-form-field data-field-name="boundary_geojson" class="d-none">
            <input type="hidden"
                   name="boundary_geojson"
                   id="property-boundary-geojson"
                   data-form-control
                   value="{{ old('boundary_geojson', isset($property) && $property->boundary_geojson ? (is_array($property->boundary_geojson) ? json_encode($property->boundary_geojson) : $property->boundary_geojson) : '') }}">
            <div class="invalid-feedback d-block"></div>
        </div>
    </div>
</div>
