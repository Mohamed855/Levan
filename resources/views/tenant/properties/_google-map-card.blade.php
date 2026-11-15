<div class="card assignment-properties-table-card h-100">
    <div class="card-body d-flex flex-column gap-3">
        <div>
            <label class="form-label fw-medium" for="property-location-search">موقع العقار على الخريطة</label>
            {{-- Relative wrapper so the results dropdown overlays the map --}}
            <div class="position-relative">
                <div class="input-group">
                    <input type="search" id="property-location-search" class="form-control"
                        placeholder="ابحث باسم المكان أو الصق رابط خرائط جوجل" aria-describedby="property-location-help"
                        autocomplete="off">
                    <button type="button" id="property-location-search-button" class="input-group-text cursor-pointer">
                        <i class="ti ti-search me-1" aria-hidden="true"></i>بحث
                    </button>
                </div>
                {{-- Dropdown results overlay —  positioned beneath the search bar --}}
                <div id="property-location-results"
                    class="list-group shadow position-absolute w-100 mt-1 bg-white rounded-2 border"
                    style="z-index: 1050; max-height: 240px; overflow-y: auto;"
                    aria-label="نتائج البحث عن الموقع"></div>
            </div>
            <small id="property-location-help" class="text-muted d-block mt-2">اضغط على الخريطة أو اسحب العلامة لتحديد موقع العقار بدقة.</small>
        </div>
        <div id="property-location-map" class="border rounded flex-grow-1" style="min-height: 400px;"
            data-api-key="{{ config('services.google_maps.api_key') }}"
            data-map-id="{{ config('services.google_maps.map_id') }}"
            data-resolve-url="{{ tenant_route_name('properties.resolve-maps-url') }}"
            role="region" aria-label="خريطة تحديد موقع العقار"></div>
        <iframe id="property-location-fallback" class="border rounded w-100 flex-grow-1" style="min-height: 400px;"
            title="خريطة الموقع للعرض فقط" referrerpolicy="strict-origin-when-cross-origin" hidden></iframe>
        <p id="property-location-status" class="small text-muted mb-0" role="status" aria-live="polite">جارٍ تحميل الخريطة...</p>
        <button type="button" id="property-location-retry" class="btn btn-outline-primary btn-sm align-self-start" hidden>إعادة تحميل الخريطة</button>
        <div data-form-field data-field-name="latitude,longitude,google_maps_url">
            <div data-form-error class="invalid-feedback d-block" hidden></div>
        </div>
    </div>
</div>
