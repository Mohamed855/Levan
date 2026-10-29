@php
    $is_edit = $is_edit ?? false;
    $property = $property ?? null;
    $steps = [
        ['id' => 'property-basic-validation', 'title' => 'البيانات الأساسية'],
        ['id' => 'property-location-validation', 'title' => 'الموقع والحدود'],
        ['id' => 'property-ownership-validation', 'title' => 'الملكية والتفاصيل'],
        ['id' => 'property-files-validation', 'title' => 'المستندات والصور'],
        ['id' => 'property-review-validation', 'title' => 'المراجعة والحفظ'],
    ];
@endphp

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

<form id="property-{{ $is_edit ? 'edit' : 'create' }}-form"
    action="{{ $is_edit ? tenant_route_name('properties.update', $property->id) : tenant_route_name('properties.store') }}"
    method="POST"
    enctype="multipart/form-data"
    autocomplete="off"
    data-form-validate
    @if (!$is_edit)
        data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
        data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
        data-draft-file-url="{{ tenant_route_name('wizard-drafts.file.destroy') }}"
        data-draft-resource-key="tenant.property-create"
    @endif>
    @csrf
    @if ($is_edit)
        @method('PUT')
    @endif

    <input type="hidden" name="property_number" value="{{ $is_edit ? $property->property_number : $generatedPropertyNumber }}">

    {{-- الخطوة 1: البيانات الأساسية --}}
    <x-form.wizard.step id="property-basic-validation" :fields="['name', 'type', 'usage_classification', 'area', 'short_description']">
        <div class="row g-4">
            <x-form.controls.input name="name" label="اسم العقار" placeholder="مثال: فيلا النخيل" required
                :value="$is_edit ? $property->name : old('name')"
                col="col-md-6" />
            <x-form.controls.select name="type" label="نوع العقار" required col="col-md-6"
                :value="$is_edit ? ($property->type instanceof \BackedEnum ? $property->type->value : $property->type) : old('type')"
                :enum="\App\Enums\Tenants\Properties\PropertyType::class" />
            <x-form.controls.select name="usage_classification" label="تصنيف الاستخدام" required col="col-md-6"
                :value="$is_edit ? ($property->usage_classification instanceof \BackedEnum ? $property->usage_classification->value : $property->usage_classification) : old('usage_classification')"
                :enum="\App\Enums\Tenants\Properties\PropertyUsageClassification::class" />
            <x-form.controls.input name="area" type="number" step="0.01" label="المساحة (م²)"
                :value="$is_edit ? $property->area : old('area')"
                placeholder="مثال: 600" required col="col-md-6" />
            <x-form.controls.textarea name="short_description" label="وصف العقار" placeholder="أدخل وصفاً للعقار..."
                :value="$is_edit ? $property->short_description : old('short_description')"
                required col="col-12" rows="4" maxlength="500" />

            <x-form.wizard.actions>
                <x-form.wizard.previous-button :href="tenant_route_name('properties.index')" />
                <div class="d-flex gap-2">
                    @if (!$is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button />
                </div>
            </x-form.wizard.actions>
        </div>
    </x-form.wizard.step>

    {{-- الخطوة 2: الموقع والحدود --}}
    <x-form.wizard.step id="property-location-validation" :fields="[
        'city',
        'district',
        'administrative_region',
        'plan_number',
        'parcel_number',
        'aerial_image',
        'boundary_geojson',
        'boundaries',
    ]">
        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $is_edit ? $property->latitude : '') }}">
        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $is_edit ? $property->longitude : '') }}">
        @php
            $defaultBorders = [
                [
                    'direction' => 'north',
                    'border_type' => 'street',
                    'border_description' => 'عرض 20 متر',
                    'length' => 0,
                ],
                [
                    'direction' => 'south',
                    'border_type' => 'parcel',
                    'border_description' => 'عرض 20 متر',
                    'length' => 0,
                ],
                [
                    'direction' => 'east',
                    'border_type' => 'parcel',
                    'border_description' => 'عرض 20 متر',
                    'length' => 0,
                ],
                [
                    'direction' => 'west',
                    'border_type' => 'street',
                    'border_description' => 'عرض 20 متر',
                    'length' => 0,
                ],
            ];
            $bordersData = old('boundaries', $is_edit && isset($property) && $property->boundaries->isNotEmpty() ? $property->boundaries->toArray() : $defaultBorders);
        @endphp

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card assignment-properties-table-card h-100">
                    <div class="card-body">
                        <div class="row g-4">
                            <x-form.controls.select name="city" label="المدينة" required col="col-md-6"
                                :value="$is_edit ? $property->city : old('city')"
                                :options="$cityOptions" />
                            <x-form.controls.input name="district" label="الحي / النطاق"
                                :value="$is_edit ? $property->district : old('district')"
                                placeholder="مثال: حي العارض" required col="col-md-6" />
                            <x-form.controls.input name="administrative_region" label="المنطقة الإدارية"
                                :value="$is_edit ? $property->administrative_region : old('administrative_region')"
                                placeholder="مثال: منطقة الرياض" required col="col-md-6" />
                            <x-form.controls.input name="plan_number" type="number" step="1" min="0"
                                :value="$is_edit ? $property->plan_number : old('plan_number')"
                                label="رقم المخطط" placeholder="مثال: 2357" required col="col-md-6" />
                            <x-form.controls.input name="parcel_number" type="number" step="1" min="0"
                                :value="$is_edit ? $property->parcel_number : old('parcel_number')"
                                label="رقم القطعة" placeholder="مثال: 1288" required col="col-md-6" />
                        </div>
                        @include('tenant.properties._boundaries-table', [
                            'bordersData' => $bordersData,
                        ])
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                @include('tenant.properties._location-map-card')
            </div>

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                <div class="d-flex gap-2">
                    @if (!$is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button />
                </div>
            </x-form.wizard.actions>
        </div>
    </x-form.wizard.step>

    {{-- الخطوة 3: الملكية والتفاصيل --}}
    <x-form.wizard.step id="property-ownership-validation" :fields="['deed_number', 'deed_date', 'issuing_authority', 'ownership_type', 'owners']">
        <div class="row g-4">
            <x-form.controls.input name="deed_number" type="number" step="1" min="0" label="رقم الصك"
                :value="$is_edit ? $property->deed_number : old('deed_number')"
                placeholder="أدخل رقم الصك" required col="col-md-6" />
            <x-form.controls.input name="deed_date" type="date" label="تاريخ الصك" placeholder="اختر تاريخ الصك"
                :value="$is_edit ? ($property->deed_date instanceof \DateTimeInterface ? $property->deed_date->format('Y-m-d') : $property->deed_date) : old('deed_date')"
                required col="col-md-6" />
            <x-form.controls.input name="issuing_authority" label="جهة الإصدار"
                :value="$is_edit ? $property->issuing_authority : old('issuing_authority')"
                placeholder="مثال: كتابة العدل الأولى بالرياض" required col="col-md-6" />
            <x-form.controls.select name="ownership_type" label="نوع الملكية" required col="col-md-6"
                :value="$is_edit ? ($property->ownership_type instanceof \BackedEnum ? $property->ownership_type->value : $property->ownership_type) : old('ownership_type')"
                :enum="\App\Enums\Tenants\Properties\PropertyOwnershipType::class" />

            {{-- بيانات الوكيل --}}
            <x-form.controls.input name="agent_name" label="اسم الوكيل" placeholder="أدخل اسم الوكيل"
                :value="$is_edit ? $property->agent_name : old('agent_name')"
                :required="false" col="col-md-6" />
            <x-form.controls.input name="agent_national_id" type="number" step="1" min="0"
                label="رقم هوية الوكيل" placeholder="أدخل رقم هوية الوكيل" :required="false"
                :value="$is_edit ? $property->agent_national_id : old('agent_national_id')"
                col="col-md-6" />
            <x-form.controls.input name="agency_number" type="number" step="1" min="0"
                label="رقم الوكالة" placeholder="أدخل رقم الوكالة" :required="false"
                :value="$is_edit ? $property->agency_number : old('agency_number')"
                col="col-md-6" />
            <x-form.controls.input name="agency_date" type="date" label="تاريخ الوكالة"
                :value="$is_edit ? ($property->agency_date instanceof \DateTimeInterface ? $property->agency_date->format('Y-m-d') : $property->agency_date) : old('agency_date')"
                placeholder="اختر تاريخ الوكالة" :required="false" col="col-md-6" />

            {{-- ملاك العقار --}}
            <div class="col-12">
                <div class="card assignment-properties-table-card">
                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0"><i class="ti ti-users me-2 text-primary"></i>بيانات الملاك</h5>
                            <small class="text-muted">يمكنك إضافة مالك واحد أو أكثر للعقار</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="btn-add-owner">
                            <i class="ti ti-plus me-1"></i> إضافة مالك آخر
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="owners-list" data-form-field data-field-name="owners">
                            @php
                                $ownersData = old('owners', $is_edit && isset($property) && $property->owners->isNotEmpty() ? $property->owners->toArray() : [['owner_name' => '', 'national_id_or_cr' => '']]);
                            @endphp
                            @foreach ($ownersData as $idx => $owner)
                                <div class="owner-item card border p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 text-muted owner-number">المالك #{{ $idx + 1 }}</h6>
                                        @if ($idx > 0)
                                            <button type="button"
                                                class="btn btn-icon btn-sm btn-danger btn-remove-owner"
                                                aria-label="حذف المالك">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6" data-form-field
                                            data-field-name="owners[{{ $idx }}][owner_name]">
                                            <label class="form-label">اسم المالك <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="owners[{{ $idx }}][owner_name]"
                                                class="form-control" value="{{ $owner['owner_name'] ?? '' }}"
                                                placeholder="أدخل اسم المالك" data-form-control required>
                                            <div class="invalid-feedback" data-form-error hidden></div>
                                        </div>
                                        <div class="col-md-6" data-form-field
                                            data-field-name="owners[{{ $idx }}][national_id_or_cr]">
                                            <label class="form-label">رقم الهوية / السجل التجاري <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" step="1" min="0"
                                                name="owners[{{ $idx }}][national_id_or_cr]"
                                                class="form-control"
                                                value="{{ $owner['national_id_or_cr'] ?? '' }}"
                                                placeholder="أدخل رقم الهوية أو السجل" data-form-control required>
                                            <div class="invalid-feedback" data-form-error hidden></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- وحدات العقار --}}
            <div class="col-12">
                <div class="card assignment-properties-table-card">
                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0"><i class="ti ti-layout-grid me-2 text-primary"></i>وحدات العقار</h5>
                            <small class="text-muted">إذا كان العقار يحتوي على وحدات متعددة (شقق، محلات، ملحقات)</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="btn-add-unit">
                            <i class="ti ti-plus me-1"></i> إضافة وحدة جديدة
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" data-form-field data-field-name="units">
                            <table class="table table-striped table-bordered w-100">
                                <thead>
                                    <tr>
                                        <th style="width: 15%;" class="text-center align-middle">رقم الوحدة</th>
                                        <th style="width: 20%;" class="text-center align-middle">نوع الوحدة</th>
                                        <th style="width: 15%;" class="text-center align-middle">المساحة (م²)</th>
                                        <th style="width: 20%;" class="text-center align-middle">الاستخدام الحالي</th>
                                        <th style="width: 20%;" class="text-center align-middle">الحالة</th>
                                        <th style="width: 10%;" class="text-center align-middle">حذف</th>
                                    </tr>
                                </thead>
                                <tbody id="units-list">
                                    @php
                                        $unitsData = old('units', $is_edit && isset($property) && $property->units->isNotEmpty() ? $property->units->toArray() : []);
                                    @endphp
                                    @foreach ($unitsData as $idx => $unit)
                                        <tr class="unit-row">
                                            <td class="text-center align-middle"><input type="text"
                                                    name="units[{{ $idx }}][unit_number]"
                                                    class="form-control form-control-sm w-100"
                                                    value="{{ $unit['unit_number'] ?? '' }}"
                                                    placeholder="رقم الوحدة (مثال: 101)" data-form-control></td>
                                            <td class="text-center align-middle"><select name="units[{{ $idx }}][unit_type]"
                                                    class="form-select form-select-sm select2 w-100"
                                                    data-placeholder="اختر نوع الوحدة"
                                                    data-select2-search="false" data-form-control>
                                                    <option value=""></option>
                                                    @foreach (\App\Enums\Tenants\Properties\PropertyUnitType::cases() as $unitType)
                                                        <option value="{{ $unitType->value }}"
                                                            @selected(($unit['unit_type'] ?? '') === $unitType->value)>{{ $unitType->label() }}
                                                        </option>
                                                    @endforeach
                                                </select></td>
                                            <td class="text-center align-middle"><input type="number" step="0.01"
                                                    name="units[{{ $idx }}][area]"
                                                    class="form-control form-control-sm w-100"
                                                    value="{{ $unit['area'] ?? '' }}" placeholder="المساحة (م²)"
                                                    data-form-control></td>
                                            <td class="text-center align-middle"><select name="units[{{ $idx }}][current_use]"
                                                    class="form-select form-select-sm select2 w-100"
                                                    data-placeholder="اختر الاستخدام الحالي"
                                                    data-select2-search="false" data-form-control>
                                                    <option value=""></option>
                                                    @foreach (\App\Enums\Tenants\Properties\PropertyUnitUsage::cases() as $unitUsage)
                                                        <option value="{{ $unitUsage->value }}"
                                                            @selected(($unit['current_use'] ?? '') === $unitUsage->value)>{{ $unitUsage->label() }}
                                                        </option>
                                                    @endforeach
                                                </select></td>
                                            <td class="text-center align-middle">
                                                <select name="units[{{ $idx }}][status]"
                                                    class="form-select form-select-sm select2 w-100"
                                                    data-placeholder="اختر الحالة"
                                                    data-select2-search="false" data-form-control>
                                                    @foreach (\App\Enums\Tenants\Properties\PropertyUnitStatus::cases() as $unitStatus)
                                                        <option value="{{ $unitStatus->value }}"
                                                            {{ ($unit['status'] ?? '') === $unitStatus->value ? 'selected' : '' }}>
                                                            {{ $unitStatus->label() }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="text-center align-middle">
                                                <button type="button"
                                                    class="btn btn-icon btn-sm btn-danger btn-remove-unit"
                                                    aria-label="حذف الوحدة"><i class="ti ti-trash"></i></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                <div class="d-flex gap-2">
                    @if (!$is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button />
                </div>
            </x-form.wizard.actions>
        </div>
    </x-form.wizard.step>

    {{-- الخطوة 4: الملفات والمستندات --}}
    <x-form.wizard.step id="property-files-validation" :fields="['main_image', 'deed_document', 'cadastral_plan', 'coordinates_statement', 'building_permit']">
        <div class="row g-4">
            <x-form.controls.file name="main_image" label="الصورة الرئيسية للعقار" :required="!$is_edit"
                accept="image/jpeg,image/png,image/webp" help="JPG أو PNG أو WebP، بحد أقصى 10MB"
                col="col-md-6" />
            <x-form.controls.file name="deed_document" label="صك الملكية" :required="!$is_edit" accept=".pdf,image/*"
                help="PDF أو صورة، بحد أقصى 10MB" col="col-md-6" />
            <x-form.controls.file name="cadastral_plan" label="مخطط العقار" :required="!$is_edit" accept=".pdf,image/*"
                help="PDF أو صورة، بحد أقصى 10MB" col="col-md-6" />
            <x-form.controls.file name="coordinates_statement" label="كشف الإحداثيات" :required="!$is_edit"
                accept=".pdf,image/*" help="PDF أو صورة، بحد أقصى 10MB" col="col-md-6" />
            <x-form.controls.file name="building_permit" label="رخصة البناء" accept=".pdf,image/*"
                help="PDF أو صورة، بحد أقصى 10MB" :required="false" col="col-md-6" />
            <x-form.controls.file name="assignment_letter_image" label="صورة الإسناد" accept="image/*"
                help="صورة فقط بحد أقصى 10MB" :required="false" col="col-md-6" />
            <x-form.controls.file name="property_images[]" label="صور إضافية للمعرض" multiple
                accept="image/*" help="يمكن اختيار عدة صور للمعرض (بحد أقصى 10MB لكل صورة)" :required="false" col="col-md-6" />
            <x-form.controls.file name="additional_documents[]" label="مستندات إضافية" multiple
                accept=".pdf,image/*" help="يمكن اختيار عدة ملفات (بحد أقصى 10MB لكل ملف)" :required="false" col="col-md-6" />

            <x-form.wizard.actions>
                <x-form.wizard.previous-button />
                <div class="d-flex gap-2">
                    @if (!$is_edit)
                        <x-form.wizard.draft-button />
                    @endif
                    <x-form.wizard.next-button />
                </div>
            </x-form.wizard.actions>
        </div>
    </x-form.wizard.step>

    {{-- الخطوة 5: المراجعة والحفظ --}}
    <x-form.wizard.step id="property-review-validation" :fields="[]">
        <div class="row g-4 assignment-review">
            <div class="col-12 d-flex flex-column gap-4">
                <div class="alert alert-warning mb-0 d-flex align-items-center gap-2" role="alert">
                    <i class="ti ti-alert-triangle fs-3" aria-hidden="true"></i>
                    <span>برجاء مراجعة بيانات العقار وموقعه وحدوده وملكيته ومرفقاته قبل الحفظ النهائي.</span>
                </div>
                <div class="row g-3">
                    <div class="col-lg-6">
                        <x-form.wizard.review-card title="البيانات الأساسية" step="1"
                            body-id="review-basic" col="" class="h-100 assignment-review__card" />
                    </div>
                    <div class="col-lg-6">
                        <x-form.wizard.review-card title="الموقع والحدود" step="2"
                            body-id="review-location" col="" class="h-100 assignment-review__card" />
                    </div>
                    <div class="col-lg-6">
                        <x-form.wizard.review-card title="الملكية والتفاصيل" step="3"
                            body-id="review-ownership" col="" class="h-100 assignment-review__card" />
                    </div>
                    <div class="col-lg-6">
                        <div class="card h-100 assignment-review__card">
                            <div class="card-body">
                                <x-form.controls.textarea name="notes" label="ملاحظات إضافية"
                                    placeholder="أدخل أي ملاحظات إدارية أو توجيهات إضافية للعقار..."
                                    :value="$is_edit ? $property->notes : old('notes')"
                                    :required="false" col="col-12" rows="3" />
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <x-form.wizard.review-card title="الملفات والمستندات" step="4" col=""
                            class="h-100 assignment-review__card">
                            <div class="row g-3">
                                <div class="col-12">
                                    <section class="assignment-selection"
                                        aria-labelledby="review-legal-documents-title">
                                        <div class="w-100 d-flex align-items-center justify-content-between mb-3">
                                            <h6 id="review-legal-documents-title" class="mb-0">المستندات الرسمية
                                                (الصك، المخطط، الإحداثيات، الرخصة)</h6>
                                            <span class="badge bg-primary"><span
                                                    id="review-legal-documents-count">0</span> ملف</span>
                                        </div>
                                        <div id="review-legal-documents" class="assignment-file-list">
                                            <p class="text-muted mb-0" data-empty>لم يتم إرفاق مستندات رسمية بعد.
                                            </p>
                                        </div>
                                    </section>
                                </div>
                                <div class="col-md-6">
                                    <section class="assignment-selection"
                                        aria-labelledby="review-gallery-documents-title">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h6 id="review-gallery-documents-title" class="mb-0">صور العقار والمعرض
                                            </h6>
                                            <span class="badge bg-primary"><span
                                                    id="review-gallery-documents-count">0</span> صورة</span>
                                        </div>
                                        <div id="review-gallery-documents" class="assignment-file-list">
                                            <p class="text-muted mb-0" data-empty>لم يتم إرفاق صور بعد.</p>
                                        </div>
                                    </section>
                                </div>
                                <div class="col-md-6">
                                    <section class="assignment-selection"
                                        aria-labelledby="review-additional-documents-title">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h6 id="review-additional-documents-title" class="mb-0">مستندات
                                                ومرفقات إضافية</h6>
                                            <span class="badge bg-primary"><span
                                                    id="review-additional-documents-count">0</span> ملف</span>
                                        </div>
                                        <div id="review-additional-documents" class="assignment-file-list">
                                            <p class="text-muted mb-0" data-empty>لم يتم إرفاق ملفات إضافية بعد.
                                            </p>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        </x-form.wizard.review-card>
                    </div>
                </div>
            </div>
        </div>

        <x-form.wizard.actions>
            <x-form.wizard.previous-button />
            <div class="d-flex gap-2">
                @if (!$is_edit)
                    <x-form.wizard.draft-button />
                @endif
                <x-form.wizard.save-button />
            </div>
        </x-form.wizard.actions>
    </x-form.wizard.step>
</form>
</x-form.wizard.stepper>