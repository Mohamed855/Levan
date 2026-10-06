@extends('tenants.layouts.layoutMaster')

@section('title', 'إضافة إسناد جديد')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
        'resources/assets/vendor/libs/select2/select2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/bs-stepper/bs-stepper.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js',
        'resources/assets/vendor/libs/select2/select2.js'
    ])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
    <script>
        $(document).ready(function() {
            if ($.fn.select2) {
                $('.select2').not('.select2-hidden-accessible').each(function() {
                    $(this).select2({
                        placeholder: $(this).data('placeholder') || 'اختر',
                        allowClear: !$(this).prop('required'),
                        width: '100%',
                        language: 'ar',
                        dir: 'rtl'
                    });
                });
            }

            $('#btn-generate-assignment-number').on('click', function() {
                const year = new Date().getFullYear();
                const random = Math.random().toString(36).substring(2, 6).toUpperCase();
                $('#assignment_number').val('ES-' + year + '-' + random).trigger('input');
            });
        });
    </script>
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'assignment-info-validation', 'title' => 'البيانات الأساسية', 'subtitle' => 'معلومات الإسناد والجهة'],
            ['id' => 'assignment-period-validation', 'title' => 'المدة والملاحظات', 'subtitle' => 'الفترة الزمنية والملاحظات'],
        ];
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <x-form.wizard.stepper id="wizard-validation" class="mt-2" :steps="$steps">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="wizard-validation-form" action="{{ tenant_route_name('assignments.store') }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                    @csrf

                    <x-form.wizard.step id="assignment-info-validation" :fields="['assignment_number', 'name', 'issuing_entity', 'city', 'category', 'entity_logo']">
                        <div class="row g-6">
                            {{-- رقم الإسناد مع زر التوليد --}}
                            <div class="col-md-6" data-form-field data-field-name="assignment_number">
                                <label for="assignment_number" class="form-label">رقم الإسناد <span class="text-danger">*</span>
                                    <small class="text-muted">مثال: ES-2026-0154</small>
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace" id="assignment_number" name="assignment_number"
                                        value="{{ old('assignment_number', $generatedAssignmentNumber ?? '') }}" style="text-transform: uppercase;"
                                        data-form-control required>
                                    <button type="button" class="btn btn-secondary" id="btn-generate-assignment-number">
                                        <i class="ti ti-wand"></i> توليد
                                    </button>
                                </div>
                                <div id="assignment_number-error" class="invalid-feedback d-block" data-form-error role="alert" hidden></div>
                            </div>

                            <x-form.controls.input name="name" label="اسم الإسناد" placeholder="مثال: إسناد أراضي شمال الرياض" required col="col-md-6" />

                            <x-form.controls.input name="issuing_entity" label="الجهة المسندة" placeholder="مثال: مركز الإسناد والتصفية - إنفاذ" required col="col-md-6" />

                            <x-form.controls.select name="category" label="تصنيف الإسناد" required col="col-md-6" :options="[
                                ['value' => 'lands', 'label' => 'أراضي'],
                                ['value' => 'villas', 'label' => 'فلل سكنية'],
                                ['value' => 'commercial_buildings', 'label' => 'عمائر تجارية'],
                                ['value' => 'educational', 'label' => 'مباني تعليمية'],
                                ['value' => 'industrial', 'label' => 'أراضي صناعية'],
                            ]" />

                            <x-form.controls.select name="city" label="المدينة الرئيسية" col="col-md-6" :options="[
                                ['value' => 'الرياض', 'label' => 'الرياض'],
                                ['value' => 'جدة', 'label' => 'جدة'],
                                ['value' => 'مكة المكرمة', 'label' => 'مكة المكرمة'],
                                ['value' => 'المدينة المنورة', 'label' => 'المدينة المنورة'],
                                ['value' => 'الدمام', 'label' => 'الدمام'],
                                ['value' => 'الخبر', 'label' => 'الخبر'],
                            ]" />

                            <x-form.controls.image-upload name="entity_logo" label="شعار الجهة أو صورة الإسناد"
                                hint="JPG، PNG أو SVG. الحد الأقصى 2MB" col="col-md-6" />

                            <x-form.wizard.actions>
                                <x-form.wizard.previous-button />
                                <x-form.wizard.next-button />
                            </x-form.wizard.actions>
                        </div>
                    </x-form.wizard.step>

                    <x-form.wizard.step id="assignment-period-validation" :fields="['start_date', 'end_date', 'notes']">
                        <div class="row g-6">
                            <x-form.controls.input name="start_date" type="date" label="تاريخ بداية الإسناد" col="col-md-6" />
                            <x-form.controls.input name="end_date" type="date" label="تاريخ نهاية الإسناد" col="col-md-6" />
                            <x-form.controls.textarea name="notes" label="الملاحظات والتفاصيل" placeholder="أي تفاصيل أو متطلبات خاصة بالإسناد..." col="col-12" rows="4" />

                            <x-form.wizard.actions>
                                <x-form.wizard.previous-button />
                                <x-form.wizard.save-button text="حفظ الإسناد" />
                            </x-form.wizard.actions>
                        </div>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
