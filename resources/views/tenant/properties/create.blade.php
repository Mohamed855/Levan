@extends('tenant.layouts.layoutMaster')

@section('title', 'إضافة عقار جديد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css', 'resources/assets/scss/tenant/pages/assignment-create.scss'])
@endsection

@section('page-script')
    <script>
        window.routes = window.routes || {};
        window.propertyUnitStatuses = @json(array_map(fn($case) => ['value' => $case->value, 'label' => $case->label()],
                \App\Enums\Tenants\Properties\PropertyUnitStatus::cases()));
        window.propertyUnitTypes = @json(array_map(fn($case) => ['value' => $case->value, 'label' => $case->label()],
                \App\Enums\Tenants\Properties\PropertyUnitType::cases()));
        window.propertyUnitUsages = @json(array_map(fn($case) => ['value' => $case->value, 'label' => $case->label()],
                \App\Enums\Tenants\Properties\PropertyUnitUsage::cases()));
    </script>
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenant/properties/create.js'])
@endsection

@section('content')
    @php
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

        <form id="property-create-form" action="{{ tenant_route_name('properties.store') }}" method="POST"
            enctype="multipart/form-data" autocomplete="off" data-form-validate
            data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
            data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
            data-draft-file-url="{{ tenant_route_name('wizard-drafts.file.destroy') }}"
            data-draft-resource-key="tenant.property-create">
            @csrf

            <input type="hidden" name="property_number" value="{{ $generatedPropertyNumber }}">

            {{-- الخطوة 1: البيانات الأساسية --}}
            <x-form.wizard.step id="property-basic-validation" :fields="['name', 'type', 'usage_classification', 'area', 'is_active', 'short_description']">
                <div class="row g-4">
                    <x-form.controls.input name="name" label="اسم العقار" placeholder="مثال: فيلا النخيل" required
                        col="col-md-6" />
                    <x-form.controls.select name="type" label="نوع العقار" required col="col-md-6" :enum="\App\Enums\Tenants\Properties\PropertyType::class" />
                    <x-form.controls.select name="usage_classification" label="تصنيف الاستخدام" required col="col-md-6"
                        :enum="\App\Enums\Tenants\Properties\PropertyUsageClassification::class" />
                    <x-form.controls.input name="area" type="number" step="0.01" label="المساحة (م²)"
                        placeholder="مثال: 600" required col="col-md-6" />
                    <x-form.controls.select name="is_active" label="حالة العقار" required col="col-md-6" :enum="\App\Enums\Shared\ActivationStatus::class"
                        value="1" />
                    <x-form.controls.textarea name="short_description" label="وصف العقار" placeholder="أدخل وصفاً للعقار..."
                        required col="col-md-6" rows="4" maxlength="500" />

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button :href="tenant_route_name('properties.index')" text="العودة" />
                        <div class="d-flex gap-2">
                            <x-form.wizard.draft-button />
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
                <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">
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
                    $bordersData = old('boundaries', $defaultBorders);
                @endphp

                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card assignment-properties-table-card h-100">
                            <div class="card-body">
                                <div class="row g-4">
                                    <x-form.controls.select name="city" label="المدينة" required col="col-md-6"
                                        :options="$cityOptions" />
                                    <x-form.controls.input name="district" label="الحي / النطاق"
                                        placeholder="مثال: حي العارض" required col="col-md-6" />
                                    <x-form.controls.input name="administrative_region" label="المنطقة الإدارية"
                                        placeholder="مثال: منطقة الرياض" required col="col-md-6" />
                                    <x-form.controls.input name="plan_number" type="number" step="1" min="0"
                                        label="رقم المخطط" placeholder="مثال: 2357" required col="col-md-6" />
                                    <x-form.controls.input name="parcel_number" type="number" step="1" min="0"
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
                            <x-form.wizard.draft-button />
                            <x-form.wizard.next-button />
                        </div>
                    </x-form.wizard.actions>
                </div>
            </x-form.wizard.step>

            {{-- الخطوة 3: الملكية والتفاصيل --}}
            <x-form.wizard.step id="property-ownership-validation" :fields="['deed_number', 'deed_date', 'issuing_authority', 'ownership_type', 'owners']">
                <div class="row g-4">
                    <x-form.controls.input name="deed_number" type="number" step="1" min="0" label="رقم الصك"
                        placeholder="أدخل رقم الصك" required col="col-md-6" />
                    <x-form.controls.input name="deed_date" type="date" label="تاريخ الصك" required col="col-md-6" />
                    <x-form.controls.input name="issuing_authority" label="جهة الإصدار"
                        placeholder="مثال: كتابة العدل الأولى بالرياض" required col="col-md-6" />
                    <x-form.controls.select name="ownership_type" label="نوع الملكية" required col="col-md-6"
                        :enum="\App\Enums\Tenants\Properties\PropertyOwnershipType::class" />

                    {{-- ملاك العقار --}}
                    <div class="col-12">
                        <div class="card assignment-properties-table-card">
                            <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-0"><i class="ti ti-users me-2 text-primary"></i>بيانات الملاك
                                    </h5>
                                    <small class="text-muted">يمكنك إضافة مالك واحد أو أكثر للعقار</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary" id="btn-add-owner">
                                    <i class="ti ti-plus me-1"></i> إضافة مالك آخر
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="owners-list" data-form-field data-field-name="owners">
                                    @php
                                        $ownersData = old('owners', [['owner_name' => '', 'national_id_or_cr' => '']]);
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

                    {{-- بيانات الوكيل (اختياري) --}}
                    <x-form.controls.input name="agent_name" label="اسم الوكيل (اختياري)" placeholder="أدخل اسم الوكيل"
                        :required="false" col="col-md-6" />
                    <x-form.controls.input name="agent_national_id" type="number" step="1" min="0"
                        label="رقم هوية الوكيل (اختياري)" placeholder="أدخل رقم هوية الوكيل" :required="false"
                        col="col-md-6" />
                    <x-form.controls.input name="agency_number" type="number" step="1" min="0"
                        label="رقم الوكالة (اختياري)" placeholder="أدخل رقم الوكالة" :required="false" col="col-md-6" />
                    <x-form.controls.input name="agency_date" type="date" label="تاريخ الوكالة (اختياري)"
                        :required="false" col="col-md-6" />

                    {{-- وحدات العقار (اختياري) --}}
                    <div class="col-12">
                        <div class="card assignment-properties-table-card">
                            <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-0"><i class="ti ti-layout-grid me-2 text-primary"></i>وحدات
                                        العقار (اختياري)</h5>
                                    <small class="text-muted">إذا كان العقار يحتوي على وحدات متعددة (شقق، محلات،
                                        ملحقات)</small>
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
                                                $unitsData = old('units', []);
                                            @endphp
                                            @foreach ($unitsData as $idx => $unit)
                                                <tr class="unit-row">
                                                    <td><input type="text"
                                                            name="units[{{ $idx }}][unit_number]"
                                                            class="form-control form-control-sm"
                                                            value="{{ $unit['unit_number'] ?? '' }}"
                                                            placeholder="وحدة ..." data-form-control></td>
                                                    <td><select name="units[{{ $idx }}][unit_type]"
                                                            class="form-select form-select-sm select2"
                                                            data-select2-search="false" data-form-control>
                                                            <option value=""></option>
                                                            @foreach (\App\Enums\Tenants\Properties\PropertyUnitType::cases() as $unitType)
                                                                <option value="{{ $unitType->value }}"
                                                                    @selected(($unit['unit_type'] ?? '') === $unitType->value)>{{ $unitType->label() }}
                                                                </option>
                                                            @endforeach
                                                        </select></td>
                                                    <td><input type="number" step="0.01"
                                                            name="units[{{ $idx }}][area]"
                                                            class="form-control form-control-sm"
                                                            value="{{ $unit['area'] ?? '' }}" placeholder="المساحة"
                                                            data-form-control></td>
                                                    <td><select name="units[{{ $idx }}][current_use]"
                                                            class="form-select form-select-sm select2"
                                                            data-select2-search="false" data-form-control>
                                                            <option value=""></option>
                                                            @foreach (\App\Enums\Tenants\Properties\PropertyUnitUsage::cases() as $unitUsage)
                                                                <option value="{{ $unitUsage->value }}"
                                                                    @selected(($unit['current_use'] ?? '') === $unitUsage->value)>{{ $unitUsage->label() }}
                                                                </option>
                                                            @endforeach
                                                        </select></td>
                                                    <td>
                                                        <select name="units[{{ $idx }}][status]"
                                                            class="form-select form-select-sm select2"
                                                            data-select2-search="false" data-form-control>
                                                            @foreach (\App\Enums\Tenants\Properties\PropertyUnitStatus::cases() as $unitStatus)
                                                                <option value="{{ $unitStatus->value }}"
                                                                    {{ ($unit['status'] ?? '') === $unitStatus->value ? 'selected' : '' }}>
                                                                    {{ $unitStatus->label() }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td class="text-center">
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
                            <x-form.wizard.draft-button />
                            <x-form.wizard.next-button />
                        </div>
                    </x-form.wizard.actions>
                </div>
            </x-form.wizard.step>

            {{-- الخطوة 4: الملفات والمستندات --}}
            <x-form.wizard.step id="property-files-validation" :fields="['main_image', 'deed_document', 'cadastral_plan', 'coordinates_statement', 'building_permit']">
                <div class="row g-4">
                    <x-form.controls.file name="main_image" label="الصورة الرئيسية للعقار" required
                        accept="image/jpeg,image/png,image/webp" help="JPG أو PNG أو WebP، بحد أقصى 4MB"
                        col="col-md-6" />
                    <x-form.controls.file name="deed_document" label="صك الملكية" required accept=".pdf,image/*"
                        help="PDF أو صورة، بحد أقصى 10MB" col="col-md-6" />
                    <x-form.controls.file name="cadastral_plan" label="مخطط العقار" required accept=".pdf,image/*"
                        help="PDF أو صورة، بحد أقصى 10MB" col="col-md-6" />
                    <x-form.controls.file name="coordinates_statement" label="كشف الإحداثيات" required
                        accept=".pdf,image/*" help="PDF أو صورة، بحد أقصى 10MB" col="col-md-6" />
                    <x-form.controls.file name="building_permit" label="رخصة البناء (اختياري)" accept=".pdf,image/*"
                        help="PDF أو صورة، بحد أقصى 10MB" :required="false" col="col-md-6" />
                    <x-form.controls.file name="assignment_letter_image" label="صورة الإسناد (اختياري)" accept="image/*"
                        help="صورة فقط بحد أقصى 4MB" :required="false" col="col-md-6" />
                    <x-form.controls.file name="property_images[]" label="صور إضافية للمعرض (اختياري)" multiple
                        accept="image/*" help="يمكن اختيار عدة صور للمعرض" :required="false" col="col-md-6" />
                    <x-form.controls.file name="additional_documents[]" label="مستندات إضافية (اختياري)" multiple
                        accept=".pdf,image/*" help="يمكن اختيار عدة ملفات" :required="false" col="col-md-6" />

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button />
                        <div class="d-flex gap-2">
                            <x-form.wizard.draft-button />
                            <x-form.wizard.next-button text="المراجعة" />
                        </div>
                    </x-form.wizard.actions>
                </div>
            </x-form.wizard.step>

            {{-- الخطوة 5: المراجعة والحفظ --}}
            <x-form.wizard.step id="property-review-validation" :fields="[]">
                <div class="row g-4 assignment-review">
                    <div class="col-lg-9 d-flex flex-column gap-4">
                        <x-form.wizard.review-card title="البيانات الأساسية" step="1" body-id="review-basic"
                            class="assignment-review__card" />
                        <x-form.wizard.review-card title="الموقع والحدود" step="2" body-id="review-location"
                            class="assignment-review__card" />
                        <x-form.wizard.review-card title="الملكية والتفاصيل" step="3" body-id="review-ownership"
                            class="assignment-review__card" />
                        <x-form.wizard.review-card title="الملفات والمستندات" step="4" body-id="review-files"
                            class="assignment-review__card">
                            <div id="review-files" class="assignment-file-list"></div>
                        </x-form.wizard.review-card>
                        <div class="card assignment-review__card">
                            <div class="card-body">
                                <x-form.controls.textarea name="notes" label="ملاحظات إضافية (اختياري)"
                                    placeholder="أدخل أي ملاحظات إدارية أو توجيهات إضافية للعقار..." :required="false"
                                    col="col-md-6" rows="3" />
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <article class="card assignment-preview h-100">
                            <img id="review-image" src="{{ asset('static/brand/logos/logo.svg') }}"
                                alt="معاينة صورة العقار" class="assignment-preview__image">
                            <div class="card-body">
                                <h5 id="review-name">اسم العقار</h5>
                                <p id="review-description" class="text-muted mb-0">وصف العقار</p>
                            </div>
                        </article>
                    </div>
                </div>

                <x-form.wizard.actions>
                    <x-form.wizard.previous-button />
                    <div class="d-flex gap-2">
                        <x-form.wizard.draft-button />
                        <x-form.wizard.save-button text="حفظ العقار" />
                    </div>
                </x-form.wizard.actions>
            </x-form.wizard.step>
        </form>
    </x-form.wizard.stepper>
@endsection
