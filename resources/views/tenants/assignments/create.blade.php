@extends('tenants.layouts.layoutMaster')

@section('title', 'إضافة إسناد جديد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css', 'resources/assets/scss/pages/assignment-create.scss'])
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
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form id="assignment-create-form" action="{{ tenant_route_name('assignments.store') }}" method="POST"
            enctype="multipart/form-data" autocomplete="off" data-form-validate
            data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
            data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
            data-draft-file-url="{{ tenant_route_name('wizard-drafts.file.destroy') }}"
            data-selected-properties-url="{{ tenant_route_name('assignments.selected-properties') }}"
            data-draft-resource-key="tenant.assignment-create">
            @csrf

            <x-form.wizard.step id="assignment-basic-validation" :fields="['assignment_number', 'name', 'category', 'status', 'issuing_entity', 'city', 'district', 'start_date', 'end_date', 'notes']">
                <div class="row g-4">
                    <x-form.controls.input name="assignment_number" label="رقم الإسناد" :value="$generatedAssignmentNumber" required col="col-md-6" />
                    <x-form.controls.input name="name" label="اسم الإسناد" required col="col-md-6" />
                    <x-form.controls.select name="category" label="نوع الإسناد" required col="col-md-6" :enum="\App\Enums\Tenants\Assignments\AssignmentCategory::class" />
                    <x-form.controls.select name="status" label="حالة الإسناد" required col="col-md-6" :enum="\App\Enums\Tenants\Assignments\AssignmentStatus::class" />
                    <x-form.controls.input name="issuing_entity" label="الجهة المسندة" required col="col-md-6" />
                    <x-form.controls.select name="city" label="المدينة" required col="col-md-6" :options="$cityOptions" />
                    <x-form.controls.input name="district" label="النطاق/الحي" required col="col-md-6" />
                    <x-form.controls.input name="start_date" type="date" label="تاريخ البداية" required col="col-md-6" />
                    <x-form.controls.input name="end_date" type="date" label="تاريخ النهاية" required col="col-md-6" />
                    <x-form.controls.textarea name="notes" label="وصف الإسناد" required col="col-12" rows="4" />
                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button :href="tenant_route_name('assignments.index')" text="العودة" />
                        <div class="d-flex gap-2"><x-form.wizard.draft-button /><x-form.wizard.next-button /></div>
                    </x-form.wizard.actions>
                </div>
            </x-form.wizard.step>

            <x-form.wizard.step id="assignment-properties-validation" :fields="['property_ids']">
                <div id="selected-property-inputs"></div>
                <div data-form-field data-field-name="property_ids">
                    <x-data-table.filters table="#assignment-properties-table">
                        <x-filter.select id="assignment-property-type" filterKey="type" label="نوع العقار" placeholder="جميع الأنواع" :enum="\App\Enums\Tenants\Properties\PropertyType::class" />
                        <x-filter.select id="assignment-property-data-status" filterKey="data_status" label="حالة البيانات" placeholder="جميع الحالات" :enum="\App\Enums\Tenants\Properties\PropertyDataStatus::class" />
                        <x-filter.select id="assignment-property-city" filterKey="city" label="المدينة" placeholder="جميع المدن" :options="$cityOptions" />
                    </x-data-table.filters>
                    <div class="card"><div class="card-body">{!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'assignment-properties-table'], false) !!}</div></div>
                    <div id="property_ids-error" class="invalid-feedback d-block" data-form-error role="alert" hidden></div>
                </div>
                <section class="assignment-selection mt-4" aria-live="polite">
                    <div class="d-flex align-items-center justify-content-between mb-3"><h5 class="mb-0">العقارات المحددة</h5><span class="badge bg-label-primary"><span id="selected-properties-count">0</span> عقار</span></div>
                    <div id="selected-properties-cards" class="assignment-selection__items"><p class="text-muted mb-0" data-empty>لم يتم اختيار عقارات بعد.</p></div>
                </section>
                <x-form.wizard.actions><x-form.wizard.previous-button /><div class="d-flex gap-2"><x-form.wizard.draft-button /><x-form.wizard.next-button /></div></x-form.wizard.actions>
            </x-form.wizard.step>

            <x-form.wizard.step id="assignment-files-validation" :fields="['assignment_image', 'assignment_letter', 'general_documents', 'additional_attachments']">
                <div class="row g-4">
                    <x-form.controls.image-upload name="assignment_image" label="صورة الإسناد" required accept="image/jpeg,image/png,image/webp,image/svg+xml" hint="JPG أو PNG أو WebP أو SVG، بحد أقصى 2MB" col="col-md-6" />
                    <x-form.controls.file name="assignment_letter" label="خطاب الإسناد" required accept="application/pdf" help="PDF فقط، بحد أقصى 10MB" col="col-md-6" />
                    <x-form.controls.file name="general_documents" id="general_documents" label="مستندات عامة" required multiple help="يمكن اختيار الملفات على أكثر من مرة" col="col-md-6" />
                    <x-form.controls.file name="additional_attachments" id="additional_attachments" label="مرفقات إضافية" required multiple help="يمكن اختيار الملفات على أكثر من مرة" col="col-md-6" />
                    <div class="col-12"><div id="general-documents-cards" class="assignment-file-list" aria-live="polite"></div></div>
                    <div class="col-12"><div id="additional-attachments-cards" class="assignment-file-list" aria-live="polite"></div></div>
                </div>
                <x-form.wizard.actions><x-form.wizard.previous-button /><div class="d-flex gap-2"><x-form.wizard.draft-button /><x-form.wizard.next-button text="المراجعة" /></div></x-form.wizard.actions>
            </x-form.wizard.step>

            <x-form.wizard.step id="assignment-review-validation" :fields="[]">
                <div class="row g-4 assignment-review">
                    <div class="col-lg-9 d-flex flex-column gap-4">
                        <article class="card assignment-review__card"><div class="card-header d-flex justify-content-between"><h5 class="mb-0">البيانات الأساسية</h5><button type="button" class="btn btn-sm btn-outline-primary" data-edit-step="1">تعديل</button></div><div class="card-body" id="review-basic"></div></article>
                        <article class="card assignment-review__card"><div class="card-header d-flex justify-content-between"><h5 class="mb-0">العقارات المرتبطة</h5><button type="button" class="btn btn-sm btn-outline-primary" data-edit-step="2">تعديل</button></div><div class="card-body"><div id="review-properties" class="assignment-selection__items"></div></div></article>
                        <article class="card assignment-review__card"><div class="card-header d-flex justify-content-between"><h5 class="mb-0">الملفات والمستندات</h5><button type="button" class="btn btn-sm btn-outline-primary" data-edit-step="3">تعديل</button></div><div class="card-body"><div id="review-files" class="assignment-file-list"></div></div></article>
                    </div>
                    <div class="col-lg-3"><article class="card assignment-preview h-100"><img id="review-image" src="{{ asset('static/brand/logos/logo.svg') }}" alt="معاينة صورة الإسناد" class="assignment-preview__image"><div class="card-body"><h5 id="review-name">اسم الإسناد</h5><p id="review-description" class="text-muted mb-0">وصف الإسناد</p></div></article></div>
                </div>
                <x-form.wizard.actions><x-form.wizard.previous-button /><div class="d-flex gap-2"><x-form.wizard.draft-button /><x-form.wizard.save-button text="حفظ الإسناد" /></div></x-form.wizard.actions>
            </x-form.wizard.step>
        </form>
    </x-form.wizard.stepper>
@endsection
