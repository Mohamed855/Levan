@extends('tenants.layouts.layoutMaster')

@section('title', 'إضافة عقار جديد')

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

                <form id="property-create-form" action="{{ tenant_route_name('properties.store') }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                    data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
                    data-draft-file-url="{{ tenant_route_name('wizard-drafts.file.destroy') }}"
                    data-draft-resource-key="tenant.property-create">
                    @csrf

                    <input type="hidden" name="property_number" value="{{ $generatedPropertyNumber }}">

                    {{-- ================================================================= --}}
                    {{-- الخطوة 1: البيانات الأساسية --}}
                    {{-- ================================================================= --}}
                    <x-form.wizard.step id="property-basic-validation" :fields="['name', 'type', 'usage_classification', 'area', 'is_active', 'short_description']">
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h5 class="card-title mb-1"><i class="ti ti-file-description me-2"></i>البيانات
                                            الأساسية</h5>
                                        <p class="text-muted small mb-0">أدخل البيانات الأساسية والوصفية للعقار في هذه
                                            الخطوة.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <x-form.controls.input name="name" label="اسم العقار"
                                                placeholder="مثال: فيلا النخيل" :required="true" col="col-md-6" />

                                            <x-form.controls.select name="type" label="نوع العقار" :required="true"
                                                col="col-md-6" :enum="\App\Enums\Tenants\Properties\PropertyType::class" />

                                            <x-form.controls.select name="usage_classification" label="تصنيف الإستخدام"
                                                :required="true" col="col-md-6" :enum="\App\Enums\Tenants\Properties\PropertyUsageClassification::class" />

                                            <x-form.controls.input name="area" type="number" step="0.01"
                                                label="المساحة (م²)" placeholder="مثال: 600" :required="true"
                                                col="col-md-6" />

                                            <x-form.controls.select name="is_active" label="حالة العقار" :required="true"
                                                col="col-md-6" :options="[
                                                    ['value' => '1', 'label' => 'نشط'],
                                                    ['value' => '0', 'label' => 'غير نشط'],
                                                ]" value="1" />

                                            <x-form.controls.textarea name="short_description" label="وصف مختصر للعقار"
                                                placeholder="أدخل وصفاً مختصراً للعقار (مثل: موقع مميز، قريب من الخدمات...)"
                                                :required="true" col="col-12" rows="3" maxlength="500" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="card border mb-3">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-3 mb-3">
                                            <div class="avatar avatar-md bg-label-primary rounded p-2">
                                                <i class="ti ti-info-circle fs-3"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">ملاحظات هامة</h6>
                                                <small class="text-muted">إرشادات تعبئة النموذج</small>
                                            </div>
                                        </div>
                                        <ul class="text-muted small ps-3 mb-0">
                                            <li class="mb-2">جميع الحقول التي عليها علامة <span
                                                    class="text-danger">*</span> إلزامية.</li>
                                            <li class="mb-2">تأكد من صحة المساحة المدخلة بالمتر المربع.</li>
                                            <li class="mb-2">يمكنك حفظ النموذج كمسودة في أي وقت والعودة لاحقاً.</li>
                                            <li>سيتم ربط العقار بالإسناد تلقائياً عند إنشاء الإسناد.</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="card border">
                                    <div class="card-body text-center">
                                        <div class="avatar avatar-lg bg-label-info rounded-circle mx-auto mb-2 p-3">
                                            <i class="ti ti-progress fs-2"></i>
                                        </div>
                                        <h6 class="mb-1">حالة اكتمال البيانات</h6>
                                        <span class="badge bg-label-warning mb-2">قيد الإدخال (20%)</span>
                                        <p class="text-muted small mb-0">يتم تحديث الحالة تلقائياً حسب تقدمك في الخطوات
                                            الخمس.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <x-form.wizard.actions>
                                    <x-form.wizard.previous-button :href="tenant_route_name('properties.index')" text="إلغاء والعودة" />
                                    <div class="d-flex gap-2">
                                        <x-form.wizard.draft-button />
                                        <x-form.wizard.next-button />
                                    </div>
                                </x-form.wizard.actions>
                            </div>
                        </div>
                    </x-form.wizard.step>

                    {{-- ================================================================= --}}
                    {{-- الخطوة 2: الموقع والحدود --}}
                    {{-- ================================================================= --}}
                    <x-form.wizard.step id="property-location-validation" :fields="[
                        'city',
                        'district',
                        'administrative_region',
                        'plan_number',
                        'parcel_number',
                        'google_maps_url',
                        'boundaries',
                    ]">
                        <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude') }}">
                        <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude') }}">

                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h5 class="card-title mb-1"><i class="ti ti-map-pin me-2"></i>بيانات الموقع الجغرافي
                                        </h5>
                                        <p class="text-muted small mb-0">حدد المدينة، الحي، المنطقة، والمخطط والقطعة.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <x-form.controls.select name="city" label="المدينة" :required="true"
                                                col="col-md-4" :options="$cityOptions" />

                                            <x-form.controls.input name="district" label="الحي"
                                                placeholder="مثال: حي العارض" :required="true" col="col-md-4" />

                                            <x-form.controls.input name="administrative_region" label="المنطقة الإدارية"
                                                placeholder="مثال: منطقة الرياض" :required="true" col="col-md-4" />

                                            <x-form.controls.input name="plan_number" label="رقم المخطط"
                                                placeholder="مثال: 2357" :required="true" col="col-md-6" />

                                            <x-form.controls.input name="parcel_number" label="رقم القطعة"
                                                placeholder="مثال: 1288" :required="true" col="col-md-6" />

                                            <x-form.controls.input name="google_maps_url"
                                                label="رابط الموقع (Google Maps)"
                                                placeholder="https://maps.google.com/?q=..." :required="true"
                                                col="col-12" icon="ti ti-link"
                                                help="الصق رابط الموقع من خرائط جوجل لاستخراج الإحداثيات وتحديد الموقع على الخريطة تلقائياً." />
                                        </div>
                                    </div>
                                </div>

                                {{-- حدود العقار وأطوالها (Dynamic inputs) --}}
                                <div class="card border">
                                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title mb-1"><i class="ti ti-ruler-2 me-2"></i>حدود العقار
                                                وأطوالها</h5>
                                            <p class="text-muted small mb-0">أدخل أطوال وحدود العقار الأربعة (الشمال،
                                                الجنوب، الشرق، الغرب).</p>
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
                                                        $defaultBorders = [
                                                            [
                                                                'direction' => 'north',
                                                                'border_type' => 'شارع',
                                                                'border_description' => 'شارع عرض 20 متر',
                                                                'length' => '30.00',
                                                            ],
                                                            [
                                                                'direction' => 'south',
                                                                'border_type' => 'قطعة',
                                                                'border_description' => 'رقم القطعة 1288',
                                                                'length' => '30.00',
                                                            ],
                                                            [
                                                                'direction' => 'east',
                                                                'border_type' => 'قطعة',
                                                                'border_description' => 'رقم القطعة 1290',
                                                                'length' => '20.00',
                                                            ],
                                                            [
                                                                'direction' => 'west',
                                                                'border_type' => 'شارع',
                                                                'border_description' => 'شارع عرض 15 متر',
                                                                'length' => '20.00',
                                                            ],
                                                        ];
                                                        $bordersData = old('boundaries', $defaultBorders);
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
                                        <small class="text-muted">معاينة تفاعلية حية (Leaflet)</small>
                                    </div>
                                    <div class="card-body">
                                        <div id="map-warning-box" class="alert alert-warning py-2 px-3 small mb-2">
                                            <i class="ti ti-info-circle me-1"></i> الصق رابط خرائط Google Maps أعلاه لعرض
                                            الموقع تلقائياً على الخريطة.
                                        </div>
                                        <div id="property-leaflet-map" class="leaflet-map-container"
                                            style="height: 300px; width: 100%;"></div>
                                        <small class="text-muted mt-2 d-block">
                                            <i class="ti ti-hand-finger me-1"></i> يمكنك سحب المؤشر لتعديل وتحديث
                                            الإحداثيات بدقة.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <x-form.wizard.actions>
                                    <x-form.wizard.previous-button />
                                    <div class="d-flex gap-2">
                                        <x-form.wizard.draft-button />
                                        <x-form.wizard.next-button />
                                    </div>
                                </x-form.wizard.actions>
                            </div>
                        </div>
                    </x-form.wizard.step>

                    {{-- ================================================================= --}}
                    {{-- الخطوة 3: الملكية والتفاصيل --}}
                    {{-- ================================================================= --}}
                    <x-form.wizard.step id="property-ownership-validation" :fields="['deed_number', 'deed_date', 'issuing_authority', 'ownership_type', 'owners']">
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h5 class="card-title mb-1"><i class="ti ti-certificate me-2"></i>بيانات الصك
                                            والملكية</h5>
                                        <p class="text-muted small mb-0">بيانات الصك الرسمي ونوع الملكية وجهة الإصدار.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <x-form.controls.file name="assignment_letter_image"
                                                label="صورة الإسناد (اختياري)" accept="image/*"
                                                help="صورة فقط (JPG, PNG, WebP) بحجم أقصاه 4MB" :required="false"
                                                col="col-md-6" />

                                            <x-form.controls.input name="deed_number" label="رقم الصك"
                                                placeholder="أدخل رقم الصك" :required="true" col="col-md-6" />

                                            <x-form.controls.input name="deed_date" type="date" label="تاريخ الصك"
                                                :required="true" col="col-md-4" />

                                            <x-form.controls.input name="issuing_authority" label="جهة الإصدار"
                                                placeholder="مثال: كتابة العدل الأولى بالرياض" :required="true"
                                                col="col-md-4" />

                                            <x-form.controls.select name="ownership_type" label="نوع الملكية"
                                                :required="true" col="col-md-4" :enum="\App\Enums\Tenants\Properties\PropertyOwnershipType::class" />
                                        </div>
                                    </div>
                                </div>

                                {{-- ملاك العقار (Dynamic inputs) --}}
                                <div class="card border mb-3">
                                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title mb-1"><i class="ti ti-users me-2"></i>بيانات الملاك</h5>
                                            <p class="text-muted small mb-0">يمكنك إضافة مالك واحد أو أكثر للعقار.</p>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-label-primary" id="btn-add-owner">
                                            <i class="ti ti-plus me-1"></i> إضافة مالك آخر
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div id="owners-list" data-form-field data-field-name="owners">
                                            @php
                                                $ownersData = old('owners', [
                                                    ['owner_name' => '', 'national_id_or_cr' => ''],
                                                ]);
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

                                {{-- بيانات الوكيل (اختياري) --}}
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h5 class="card-title mb-1"><i class="ti ti-user-check me-2"></i>بيانات الوكيل
                                            (اختياري)</h5>
                                        <p class="text-muted small mb-0">بيانات الوكيل الشرعي إن وجد.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <x-form.controls.input name="agent_name" label="اسم الوكيل"
                                                placeholder="أدخل اسم الوكيل" :required="false" col="col-md-6" />

                                            <x-form.controls.input name="agent_national_id" label="رقم الهوية"
                                                placeholder="أدخل رقم هوية الوكيل" :required="false" col="col-md-6" />

                                            <x-form.controls.input name="agency_number" label="رقم الوكالة"
                                                placeholder="أدخل رقم الوكالة" :required="false" col="col-md-6" />

                                            <x-form.controls.input name="agency_date" type="date"
                                                label="تاريخ الوكالة" :required="false" col="col-md-6" />
                                        </div>
                                    </div>
                                </div>

                                {{-- وحدات العقار (Dynamic inputs - اختياري) --}}
                                <div class="card border">
                                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title mb-1"><i class="ti ti-layout-grid me-2"></i>وحدات العقار
                                                (اختياري)</h5>
                                            <p class="text-muted small mb-0">إذا كان العقار يحتوي على وحدات متعددة (شقق،
                                                محلات، ملحقات).</p>
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
                                                        $unitsData = old('units', []);
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

                            <div class="col-lg-4">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center gap-3 mb-3">
                                            <div class="avatar avatar-md bg-label-success rounded p-2">
                                                <i class="ti ti-shield-check fs-3"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">توثيق الملكية</h6>
                                                <small class="text-muted">بيانات رسمية معتمدة</small>
                                            </div>
                                        </div>
                                        <p class="text-muted small mb-2">أدخل بيانات الملكية كما هي في الصك أو الوثائق
                                            الرسمية.</p>
                                        <p class="text-muted small mb-0">يمكنك إضافة أكثر من مالك في حال كان العقار مشاعاً
                                            أو مملوكاً لعدة أشخاص أو شركاء.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <x-form.wizard.actions>
                                    <x-form.wizard.previous-button />
                                    <div class="d-flex gap-2">
                                        <x-form.wizard.draft-button />
                                        <x-form.wizard.next-button />
                                    </div>
                                </x-form.wizard.actions>
                            </div>
                        </div>
                    </x-form.wizard.step>

                    {{-- ================================================================= --}}
                    {{-- الخطوة 4: المستندات والصور --}}
                    {{-- ================================================================= --}}
                    <x-form.wizard.step id="property-documents-validation" :fields="['deed_document', 'cadastral_plan', 'coordinates_statement', 'main_image']">
                        <div class="row g-4">
                            <div class="col-lg-7">
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h5 class="card-title mb-1"><i class="ti ti-files me-2"></i>المستندات المطلوبة
                                        </h5>
                                        <p class="text-muted small mb-0">ارفع المستندات الرسمية الداعمة للعقار.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <x-form.controls.file name="deed_document" label="صك الملكية"
                                                accept=".pdf,image/*" help="ملف PDF أو صورة بحد أقصى 10MB"
                                                :required="true" col="col-md-6" />

                                            <x-form.controls.file name="cadastral_plan" label="مخطط العقار"
                                                accept=".pdf,image/*" help="ملف PDF أو صورة بحد أقصى 10MB"
                                                :required="true" col="col-md-6" />

                                            <x-form.controls.file name="coordinates_statement" label="كشف الإحداثيات"
                                                accept=".pdf,image/*" help="ملف PDF أو صورة بحد أقصى 10MB"
                                                :required="true" col="col-md-6" />

                                            <x-form.controls.file name="building_permit" label="رخصة البناء (اختياري)"
                                                accept=".pdf,image/*" help="ملف PDF أو صورة بحد أقصى 10MB"
                                                :required="false" col="col-md-6" />

                                            <x-form.controls.file name="additional_documents[]"
                                                label="مستندات إضافية (اختياري)" multiple accept=".pdf,image/*"
                                                help="يمكنك اختيار عدة ملفات معاً" :required="false" col="col-12" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h5 class="card-title mb-1"><i class="ti ti-photo me-2"></i>صور العقار</h5>
                                        <p class="text-muted small mb-0">الصورة الرئيسية ومعرض الصور للعقار.</p>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <x-form.controls.image-upload name="main_image" label="الصورة الرئيسية للعقار"
                                                :required="true" accept="image/jpeg,image/png,image/webp"
                                                hint="الصورة الأساسية التي تظهر في قائمة وبطاقات العقارات (JPG, PNG, WebP بحد أقصى 4MB)"
                                                col="col-12" />

                                            <x-form.controls.file name="property_images[]"
                                                label="صور العقار الإضافية (معرض الصور)" multiple accept="image/*"
                                                help="يمكن اختيار عدة صور للمعرض (داخلية، خارجية، جوية)" :required="false"
                                                col="col-12" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <x-form.wizard.actions>
                                    <x-form.wizard.previous-button />
                                    <div class="d-flex gap-2">
                                        <x-form.wizard.draft-button />
                                        <x-form.wizard.next-button text="المراجعة والحفظ" />
                                    </div>
                                </x-form.wizard.actions>
                            </div>
                        </div>
                    </x-form.wizard.step>

                    {{-- ================================================================= --}}
                    {{-- الخطوة 5: المراجعة والحفظ --}}
                    {{-- ================================================================= --}}
                    <x-form.wizard.step id="property-review-validation" :fields="[]">
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="card border mb-3">
                                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-primary"><i class="ti ti-file-text me-1"></i> ملخص البيانات
                                            الأساسية</h6>
                                        <button type="button" class="btn btn-sm btn-secondary" data-edit-step="1">
                                            <i class="ti ti-edit me-1"></i> تعديل
                                        </button>
                                    </div>
                                    <div class="card-body" id="review-basic">
                                        <p class="text-muted mb-0">جاري تحميل البيانات...</p>
                                    </div>
                                </div>

                                <div class="card border mb-3">
                                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-primary"><i class="ti ti-map-pin me-1"></i> ملخص الموقع
                                            والحدود</h6>
                                        <button type="button" class="btn btn-sm btn-secondary" data-edit-step="2">
                                            <i class="ti ti-edit me-1"></i> تعديل
                                        </button>
                                    </div>
                                    <div class="card-body" id="review-location">
                                        <p class="text-muted mb-0">جاري تحميل البيانات...</p>
                                    </div>
                                </div>

                                <div class="card border mb-3">
                                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-primary"><i class="ti ti-certificate me-1"></i> ملخص الملكية
                                            والتفاصيل</h6>
                                        <button type="button" class="btn btn-sm btn-secondary" data-edit-step="3">
                                            <i class="ti ti-edit me-1"></i> تعديل
                                        </button>
                                    </div>
                                    <div class="card-body" id="review-ownership">
                                        <p class="text-muted mb-0">جاري تحميل البيانات...</p>
                                    </div>
                                </div>

                                <div class="card border mb-3">
                                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 text-primary"><i class="ti ti-files me-1"></i> المستندات والصور
                                            المرفقة</h6>
                                        <button type="button" class="btn btn-sm btn-secondary" data-edit-step="4">
                                            <i class="ti ti-edit me-1"></i> تعديل
                                        </button>
                                    </div>
                                    <div class="card-body" id="review-documents">
                                        <p class="text-muted mb-0">جاري فحص الملفات المرفقة...</p>
                                    </div>
                                </div>

                                <div class="card border">
                                    <div class="card-body">
                                        <x-form.controls.textarea name="notes" label="ملاحظات إضافية (اختياري)"
                                            placeholder="أدخل أي ملاحظات إدارية أو توجيهات إضافية للعقار..."
                                            :required="false" col="col-12" rows="3" />
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                {{-- بطاقة معاينة الصورة الرئيسية --}}
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h6 class="card-title mb-0">الصورة الرئيسية للعقار</h6>
                                    </div>
                                    <div class="card-body text-center p-3">
                                        <img id="review-main-image-preview"
                                            src="{{ asset('static/brand/logos/logo.svg') }}" alt="معاينة صورة العقار"
                                            class="img-fluid rounded mb-2"
                                            style="max-height: 200px; width: 100%; object-fit: cover;">
                                    </div>
                                </div>

                                {{-- قائمة التحقق Checklist --}}
                                <div class="card border mb-3">
                                    <div class="card-header pb-2">
                                        <h6 class="card-title mb-0"><i class="ti ti-checklist me-1 text-primary"></i>
                                            قائمة التحقق</h6>
                                        <small class="text-muted">تأكد من اكتمال كافة الأقسام</small>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="checklist-item">
                                            <span>البيانات الأساسية</span>
                                            <span id="check-step-1"><span class="badge bg-label-success"><i
                                                        class="ti ti-check me-1"></i> مكتمل</span></span>
                                        </div>
                                        <div class="checklist-item">
                                            <span>الموقع والحدود</span>
                                            <span id="check-step-2"><span class="badge bg-label-success"><i
                                                        class="ti ti-check me-1"></i> مكتمل</span></span>
                                        </div>
                                        <div class="checklist-item">
                                            <span>الملكية والملاك</span>
                                            <span id="check-step-3"><span class="badge bg-label-success"><i
                                                        class="ti ti-check me-1"></i> مكتمل</span></span>
                                        </div>
                                        <div class="checklist-item">
                                            <span>المستندات والصور</span>
                                            <span id="check-step-4"><span class="badge bg-label-success"><i
                                                        class="ti ti-check me-1"></i> مكتمل</span></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- حالة البيانات --}}
                                <div class="card border text-center">
                                    <div class="card-body">
                                        <div class="avatar avatar-lg bg-label-success rounded-circle mx-auto mb-2 p-3">
                                            <i class="ti ti-circle-check fs-2"></i>
                                        </div>
                                        <h6 class="mb-1">حالة البيانات</h6>
                                        <span class="badge bg-label-success mb-2">مكتملة وجاهزة للحفظ (100%)</span>
                                        <p class="text-muted small mb-0">كافة البيانات والمستندات المطلوبة مكتملة وجاهزة
                                            للربط بإسناد.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <x-form.wizard.actions>
                                    <x-form.wizard.previous-button />
                                    <div class="d-flex gap-2">
                                        <x-form.wizard.draft-button />
                                        <x-form.wizard.save-button text="حفظ العقار" />
                                    </div>
                                </x-form.wizard.actions>
                            </div>
                        </div>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
