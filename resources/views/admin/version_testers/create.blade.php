@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة عضو إلى فريق اختبار الإصدارات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/admin/version_testers/form-validation.js'])
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
                                <span class="bs-stepper-title">معلومات عضو الفريق</span>
                                <span class="bs-stepper-subtitle">البيانات الأساسية</span>
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

                    <form id="wizard-validation-form" action="{{ route('admin.version_testers.store') }}" method="POST"
                        enctype="multipart/form-data" onSubmit="return false">
                        @csrf

                        <div id="info-validation" class="content">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="tenant_id" class="form-label">العميل (التينانت)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select id="tenant_id" name="tenant_id" class="form-select select2" required
                                        data-placeholder="اختر العميل">
                                        <option value=""> </option>
                                        @foreach ($tenants as $tenant)
                                            <option value="{{ $tenant->id }}"
                                                {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                                                {{ $tenant->id }} - ({{ $tenant->company_name }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="type" class="form-label">نوع العضو <span
                                            class="text-danger">*</span></label>
                                    <select id="type" name="type" class="form-select select2" required
                                        data-placeholder="اختر نوع العضو">
                                        <option value=""> </option>
                                        @foreach ($types as $type)
                                            <option value="{{ $type->value }}"
                                                {{ old('type') == $type->value ? 'selected' : '' }}>
                                                {{ $type->label() }} - ({{ $type->description() }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 mb-4">
                                    <label for="is_active" class="form-label">حالة العضو <span
                                            class="text-danger">*</span></label>
                                    <select id="is_active" name="is_active" class="form-select select2">
                                        <option value="">اختر الحالة</option>
                                        <option value="1" {{ old('is_active') == 1 ? 'selected' : '' }}>مفعل</option>
                                        <option value="0" {{ old('is_active') == 0 ? 'selected' : '' }}>غير مفعل
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <x-wizard.actions>
                                <x-wizard.btn-prev :href="route('admin.version_testers.index')" />
                                <div class="d-flex gap-2">
                                    <x-wizard.btn-draft step="1" />
                                    <x-wizard.btn-save type="submit" />
                                </div>
                            </x-wizard.actions>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
