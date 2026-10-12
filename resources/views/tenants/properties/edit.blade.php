@extends('tenants.layouts.layoutMaster')

@section('title', 'تعديل العقار - ' . $property->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/leaflet/leaflet.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/leaflet/leaflet.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
    <style>
        .leaflet-map-container {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            border: 1px solid #e0e2e8;
        }

        .checklist-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            border-radius: 6px;
            background: #f8f9fa;
            margin-bottom: 8px;
        }
    </style>
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenant/properties/create.js'])
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'property-basic-validation', 'title' => 'البيانات الأساسية', 'subtitle' => 'بيانات وهوية العقار'],
            [
                'id' => 'property-location-validation',
                'title' => 'الموقع والحدود',
                'subtitle' => 'الموقع والخريطة والأطوال',
            ],
            [
                'id' => 'property-ownership-validation',
                'title' => 'الملكية والتفاصيل',
                'subtitle' => 'الصك والملاك والوحدات',
            ],
            [
                'id' => 'property-documents-validation',
                'title' => 'المستندات والصور',
                'subtitle' => 'المرفقات والصور الرسمية',
            ],
            [
                'id' => 'property-review-validation',
                'title' => 'المراجعة والحفظ',
                'subtitle' => 'التأكيد والاعتماد النهائي',
            ],
        ];
    @endphp

    <div class="row">
        <div class="col-12 mb-4">
            <x-form.wizard.stepper id="wizard-validation" class="mt-2" :steps="$steps">
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="property-create-form" action="{{ tenant_route_name('properties.update', $property->id) }}"
                    method="POST" enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="property_number" value="{{ $property->property_number }}">

                    {{-- الخطوة 1: البيانات الأساسية --}}
                    <x-form.wizard.step id="property-basic-validation" :fields="['name', 'type', 'usage_classification', 'area', 'is_active', 'short_description']">
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h5 class="card-title mb-1"><i class="ti ti-file-description me-2"></i>البيانات
                                            الأساسية</h5>
                                        <p class="text-muted small mb-0">تعديل البيانات الأساسية والوصفية للعقار.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <x-form.controls.input name="name" label="اسم العقار"
                                                placeholder="مثال: فيلا النخيل" :value="$property->name" :required="true"
                                                col="col-md-6" />

                                            <x-form.controls.select name="type" label="نوع العقار" :required="true"
                                                col="col-md-6" :value="$property->type?->value ?? $property->type" :enum="\App\Enums\Tenants\Properties\PropertyType::class" />

                                            <x-form.controls.select name="usage_classification" label="تصنيف الإستخدام"
                                                :required="true" col="col-md-6" :value="$property->usage_classification?->value ??
                                                    $property->usage_classification" :enum="\App\Enums\Tenants\Properties\PropertyUsageClassification::class" />

                                            <x-form.controls.input name="area" type="number" step="0.01"
                                                label="المساحة (م²)" :value="$property->area" placeholder="مثال: 600"
                                                :required="true" col="col-md-6" />

                                            <x-form.controls.select name="is_active" label="حالة العقار" :required="true"
                                                col="col-md-6" :value="$property->is_active ? '1' : '0'" :options="[
                                                    ['value' => '1', 'label' => 'نشط'],
                                                    ['value' => '0', 'label' => 'غير نشط'],
                                                ]" />

                                            <x-form.controls.textarea name="short_description" label="وصف مختصر للعقار"
                                                :value="$property->short_description" placeholder="أدخل وصفاً مختصراً للعقار..."
                                                :required="true" col="col-12" rows="3" maxlength="500" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="card border mb-3">
                                    <div class="card-body">
                                        <h6 class="mb-2"><i class="ti ti-info-circle me-1 text-primary"></i> بيانات العقار
                                            الحالية</h6>
                                        <p class="small text-muted mb-1"><strong>رقم العقار:</strong>
                                            {{ $property->property_number }}</p>
                                        <p class="small text-muted mb-1"><strong>حالة الإسناد:</strong>
                                            {{ $property->is_linked ? 'مرتبط بإسناد: ' . $property->assignment?->name : 'غير مرتبط بإسناد' }}
                                        </p>
                                        <p class="small text-muted mb-0"><strong>تاريخ الإضافة:</strong>
                                            {{ $property->created_at?->format('Y-m-d') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <x-form.wizard.actions>
                                    <x-form.wizard.previous-button :href="tenant_route_name('properties.index')" text="إلغاء والعودة" />
                                    <x-form.wizard.next-button />
                                </x-form.wizard.actions>
                            </div>
                        </div>
                    </x-form.wizard.step>

                    {{-- الخطوة 2: الموقع والحدود --}}
                    <x-form.wizard.step id="property-location-validation" :fields="[
                        'city',
                        'district',
                        'administrative_region',
                        'plan_number',
                        'parcel_number',
                        'google_maps_url',
                        'boundaries',
                    ]">
                        <input type="hidden" name="latitude" id="latitude"
                            value="{{ old('latitude', $property->latitude) }}">
                        <input type="hidden" name="longitude" id="longitude"
                            value="{{ old('longitude', $property->longitude) }}">

                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h5 class="card-title mb-1"><i class="ti ti-map-pin me-2"></i>بيانات الموقع الجغرافي
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <x-form.controls.select name="city" label="المدينة" :required="true"
                                                col="col-md-4" :value="$property->city" :options="$cityOptions" />

                                            <x-form.controls.input name="district" label="الحي" :value="$property->district"
                                                placeholder="مثال: حي العارض" :required="true" col="col-md-4" />

                                            <x-form.controls.input name="administrative_region" label="المنطقة الإدارية"
                                                :value="$property->administrative_region" placeholder="مثال: منطقة الرياض" :required="true"
                                                col="col-md-4" />

                                            <x-form.controls.input name="plan_number" label="رقم المخطط" :value="$property->plan_number"
                                                placeholder="مثال: 2357" :required="true" col="col-md-6" />

                                            <x-form.controls.input name="parcel_number" label="رقم القطعة"
                                                :value="$property->parcel_number" placeholder="مثال: 1288" :required="true"
                                                col="col-md-6" />

                                            <x-form.controls.input name="google_maps_url" label="رابط الموقع (Google Maps)"
                                                :value="$property->google_maps_url" placeholder="https://maps.google.com/?q=..."
                                                :required="true" col="col-12" icon="ti ti-link" />
                                        </div>
                                    </div>
                                </div>

                                {{-- حدود العقار وأطوالها --}}
                                <div class="card border">
                                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title mb-1"><i class="ti ti-ruler-2 me-2"></i>حدود العقار
                                                وأطوالها</h5>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-label-primary" id="btn-add-border">
                                            <i class="ti ti-plus me-1"></i> إضافة حد جديد
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive" data-form-field data-field-name="boundaries">
                                            <table class="table table-bordered table-sm align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 22%;">الجهة <span class="text-danger">*</span>
                                                        </th>
                                                        <th style="width: 25%;">نوع الحد <span
                                                                class="text-danger">*</span></th>
                                                        <th style="width: 28%;">وصف الحد <span
                                                                class="text-danger">*</span></th>
                                                        <th style="width: 17%;">الطول (م) <span
                                                                class="text-danger">*</span></th>
                                                        <th style="width: 8%;" class="text-center">حذف</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="boundaries-list">
                                                    @php
                                                        $existingBorders = $property->boundaries
                                                            ->map(
                                                                fn($b) => [
                                                                    'direction' =>
                                                                        $b->direction?->value ?? $b->direction,
                                                                    'border_type' => $b->border_type,
                                                                    'border_description' => $b->border_description,
                                                                    'length' => (string) $b->length,
                                                                ],
                                                            )
                                                            ->toArray();
                                                        $bordersData = old(
                                                            'boundaries',
                                                            $existingBorders ?: [
                                                                [
                                                                    'direction' => 'north',
                                                                    'border_type' => 'شارع',
                                                                    'border_description' => '',
                                                                    'length' => '',
                                                                ],
                                                            ],
                                                        );
                                                    @endphp
                                                    @foreach ($bordersData as $idx => $border)
                                                        <tr class="boundary-row">
                                                            <td>
                                                                <select name="boundaries[{{ $idx }}][direction]"
                                                                    class="form-select form-select-sm" data-form-control
                                                                    required>
                                                                    <option value="north"
                                                                        {{ ($border['direction'] ?? '') === 'north' ? 'selected' : '' }}>
                                                                        الشمال</option>
                                                                    <option value="south"
                                                                        {{ ($border['direction'] ?? '') === 'south' ? 'selected' : '' }}>
                                                                        الجنوب</option>
                                                                    <option value="east"
                                                                        {{ ($border['direction'] ?? '') === 'east' ? 'selected' : '' }}>
                                                                        الشرق</option>
                                                                    <option value="west"
                                                                        {{ ($border['direction'] ?? '') === 'west' ? 'selected' : '' }}>
                                                                        الغرب</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="text"
                                                                    name="boundaries[{{ $idx }}][border_type]"
                                                                    class="form-control form-control-sm"
                                                                    value="{{ $border['border_type'] ?? '' }}"
                                                                    placeholder="شارع / قطعة..." data-form-control
                                                                    required>
                                                            </td>
                                                            <td>
                                                                <input type="text"
                                                                    name="boundaries[{{ $idx }}][border_description]"
                                                                    class="form-control form-control-sm"
                                                                    value="{{ $border['border_description'] ?? '' }}"
                                                                    placeholder="عرض 20 متر..." data-form-control required>
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01"
                                                                    name="boundaries[{{ $idx }}][length]"
                                                                    class="form-control form-control-sm"
                                                                    value="{{ $border['length'] ?? '' }}"
                                                                    placeholder="0.00" data-form-control required>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button"
                                                                    class="btn btn-icon btn-sm btn-label-danger btn-remove-border"
                                                                    aria-label="حذف الحد">
                                                                    <i class="ti ti-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="card border sticky-top" style="top: 80px;">
                                    <div class="card-header pb-2">
                                        <h6 class="card-title mb-0"><i class="ti ti-map me-1 text-primary"></i> موقع
                                            العقار على الخريطة</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="map-warning-box" class="alert alert-warning py-2 px-3 small mb-2"
                                            style="{{ $property->google_maps_url ? 'display: none;' : '' }}">
                                            <i class="ti ti-info-circle me-1"></i> الصق رابط خرائط Google Maps أعلاه لعرض
                                            الموقع.
                                        </div>
                                        <div id="property-leaflet-map" class="leaflet-map-container"
                                            style="height: 300px; width: 100%;"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <x-form.wizard.actions>
                                    <x-form.wizard.previous-button />
                                    <x-form.wizard.next-button />
                                </x-form.wizard.actions>
                            </div>
                        </div>
                    </x-form.wizard.step>

                    {{-- الخطوة 3: الملكية والتفاصيل --}}
                    <x-form.wizard.step id="property-ownership-validation" :fields="['deed_number', 'deed_date', 'issuing_authority', 'ownership_type', 'owners']">
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h5 class="card-title mb-1"><i class="ti ti-certificate me-2"></i>بيانات الصك
                                            والملكية</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <x-form.controls.file name="assignment_letter_image"
                                                label="تحديث صورة الإسناد (اختياري)" accept="image/*" :required="false"
                                                col="col-md-6" />

                                            <x-form.controls.input name="deed_number" label="رقم الصك" :value="$property->deed_number"
                                                placeholder="أدخل رقم الصك" :required="true" col="col-md-6" />

                                            <x-form.controls.input name="deed_date" type="date" label="تاريخ الصك"
                                                :value="$property->deed_date?->format('Y-m-d')" :required="true" col="col-md-4" />

                                            <x-form.controls.input name="issuing_authority" label="جهة الإصدار"
                                                :value="$property->issuing_authority" placeholder="مثال: كتابة العدل" :required="true"
                                                col="col-md-4" />

                                            <x-form.controls.select name="ownership_type" label="نوع الملكية"
                                                :required="true" col="col-md-4" :value="$property->ownership_type?->value ??
                                                    $property->ownership_type" :enum="\App\Enums\Tenants\Properties\PropertyOwnershipType::class" />
                                        </div>
                                    </div>
                                </div>

                                {{-- ملاك العقار --}}
                                <div class="card border mb-3">
                                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title mb-1"><i class="ti ti-users me-2"></i>بيانات الملاك</h5>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-label-primary" id="btn-add-owner">
                                            <i class="ti ti-plus me-1"></i> إضافة مالك آخر
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div id="owners-list" data-form-field data-field-name="owners">
                                            @php
                                                $existingOwners = $property->owners
                                                    ->map(
                                                        fn($o) => [
                                                            'owner_name' => $o->owner_name,
                                                            'national_id_or_cr' => $o->national_id_or_cr,
                                                        ],
                                                    )
                                                    ->toArray();
                                                $ownersData = old(
                                                    'owners',
                                                    $existingOwners ?: [
                                                        ['owner_name' => '', 'national_id_or_cr' => ''],
                                                    ],
                                                );
                                            @endphp
                                            @foreach ($ownersData as $idx => $owner)
                                                <div class="owner-item card border p-3 mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <h6 class="mb-0 text-muted owner-number">المالك
                                                            #{{ $idx + 1 }}</h6>
                                                        @if ($idx > 0)
                                                            <button type="button"
                                                                class="btn btn-icon btn-sm btn-label-danger btn-remove-owner"
                                                                aria-label="حذف المالك">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">اسم المالك <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="text"
                                                                name="owners[{{ $idx }}][owner_name]"
                                                                class="form-control"
                                                                value="{{ $owner['owner_name'] ?? '' }}"
                                                                placeholder="أدخل اسم المالك" data-form-control required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">رقم الهوية / السجل التجاري <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="text"
                                                                name="owners[{{ $idx }}][national_id_or_cr]"
                                                                class="form-control"
                                                                value="{{ $owner['national_id_or_cr'] ?? '' }}"
                                                                placeholder="أدخل رقم الهوية أو السجل" data-form-control
                                                                required>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- بيانات الوكيل --}}
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h5 class="card-title mb-1"><i class="ti ti-user-check me-2"></i>بيانات الوكيل
                                            (اختياري)</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <x-form.controls.input name="agent_name" label="اسم الوكيل" :value="$property->agent_name"
                                                :required="false" col="col-md-6" />

                                            <x-form.controls.input name="agent_national_id" label="رقم الهوية"
                                                :value="$property->agent_national_id" :required="false" col="col-md-6" />

                                            <x-form.controls.input name="agency_number" label="رقم الوكالة"
                                                :value="$property->agency_number" :required="false" col="col-md-6" />

                                            <x-form.controls.input name="agency_date" type="date"
                                                label="تاريخ الوكالة" :value="$property->agency_date?->format('Y-m-d')" :required="false"
                                                col="col-md-6" />
                                        </div>
                                    </div>
                                </div>

                                {{-- وحدات العقار --}}
                                <div class="card border">
                                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title mb-1"><i class="ti ti-layout-grid me-2"></i>وحدات العقار
                                                (اختياري)</h5>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-label-primary" id="btn-add-unit">
                                            <i class="ti ti-plus me-1"></i> إضافة وحدة جديدة
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive" data-form-field data-field-name="units">
                                            <table class="table table-bordered table-sm align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width: 15%;">رقم الوحدة</th>
                                                        <th style="width: 20%;">نوع الوحدة</th>
                                                        <th style="width: 15%;">المساحة (م²)</th>
                                                        <th style="width: 20%;">الاستخدام الحالي</th>
                                                        <th style="width: 20%;">الحالة</th>
                                                        <th style="width: 10%;" class="text-center">حذف</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="units-list">
                                                    @php
                                                        $existingUnits = $property->units
                                                            ->map(
                                                                fn($u) => [
                                                                    'unit_number' => $u->unit_number,
                                                                    'unit_type' => $u->unit_type,
                                                                    'area' => (string) $u->area,
                                                                    'current_use' => $u->current_use,
                                                                    'status' => $u->status,
                                                                ],
                                                            )
                                                            ->toArray();
                                                        $unitsData = old('units', $existingUnits);
                                                    @endphp
                                                    @foreach ($unitsData as $idx => $unit)
                                                        <tr class="unit-row">
                                                            <td>
                                                                <input type="text"
                                                                    name="units[{{ $idx }}][unit_number]"
                                                                    class="form-control form-control-sm"
                                                                    value="{{ $unit['unit_number'] ?? '' }}"
                                                                    placeholder="رقم الوحدة" data-form-control>
                                                            </td>
                                                            <td>
                                                                <input type="text"
                                                                    name="units[{{ $idx }}][unit_type]"
                                                                    class="form-control form-control-sm"
                                                                    value="{{ $unit['unit_type'] ?? '' }}"
                                                                    placeholder="فيلا / شقة..." data-form-control>
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01"
                                                                    name="units[{{ $idx }}][area]"
                                                                    class="form-control form-control-sm"
                                                                    value="{{ $unit['area'] ?? '' }}"
                                                                    placeholder="المساحة" data-form-control>
                                                            </td>
                                                            <td>
                                                                <input type="text"
                                                                    name="units[{{ $idx }}][current_use]"
                                                                    class="form-control form-control-sm"
                                                                    value="{{ $unit['current_use'] ?? '' }}"
                                                                    placeholder="سكني / تجاري..." data-form-control>
                                                            </td>
                                                            <td>
                                                                <select name="units[{{ $idx }}][status]"
                                                                    class="form-select form-select-sm" data-form-control>
                                                                    <option value="available"
                                                                        {{ ($unit['status'] ?? '') === 'available' ? 'selected' : '' }}>
                                                                        متاح</option>
                                                                    <option value="rented"
                                                                        {{ ($unit['status'] ?? '') === 'rented' ? 'selected' : '' }}>
                                                                        مؤجر</option>
                                                                    <option value="sold"
                                                                        {{ ($unit['status'] ?? '') === 'sold' ? 'selected' : '' }}>
                                                                        مباع</option>
                                                                </select>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button"
                                                                    class="btn btn-icon btn-sm btn-label-danger btn-remove-unit"
                                                                    aria-label="حذف الوحدة">
                                                                    <i class="ti ti-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4"></div>

                            <div class="col-12">
                                <x-form.wizard.actions>
                                    <x-form.wizard.previous-button />
                                    <x-form.wizard.next-button />
                                </x-form.wizard.actions>
                            </div>
                        </div>
                    </x-form.wizard.step>

                    {{-- الخطوة 4: المستندات والصور --}}
                    <x-form.wizard.step id="property-documents-validation" :fields="[]">
                        <div class="row g-4">
                            <div class="col-lg-7">
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h5 class="card-title mb-1"><i class="ti ti-files me-2"></i>المستندات المطلوبة
                                        </h5>
                                        <p class="text-muted small mb-0">يمكنك رفع مستندات جديدة لاستبدال أو إضافة المرفقات
                                            الحالية.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <x-form.controls.file name="deed_document"
                                                label="صك الملكية (اختياري في التحديث)" accept=".pdf,image/*"
                                                help="اتركه فارغاً للإبقاء على الملف الحالي" :required="false"
                                                col="col-md-6" />

                                            <x-form.controls.file name="cadastral_plan"
                                                label="مخطط العقار (اختياري في التحديث)" accept=".pdf,image/*"
                                                help="اتركه فارغاً للإبقاء على الملف الحالي" :required="false"
                                                col="col-md-6" />

                                            <x-form.controls.file name="coordinates_statement"
                                                label="كشف الإحداثيات (اختياري في التحديث)" accept=".pdf,image/*"
                                                help="اتركه فارغاً للإبقاء على الملف الحالي" :required="false"
                                                col="col-md-6" />

                                            <x-form.controls.file name="building_permit" label="رخصة البناء (اختياري)"
                                                accept=".pdf,image/*" :required="false" col="col-md-6" />

                                            <x-form.controls.file name="additional_documents[]"
                                                label="مستندات إضافية جديدة" multiple accept=".pdf,image/*"
                                                :required="false" col="col-12" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h5 class="card-title mb-1"><i class="ti ti-photo me-2"></i>صور العقار</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <x-form.controls.image-upload name="main_image" label="تحديث الصورة الرئيسية"
                                                :value="$property->main_image ? tasset($property->main_image) : null" :required="false"
                                                accept="image/jpeg,image/png,image/webp" col="col-12" />

                                            <x-form.controls.file name="property_images[]" label="إضافة صور جديدة للمعرض"
                                                multiple accept="image/*" :required="false" col="col-12" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <x-form.wizard.actions>
                                    <x-form.wizard.previous-button />
                                    <x-form.wizard.next-button text="المراجعة والحفظ" />
                                </x-form.wizard.actions>
                            </div>
                        </div>
                    </x-form.wizard.step>

                    {{-- الخطوة 5: المراجعة والحفظ --}}
                    <x-form.wizard.step id="property-review-validation" :fields="[]">
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="card border mb-3">
                                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-primary"><i class="ti ti-file-text me-1"></i> ملخص البيانات
                                            الأساسية</h6>
                                        <button type="button" class="btn btn-sm btn-secondary" data-edit-step="1"><i
                                                class="ti ti-edit me-1"></i> تعديل</button>
                                    </div>
                                    <div class="card-body" id="review-basic"></div>
                                </div>

                                <div class="card border mb-3">
                                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-primary"><i class="ti ti-map-pin me-1"></i> ملخص الموقع
                                            والحدود</h6>
                                        <button type="button" class="btn btn-sm btn-secondary" data-edit-step="2"><i
                                                class="ti ti-edit me-1"></i> تعديل</button>
                                    </div>
                                    <div class="card-body" id="review-location"></div>
                                </div>

                                <div class="card border mb-3">
                                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-primary"><i class="ti ti-certificate me-1"></i> ملخص الملكية
                                            والتفاصيل</h6>
                                        <button type="button" class="btn btn-sm btn-secondary" data-edit-step="3"><i
                                                class="ti ti-edit me-1"></i> تعديل</button>
                                    </div>
                                    <div class="card-body" id="review-ownership"></div>
                                </div>

                                <div class="card border mb-3">
                                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-primary"><i class="ti ti-files me-1"></i> المستندات والصور
                                        </h6>
                                        <button type="button" class="btn btn-sm btn-secondary" data-edit-step="4"><i
                                                class="ti ti-edit me-1"></i> تعديل</button>
                                    </div>
                                    <div class="card-body" id="review-documents"></div>
                                </div>

                                <div class="card border">
                                    <div class="card-body">
                                        <x-form.controls.textarea name="notes" label="ملاحظات إضافية (اختياري)"
                                            :value="$property->notes" placeholder="أدخل أي ملاحظات إضافية..." :required="false"
                                            col="col-12" rows="3" />
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h6 class="card-title mb-0">الصورة الرئيسية للعقار</h6>
                                    </div>
                                    <div class="card-body text-center p-3">
                                        <img id="review-main-image-preview"
                                            src="{{ $property->main_image ? tasset($property->main_image) : asset('static/brand/logos/logo.svg') }}"
                                            alt="معاينة صورة العقار" class="img-fluid rounded mb-2"
                                            style="max-height: 200px; width: 100%; object-fit: cover;">
                                    </div>
                                </div>

                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h6 class="card-title mb-0"><i class="ti ti-checklist me-1 text-primary"></i>
                                            قائمة التحقق</h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="checklist-item"><span>البيانات الأساسية</span><span
                                                id="check-step-1"><span class="badge bg-label-success"><i
                                                        class="ti ti-check me-1"></i> مكتمل</span></span></div>
                                        <div class="checklist-item"><span>الموقع والحدود</span><span
                                                id="check-step-2"><span class="badge bg-label-success"><i
                                                        class="ti ti-check me-1"></i> مكتمل</span></span></div>
                                        <div class="checklist-item"><span>الملكية والملاك</span><span
                                                id="check-step-3"><span class="badge bg-label-success"><i
                                                        class="ti ti-check me-1"></i> مكتمل</span></span></div>
                                        <div class="checklist-item"><span>المستندات والصور</span><span
                                                id="check-step-4"><span class="badge bg-label-success"><i
                                                        class="ti ti-check me-1"></i> مكتمل</span></span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <x-form.wizard.actions>
                                    <x-form.wizard.previous-button />
                                    <x-form.wizard.save-button text="تحديث وحفظ العقار" />
                                </x-form.wizard.actions>
                            </div>
                        </div>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
