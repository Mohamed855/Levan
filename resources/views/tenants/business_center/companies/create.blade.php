@extends('tenants.layouts.layoutMaster')

@section('title', 'إضافة شركة جديدة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenants/business-center/companies/validation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#company-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">معلومات الشركة</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="bs-stepper-content">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="wizard-validation-form" action="{{ tenant_route_name('companies.store') }}" method="POST"
                        enctype="multipart/form-data" novalidate data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                        @csrf

                        <div id="company-info-validation" class="content">
                            <div class="row g-4 mb-4">
                                {{-- اسم الشركة --}}
                                <x-form.controls.input name="name" id="name" label="اسم الشركة"
                                    placeholder="ادخل اسم الشركة" :value="old('name')" required col="col-md-6" />

                                {{-- نسبة المستهدف --}}
                                <x-form.controls.input type="number" name="target_percentage" id="target_percentage"
                                    label="نسبة المستهدف الشهري (%)" placeholder="مثال: 10" :value="old('target_percentage')"
                                    step="0.01" min="0" max="100"
                                    help="النسبة المئوية من إجمالي المحفظة (مثال: 10 تعني 10%)" required col="col-md-6" />

                                {{-- شعار الشركة --}}
                                <x-form.controls.image-upload name="logo" id="logo" label="شعار الشركة"
                                    accept=".jpg,.jpeg,.png,.webp"
                                    hint="الأنواع: JPG, JPEG, PNG — الحجم الأقصى: 2MB" required col="col-md-6" />
                            </div>

                                {{-- المفوضون --}}
                                <div class="col-12 mb-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="form-label mb-0">المفوّضون</label>
                                        <button type="button" class="btn btn-primary btn-sm" id="btn-add-person">
                                            <i class="ti ti-plus me-1"></i>إضافة مفوّض
                                        </button>
                                    </div>

                                    <div id="authorized-people-list">
                                        <!-- Dynamic rows will be added here -->
                                    </div>
                                </div>

                                {{-- أزرار التحكم --}}
                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev" disabled>
                                        السابق
                                    </button>
                                    <div class="d-flex gap-2">
                                        <x-form.wizard.draft-button />
                                        <button type="submit" class="btn btn-primary btn-next btn-submit">
                                            حفظ
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
