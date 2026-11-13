@php
    $fallbackImage = asset('static/brand/logos/logo.svg');
    $totalProperties = count($data['properties'] ?? []);
    $totalArea = (float) $auction->properties->sum('area');
    $totalEstimatedValue = (float) $auction->properties->sum('estimated_value');

    $existingPropertyAttachments = [];
    foreach ($auction->properties as $prop) {
        $existingPropertyAttachments[$prop->id] = [
            'deed' => isset($data['attachments']['deed'][$prop->id])
                ? [
                    'name' => $data['attachments']['deed'][$prop->id]['name'] ?? 'وثيقة الملكية',
                    'url' => tenant_route_name('auction-establishment-requests.attachment', [
                        'auction' => $auction,
                        'type' => 'deed',
                        'property' => $prop->id,
                    ]),
                ]
                : null,
            'valuation' => isset($data['attachments']['valuation'][$prop->id])
                ? [
                    'name' => $data['attachments']['valuation'][$prop->id]['name'] ?? 'نموذج التقييم',
                    'url' => tenant_route_name('auction-establishment-requests.attachment', [
                        'auction' => $auction,
                        'type' => 'valuation',
                        'property' => $prop->id,
                    ]),
                ]
                : null,
            'representative_authority' => isset($data['attachments']['representative_authority'][$prop->id])
                ? [
                    'name' =>
                        $data['attachments']['representative_authority'][$prop->id]['name'] ??
                        'مستند التفويض أو الوكالة',
                    'url' => tenant_route_name('auction-establishment-requests.attachment', [
                        'auction' => $auction,
                        'type' => 'representative_authority',
                        'property' => $prop->id,
                    ]),
                ]
                : null,
        ];
    }
@endphp

<div class="row g-4">
    <div class="col-12">
        <div class="alert alert-info d-flex align-items-center mb-0">
            <i class="ti ti-info-circle ti-md me-2"></i>
            <div>
                تُؤخذ بيانات الملاك والصك والمساحة والاستخدام والموقع والوكالة المسجلة من ملف العقار عند الحفظ. انقر على
                أي عقار أو زر "تعديل البيانات" لفتح نافذة استكمال وتعديل تفاصيل العقار ومرفقاته.
            </div>
        </div>
    </div>

    {{-- بطاقة جدول العقارات --}}
    <div class="col-12">
        <div class="card assignment-properties-table-card border shadow-none mb-0">
            <div
                class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3 bg-light-subtle">
                <div>
                    <h5 class="mb-0 fw-bold"><i class="ti ti-building me-1 text-primary"></i>عقارات المزاد المطلوب إقامته
                    </h5>
                </div>
                <div class="d-flex flex-wrap gap-2 text-start">
                    <span class="badge bg-primary p-2">المكتملة: <span id="completed-establishment-props-count">0</span>
                        / {{ $totalProperties }}</span>
                    <span class="badge bg-info p-2">إجمالي المساحة: {{ number_format($totalArea) }} م²</span>
                    <span class="badge bg-success p-2">إجمالي القيمة: {{ number_format($totalEstimatedValue) }}
                        ر.س</span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="establishment-properties-table"
                        data-existing-attachments="{{ json_encode($existingPropertyAttachments) }}">
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
                                    $image = $property?->main_image_url ?? ($property?->image_url ?? $fallbackImage);
                                @endphp
                                <tr class="establishment-property-row cursor-pointer"
                                    data-property-row="{{ $propertyData['property_id'] }}"
                                    data-property-id="{{ $propertyData['property_id'] }}"
                                    data-property-index="{{ $index }}"
                                    data-property-name="{{ $property?->name ?? 'عقار' }}"
                                    data-property-number="{{ $property?->property_number ?? '-' }}"
                                    data-property-type="{{ $property?->type_label ?? '-' }}">
                                    <td class="text-center" onclick="event.stopPropagation();">
                                        <input type="checkbox" class="form-check-input property-complete-check"
                                             data-property-check="{{ $propertyData['property_id'] }}"
                                            data-property-index="{{ $index }}" disabled tabindex="-1"
                                            style="pointer-events: none; cursor: default;"
                                            aria-label="اكتمال بيانات {{ $property?->name ?? 'العقار' }}">
                                    </td>
                                    <td>
                                        <code>{{ $property?->property_number ?? '-' }}</code>
                                    </td>
                                    <td class="text-center">
                                        <img src="{{ $image }}" class="rounded"
                                            style="width:36px;height:36px;object-fit:cover" alt=""
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
                                        <button type="button"
                                            class="btn btn-icon btn-label-primary edit-establishment-prop-btn"
                                            data-property-id="{{ $propertyData['property_id'] }}"
                                            data-property-index="{{ $index }}"
                                            data-property-name="{{ $property?->name ?? 'عقار' }}"
                                            data-property-number="{{ $property?->property_number ?? '-' }}"
                                            data-property-type="{{ $property?->type_label ?? '-' }}" title="تعديل"
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

{{-- حاوية الحقول المخفية لحفظ بيانات العقارات المكتملة والمرفقات --}}
<div id="establishment-properties-data-container" class="d-none">
    @foreach ($data['properties'] as $index => $propertyData)
        @php
            $propId = $propertyData['property_id'];
            $propModel = $auction->properties->firstWhere('id', $propId);
            $hasInheritedContract = filled(
                $propModel?->assignment?->assignment_number ?? $auction->assignment?->assignment_number,
            );
            $hasInheritedAgent = filled($propModel?->agent_name);
            $hasInheritedEstimated = filled($propModel?->estimated_value);
            $prefix = "data[properties][{$index}]";
        @endphp
        <div class="property-hidden-data" data-prop-hidden="{{ $propId }}" data-prop-index="{{ $index }}"
            data-prop-inherited-contract="{{ $hasInheritedContract ? '1' : '0' }}"
            data-prop-inherited-agent="{{ $hasInheritedAgent ? '1' : '0' }}"
            data-prop-inherited-estimated="{{ $hasInheritedEstimated ? '1' : '0' }}">
            <input type="hidden" name="{{ $prefix }}[property_id]" value="{{ $propId }}"
                data-field="property_id">
            @foreach ($schema->propertyFields() as $fieldKey => $fieldDef)
                <input type="hidden" name="{{ $prefix }}[{{ $fieldKey }}]"
                    value="{{ old("data.properties.{$index}.{$fieldKey}", $propertyData[$fieldKey] ?? '') }}"
                    data-field="{{ $fieldKey }}">
            @endforeach
            <div class="property-file-inputs" data-prop-files="{{ $propId }}"></div>
        </div>
    @endforeach
</div>
