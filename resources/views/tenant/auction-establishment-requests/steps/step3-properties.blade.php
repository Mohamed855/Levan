@php
    $fallbackImage = asset('static/brand/logos/logo.svg');
    $totalProperties = count($data['properties'] ?? []);
    $totalArea = (float) $auction->properties->sum('area');
    $totalEstimatedValue = (float) $auction->properties->sum('estimated_value');
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="alert alert-info d-flex align-items-center mb-0">
            <i class="ti ti-info-circle ti-md me-2"></i>
            <div>
                تُؤخذ بيانات الملاك والصك والمساحة والاستخدام والموقع والوكالة المسجلة من ملف العقار عند الحفظ. انقر على أي عقار أو زر "تعديل البيانات" لفتح نافذة استكمال وتعديل تفاصيل العقار المطلوبة.
            </div>
        </div>
    </div>

    {{-- بطاقة جدول العقارات --}}
    <div class="col-12">
        <div class="card assignment-properties-table-card border shadow-none mb-0">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 bg-light-subtle">
                <div>
                    <h5 class="mb-0 fw-bold">عقارات المزاد المطلوب إقامته</h5>
                    <small class="text-muted">استكمل بيانات وتفاصيل جميع العقارات لتقديم طلب إقامة المزاد.</small>
                </div>
                <div class="d-flex flex-wrap gap-2 text-start">
                    <span class="badge bg-primary p-2">المكتملة: <span id="completed-establishment-props-count">0</span> / {{ $totalProperties }}</span>
                    <span class="badge bg-info p-2">إجمالي المساحة: {{ number_format($totalArea) }} م²</span>
                    <span class="badge bg-success p-2">إجمالي القيمة: {{ number_format($totalEstimatedValue) }} ر.س</span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="establishment-properties-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 3.5rem;" class="text-center" title="اكتمال البيانات المطلوبة">
                                    <i class="ti ti-checkbox fs-5" aria-hidden="true"></i>
                                </th>
                                <th style="width: 110px;">رقم الأصل</th>
                                <th style="width: 60px;" class="text-center">الصورة</th>
                                <th>اسم العقار</th>
                                <th>نوع العقار</th>
                                <th>المدينة / الحي</th>
                                <th>المساحة</th>
                                <th>القيمة التقديرية</th>
                                <th class="text-center" style="width: 150px;">حالة البيانات</th>
                                <th class="text-center" style="width: 120px;">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody id="establishment-properties-container">
                            @forelse ($data['properties'] as $index => $propertyData)
                                @php
                                    $property = $auction->properties->firstWhere('id', $propertyData['property_id']);
                                    $image = $property?->main_image_url ?? $property?->image_url ?? $fallbackImage;
                                @endphp
                                <tr class="establishment-property-row cursor-pointer"
                                    data-property-row="{{ $propertyData['property_id'] }}"
                                    data-property-index="{{ $index }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#property-details-modal-{{ $propertyData['property_id'] }}">
                                    <td class="text-center" onclick="event.stopPropagation();">
                                        <input type="checkbox"
                                            class="form-check-input property-complete-check"
                                            data-property-check="{{ $propertyData['property_id'] }}"
                                            data-property-index="{{ $index }}"
                                            disabled
                                            tabindex="-1"
                                            style="pointer-events: none; cursor: default;"
                                            aria-label="اكتمال بيانات {{ $property?->name ?? 'العقار' }}">
                                    </td>
                                    <td>
                                        <code class="copyable-code">{{ $property?->property_number ?? '-' }}</code>
                                    </td>
                                    <td class="text-center">
                                        <img src="{{ $image }}" class="rounded" style="width:36px;height:36px;object-fit:cover" alt=""
                                            onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-body">{{ $property?->name ?? 'عقار' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-info">{{ $property?->type_label ?? '-' }}</span>
                                    </td>
                                    <td>
                                        {{ $property?->city ?? '-' }}{{ $property?->district ? ' - ' . $property->district : '' }}
                                    </td>
                                    <td>{{ number_format((float) ($property?->area ?? 0)) }} م²</td>
                                    <td>{{ number_format((float) ($property?->estimated_value ?? 0)) }} ر.س</td>
                                    <td class="text-center">
                                        <span class="badge bg-label-warning property-status-badge"
                                            data-property-badge="{{ $propertyData['property_id'] }}">
                                            <i class="ti ti-alert-triangle me-1"></i>بيانات ناقصة
                                        </span>
                                    </td>
                                    <td class="text-center" onclick="event.stopPropagation();">
                                        <button type="button" class="btn btn-icon btn-label-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#property-details-modal-{{ $propertyData['property_id'] }}"
                                            title="تعديل"
                                            aria-label="تعديل">
                                            <i class="ti ti-edit" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4 text-muted">
                                        لا توجد عقارات مرتبطة بهذا المزاد.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- نوافذ استكمال تفاصيل العقارات (Modals) --}}
@foreach ($data['properties'] as $index => $propertyData)
    @php
        $property = $auction->properties->firstWhere('id', $propertyData['property_id']);
    @endphp
    <div class="modal fade property-establishment-modal"
        id="property-details-modal-{{ $propertyData['property_id'] }}"
        tabindex="-1"
        aria-labelledby="property-modal-label-{{ $propertyData['property_id'] }}"
        aria-hidden="true"
        data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-building fs-4 text-primary"></i>
                        <h5 class="modal-title fw-bold mb-0" id="property-modal-label-{{ $propertyData['property_id'] }}">
                            استكمال بيانات العقار: {{ $property?->name ?? 'عقار' }}
                        </h5>
                        <span class="badge bg-label-primary">{{ $property?->property_number ?? '-' }}</span>
                        <span class="badge bg-label-info">{{ $property?->type_label ?? '-' }}</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body" data-property-modal-body="{{ $propertyData['property_id'] }}">
                    <input type="hidden" name="data[properties][{{ $index }}][property_id]" value="{{ $propertyData['property_id'] }}">
                    <div class="alert alert-info py-2 px-3 mb-4 d-flex align-items-center">
                        <i class="ti ti-info-circle me-2"></i>
                        <small>يرجى استكمال جميع الحقول الإلزامية حتى يتم اعتماد العقار كمكتمل البيانات.</small>
                    </div>
                    <div class="row g-4">
                        @foreach ($schema->propertyFields() as $key => $field)
                            @include('tenant.auction-establishment-requests.field', [
                                'path' => 'properties.' . $index . '.' . $key,
                                'readonly' => false,
                            ])
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i>إلغاء
                    </button>
                    <button type="button" class="btn btn-primary btn-save-prop-details"
                        data-property-id="{{ $propertyData['property_id'] }}"
                        data-property-index="{{ $index }}">
                        <i class="ti ti-check me-1"></i>حفظ تفاصيل العقار
                    </button>
                </div>
            </div>
        </div>
    </div>
@endforeach
