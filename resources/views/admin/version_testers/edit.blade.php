@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل عضو في فريق اختبار الإصدارات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">تعديل بيانات عضو الفريق</span>
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

                    <form id="wizard-validation-form" data-form-validate data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                        action="{{ route('admin.version_testers.update', $versionTester->id) }}" method="POST"
                        enctype="multipart/form-data" onSubmit="return false">
                        @csrf
                        @method('PUT')

                        <div id="info-validation" class="content">
                            <div class="row g-4">
                                <!-- العميل (التينانت) -->
                                <x-form.controls.select name="tenant_id" id="tenant_id" label="العميل (التينانت)"
                                    placeholder="اختر العميل" required select2 col="col-md-6">
                                    <option value=""> </option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}"
                                            {{ old('tenant_id', $versionTester->tenant_id) == $tenant->id ? 'selected' : '' }}>
                                            {{ $tenant->id }} - ({{ $tenant->company_name }})
                                        </option>
                                    @endforeach
                                </x-form.controls.select>

                                <!-- نوع العضو -->
                                <x-form.controls.select name="type" id="type" label="نوع العضو"
                                    placeholder="اختر نوع العضو" required select2 col="col-md-6">
                                    <option value=""> </option>
                                    @foreach ($types as $type)
                                        <option value="{{ $type->value }}"
                                            {{ old('type', $versionTester->type->value) == $type->value ? 'selected' : '' }}>
                                            {{ $type->label() }} - ({{ $type->description() }})
                                        </option>
                                    @endforeach
                                </x-form.controls.select>

                                <!-- الحالة -->
                                <x-form.controls.select name="is_active" id="is_active" label="حالة العضو"
                                    placeholder="اختر الحالة" required select2 col="col-md-6">
                                    <option value="">اختر الحالة</option>
                                    <x-form.controls.enum-options :enum="\App\Enums\Shared\ActivationStatus::class" :selected="old('is_active', $versionTester->is_active)" />
                                </x-form.controls.select>
                            </div>

                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary waves-effect waves-light btn-submit">
                                    <i class="bx bx-save me-1"></i>
                                    <span>تحديث </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
