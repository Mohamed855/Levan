@extends('tenant.layouts.layoutMaster')

@section('title', 'إضافة إسناد جديد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css', 'resources/assets/scss/tenant/pages/assignment-create.scss'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenant/assignments/create.js'])
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'assignment-basic-validation', 'title' => 'البيانات الأساسية'],
            ['id' => 'assignment-properties-validation', 'title' => 'العقارات المرتبطة'],
            ['id' => 'assignment-files-validation', 'title' => 'الملفات والمستندات'],
            ['id' => 'assignment-review-validation', 'title' => 'المراجعة والحفظ'],
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

        <form id="assignment-create-form" action="{{ tenant_route_name('assignments.store') }}" method="POST"
            enctype="multipart/form-data" autocomplete="off" data-form-validate
            data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
            data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
            data-draft-file-url="{{ tenant_route_name('wizard-drafts.file.destroy') }}"
            data-selected-properties-url="{{ tenant_route_name('assignments.selected-properties') }}"
            data-draft-resource-key="tenant.assignment-create" data-property-image-fallback="{{ $propertyImageFallback }}">
            @csrf

            <x-form.wizard.step id="assignment-basic-validation" :fields="[
                'assignment_number',
                'name',
                'category',
                'issuing_entity',
                'city',
                'district',
                'start_date',
                'end_date',
                'notes',
            ]">
                <div class="row g-4">
                    <x-form.controls.input name="assignment_number" label="رقم الإسناد" :value="$generatedAssignmentNumber" required
                        placeholder="رقم الإسناد" col="col-md-6" />
                    <x-form.controls.input name="name" label="اسم الإسناد"
                        placeholder="أدخل اسم الإسناد (مثال: إسناد بيع عقارات المحمدية)" required col="col-md-6" />
                    <x-form.controls.select name="category" label="تصنيف الإسناد" required col="col-md-6"
                        :enum="\App\Enums\Tenants\Assignments\AssignmentCategory::class" />
                    <x-form.controls.input name="issuing_entity" label="الجهة المسندة"
                        placeholder="أدخل الجهة المسندة (مثال: محكمة التنفيذ)" required col="col-md-6" />
                    <x-form.controls.select name="city" label="المدينة" required col="col-md-6" :options="$cityOptions" />
                    <x-form.controls.input name="district" label="النطاق/الحي" placeholder="أدخل النطاق أو الحي"
                        col="col-md-6" />
                    <x-form.controls.input name="start_date" type="date" label="تاريخ البداية"
                        placeholder="اختر تاريخ البداية" required col="col-md-6" />
                    <x-form.controls.input name="end_date" type="date" label="تاريخ النهاية"
                        placeholder="اختر تاريخ النهاية" required col="col-md-6" />
                    <x-form.controls.textarea name="notes" label="وصف الإسناد"
                        placeholder="أدخل وصفاً تفصيلياً للإسناد..." required col="col-md-6" rows="4" />
                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button :href="tenant_route_name('assignments.index')" text="العودة" />
                        <div class="d-flex gap-2"><x-form.wizard.draft-button /><x-form.wizard.next-button /></div>
                    </x-form.wizard.actions>
                </div>
            </x-form.wizard.step>

            <x-form.wizard.step id="assignment-properties-validation" :fields="['property_ids']">
                <div data-form-field data-field-name="property_ids">
                    <div id="selected-property-inputs"></div>
                    <div class="card assignment-properties-table-card">
                        <div class="card-body">
                            <div class="row g-3 mb-4" data-datatable-filter-group="assignment-properties"
                                data-datatable-target="#assignment-properties-table">
                                <x-filter.select id="assignment-property-type" filterKey="type" label="نوع العقار"
                                    placeholder="جميع الأنواع" :enum="\App\Enums\Tenants\Properties\PropertyType::class" col="col-md-4" />
                                <x-filter.select id="assignment-property-data-status" filterKey="data_status"
                                    label="حالة البيانات" placeholder="جميع الحالات" :enum="\App\Enums\Tenants\Properties\PropertyDataStatus::class" col="col-md-4" />
                                <x-filter.select id="assignment-property-city" filterKey="city" label="المدينة"
                                    placeholder="جميع المدن" :options="$cityOptions" col="col-md-4" />
                            </div>
                            {!! $dataTable->table(
                                ['class' => 'table table-striped table-bordered w-100', 'id' => 'assignment-properties-table'],
                                false,
                            ) !!}
                        </div>
                    </div>
                    <div id="property_ids-error" class="invalid-feedback d-block" data-form-error role="alert" hidden>
                    </div>
                </div>
                <section class="assignment-selection mt-4" aria-live="polite">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="mb-0">العقارات المحددة</h6><span class="badge bg-primary"><span
                                id="selected-properties-count">0</span> عقار</span>
                    </div>
                    <div id="selected-properties-cards" class="assignment-selection__items">
                        <p class="text-muted mb-0" data-empty>لم يتم اختيار عقارات بعد.</p>
                    </div>
                </section>
                <x-form.wizard.actions><x-form.wizard.previous-button />
                    <div class="d-flex gap-2"><x-form.wizard.draft-button /><x-form.wizard.next-button /></div>
                </x-form.wizard.actions>
            </x-form.wizard.step>

            <x-form.wizard.step id="assignment-files-validation" :fields="['assignment_image', 'assignment_letter', 'general_documents', 'additional_attachments']">
                <div class="row g-4">
                    <x-form.controls.file name="assignment_image" label="صورة الإسناد" required
                        accept="image/jpeg,image/png,image/webp,image/svg+xml"
                        help="JPG أو PNG أو WebP أو SVG، بحد أقصى 10MB" col="col-md-6" />
                    <x-form.controls.file name="assignment_letter" label="خطاب الإسناد" required accept="application/pdf"
                        help="PDF فقط، بحد أقصى 10MB" col="col-md-6" />
                    <x-form.controls.file name="general_documents" id="general_documents" label="مستندات عامة" required
                        multiple help="يمكن اختيار الملفات على أكثر من مرة" col="col-md-6" />
                    <x-form.controls.file name="additional_attachments" id="additional_attachments" label="مرفقات إضافية"
                        multiple help="يمكن اختيار الملفات على أكثر من مرة" col="col-md-6" />
                </div>
                <x-form.wizard.actions><x-form.wizard.previous-button />
                    <div class="d-flex gap-2"><x-form.wizard.draft-button /><x-form.wizard.next-button text="المراجعة" />
                    </div>
                </x-form.wizard.actions>
            </x-form.wizard.step>

            <x-form.wizard.step id="assignment-review-validation" :fields="[]">
                <div class="row g-4 assignment-review">
                    <div class="col-12 d-flex flex-column gap-4">
                        <div class="alert alert-warning mb-0 d-flex align-items-center gap-2" role="alert">
                            <i class="ti ti-alert-triangle fs-3" aria-hidden="true"></i>
                            <span>برجاء مراجعة بيانات الإسناد والعقارات المرتبطة والمرفقات قبل الاعتماد النهائي.</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-lg-6">
                                <x-form.wizard.review-card title="البيانات الأساسية" step="1"
                                    body-id="review-basic" col="" class="h-100 assignment-review__card" />
                            </div>
                            <div class="col-lg-6">
                                <x-form.wizard.review-card title="العقارات المرتبطة" step="2" col=""
                                    class="h-100 assignment-review__card">
                                    <div id="review-properties" class="assignment-selection__items"></div>
                                </x-form.wizard.review-card>
                            </div>
                            <div class="col-12">
                                <x-form.wizard.review-card title="الملفات والمستندات" step="3" col=""
                                    class="h-100 assignment-review__card">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <section class="assignment-selection"
                                                aria-labelledby="review-primary-documents-title">
                                                <div class="w-100 d-flex align-items-center justify-content-between mb-3">
                                                    <h6 id="review-primary-documents-title" class="mb-0">مستندات الإسناد
                                                        الأساسية (صورة وخطاب الإسناد)</ا>
                                                        <span class="badge bg-primary"><span
                                                                id="review-primary-documents-count">0</span> ملف</span>
                                                </div>
                                                <div id="review-primary-documents" class="assignment-file-list">
                                                    <p class="text-muted mb-0" data-empty>لم يتم إرفاق مستندات أساسية بعد.
                                                    </p>
                                                </div>
                                            </section>
                                        </div>
                                        <div class="col-md-6">
                                            <section class="assignment-selection"
                                                aria-labelledby="review-general-documents-title">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <h6 id="review-general-documents-title" class="mb-0">مستندات عامة
                                                    </h6>
                                                    <span class="badge bg-primary"><span
                                                            id="review-general-documents-count">0</span> ملف</span>
                                                </div>
                                                <div id="review-general-documents" class="assignment-file-list">
                                                    <p class="text-muted mb-0" data-empty>لم يتم إرفاق ملفات بعد.</p>
                                                </div>
                                            </section>
                                        </div>
                                        <div class="col-md-6">
                                            <section class="assignment-selection"
                                                aria-labelledby="review-additional-attachments-title">
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <h6 id="review-additional-attachments-title" class="mb-0">مرفقات
                                                        إضافية</h6>
                                                    <span class="badge bg-primary"><span
                                                            id="review-additional-attachments-count">0</span> ملف</span>
                                                </div>
                                                <div id="review-additional-attachments" class="assignment-file-list">
                                                    <p class="text-muted mb-0" data-empty>لم يتم إرفاق ملفات بعد.</p>
                                                </div>
                                            </section>
                                        </div>
                                    </div>
                                </x-form.wizard.review-card>
                            </div>
                        </div>
                    </div>
                </div>
                <x-form.wizard.actions><x-form.wizard.previous-button />
                    <div class="d-flex gap-2"><x-form.wizard.draft-button /><x-form.wizard.save-button
                            text="حفظ الإسناد" /></div>
                </x-form.wizard.actions>
            </x-form.wizard.step>
        </form>
    </x-form.wizard.stepper>
@endsection
