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
                                        <code class="copyable-code">{{ $property?->property_number ?? '-' }}</code>
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
            $hasInheritedContract = filled($propModel?->assignment?->assignment_number ?? $auction->assignment?->assignment_number);
            $hasInheritedAgent = filled($propModel?->agent_name);
            $hasInheritedEstimated = filled($propModel?->estimated_value);
            $prefix = "data[properties][{$index}]";
        @endphp
        <div class="property-hidden-data" data-prop-hidden="{{ $propId }}"
            data-prop-index="{{ $index }}"
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

{{-- نافذة منبثقة واحدة موحدة لاستكمال بيانات ومرفقات العقار عبر JavaScript (نفس أسلوب مودال الإقفال) --}}
<x-bootstrap.modal id="establishmentPropertyModal" size="xl" scrollable="true" :staticBackdrop="true">
    <x-slot:header>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <i class="ti ti-building fs-4 text-primary"></i>
            <h5 class="modal-title fw-bold mb-0" id="establishmentPropertyModalTitle">
                استكمال بيانات العقار
            </h5>
            <code class="copyable-code" id="establishmentPropertyModalNumber">-</code>
            <span class="badge bg-label-info" id="establishmentPropertyModalType">-</span>
        </div>
    </x-slot:header>

    <div id="establishmentPropertyModalBody">
        <input type="hidden" id="modal_prop_id">
        <input type="hidden" id="modal_prop_index">

        <div class="alert alert-info py-2 px-3 mb-4 d-flex align-items-center">
            <i class="ti ti-info-circle me-2"></i>
            <small>البيانات المستوردة من سجل العقار والمزاد والإسناد (الملاك، الصك، المساحة، النوع، الاستخدام، المدينة) مقفلة تلقائياً وغير قابلة للتعديل للحفاظ على مطابقة السجلات.</small>
        </div>

        <div class="row g-4">
            {{-- المالك / الملاك --}}
            <x-form.controls.input name="modal_prop_owner_name" id="modal_prop_owner_name" label="المالك / الملاك"
                required col="col-md-6" :readonly="true" class="bg-light cursor-not-allowed" tabindex="-1"
                style="pointer-events: none;" help="مستورد من سجل ملاك العقار" />

            {{-- رقم الصك --}}
            <x-form.controls.input name="modal_prop_deed_number" id="modal_prop_deed_number"
                label="رقم الصك / الصحيفة العقارية" required inputmode="numeric" pattern="[0-9]+" col="col-md-6"
                :readonly="true" class="bg-light cursor-not-allowed" tabindex="-1" style="pointer-events: none;"
                help="مستورد من وثيقة ملكية العقار" />

            {{-- رقم عقد المزاد --}}
            <x-form.controls.input name="modal_prop_contract_number" id="modal_prop_contract_number"
                label="رقم عقد المزاد / الإسناد" required inputmode="numeric" pattern="[0-9]+" col="col-md-6"
                help="رقم الإسناد أو عقد الوساطة للمزاد" />

            {{-- المساحة --}}
            <x-form.controls.input name="modal_prop_area" id="modal_prop_area" label="المساحة (م²)" type="number"
                min="0.01" step="any" required col="col-md-6" :readonly="true" class="bg-light cursor-not-allowed"
                tabindex="-1" style="pointer-events: none;" help="مستورد من مساحة العقار" />

            {{-- نوع العقار --}}
            <x-form.controls.input name="modal_prop_type" id="modal_prop_type" label="نوع العقار" required
                col="col-md-6" :readonly="true" class="bg-light cursor-not-allowed" tabindex="-1"
                style="pointer-events: none;" help="مستورد من تصنيف العقار" />

            {{-- استخدام العقار --}}
            <x-form.controls.input name="modal_prop_usage" id="modal_prop_usage" label="استخدام العقار" required
                col="col-md-6" :readonly="true" class="bg-light cursor-not-allowed" tabindex="-1"
                style="pointer-events: none;" help="مستورد من تصنيف استخدام العقار" />

            {{-- الاستخدام الآخر (إن وجد) --}}
            <div class="col-md-6 d-none" id="modal_prop_usage_other_col">
                <x-form.controls.input name="modal_prop_usage_other" id="modal_prop_usage_other"
                    label="الاستخدام الآخر" col="col-12" />
            </div>

            {{-- عمر العقار --}}
            <x-form.controls.input name="modal_prop_age" id="modal_prop_age" label="عمر العقار بالسنوات (صفر للأرض)"
                type="number" min="0" max="200" step="1" required col="col-md-6" />

            {{-- المدينة / الحي --}}
            <x-form.controls.input name="modal_prop_city_district" id="modal_prop_city_district"
                label="المدينة / الحي" required col="col-md-6" :readonly="true" class="bg-light cursor-not-allowed"
                tabindex="-1" style="pointer-events: none;" help="مستورد من موقع العقار" />

            {{-- الحالة النظامية --}}
            <x-form.controls.select name="modal_prop_status" id="modal_prop_status" label="الحالة النظامية"
                :options="[
                    'clear' => 'سليم',
                    'seized' => 'محجوز',
                    'mortgaged' => 'مرهون',
                    'endowed' => 'وقف',
                    'other' => 'أخرى',
                ]" required col="col-md-6" />

            {{-- تفاصيل الحالة الأخرى --}}
            <div class="col-md-6 d-none" id="modal_prop_status_other_col">
                <x-form.controls.input name="modal_prop_status_other" id="modal_prop_status_other"
                    label="تفاصيل الحالة الأخرى" col="col-12" />
            </div>

            {{-- نسبة الملكية المعروضة --}}
            <x-form.controls.input name="modal_prop_ownership_share" id="modal_prop_ownership_share"
                label="نسبة الملكية المعروضة %" type="number" min="0.01" max="100" step="any"
                required col="col-md-6" />

            {{-- اسم الوكيل --}}
            <x-form.controls.input name="modal_prop_agent_name" id="modal_prop_agent_name"
                label="اسم الوكيل (إن وجد)" col="col-md-6" />

            {{-- رقم الوكالة --}}
            <x-form.controls.input name="modal_prop_agency_number" id="modal_prop_agency_number"
                label="رقم الوكالة (إن وجد)" inputmode="numeric" pattern="[0-9]+" col="col-md-6" />

            {{-- قيمة افتتاح المزاد --}}
            <x-form.controls.input name="modal_prop_opening_value" id="modal_prop_opening_value"
                label="قيمة افتتاح المزاد (ر.س)" type="number" min="0" step="0.01" :price="true"
                required col="col-md-4" />

            {{-- مبلغ الدخول --}}
            <x-form.controls.input name="modal_prop_entry_amount" id="modal_prop_entry_amount"
                label="مبلغ الدخول (ر.س)" type="number" min="0" step="0.01" :price="true" required
                col="col-md-4" />

            {{-- القيمة التقديرية --}}
            <x-form.controls.input name="modal_prop_estimated_value" id="modal_prop_estimated_value"
                label="القيمة التقديرية (ر.س)" type="number" min="0" step="0.01" :price="true"
                required col="col-md-4" />

            {{-- الإفصاح الخاص بالعقار --}}
            <x-form.controls.textarea name="modal_prop_disclosure" id="modal_prop_disclosure"
                label="الإفصاح الخاص بالعقار" required col="col-12" rows="2" />

            <div class="col-md-4">
                <x-form.controls.file name="modal_file_deed" id="modal_file_deed"
                    label="وثيقة الملكية (الصك الإلكتروني)" accept=".pdf,.png,.jpg,.jpeg" col="col-12" />
            </div>

            <div class="col-md-4">
                <x-form.controls.file name="modal_file_valuation" id="modal_file_valuation"
                    label="نموذج التقييم العقاري" accept=".pdf,.png,.jpg,.jpeg" col="col-12"
                    help="مطلوب عند وجود مبلغ دخول." />
            </div>

            <div class="col-md-4">
                <x-form.controls.file name="modal_file_representative_authority"
                    id="modal_file_representative_authority" label="مستند التفويض أو الوكالة"
                    accept=".pdf,.png,.jpg,.jpeg" col="col-12" help="مطلوب عند وجود وكيل أو معتمد غير المالك." />
            </div>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>إلغاء
        </button>
        <button type="button" class="btn btn-primary" id="btn-save-establishment-prop-modal">
            <i class="ti ti-check me-1"></i>حفظ تفاصيل العقار
        </button>
    </x-slot:footer>
</x-bootstrap.modal>
