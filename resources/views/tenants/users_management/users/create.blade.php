@extends('tenants.layouts.layoutMaster')

@section('title', 'إضافة مستخدم جديد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenants/user-mangment/user/validation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#basic-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">المعلومات الأساسية</span>
                                <span class="bs-stepper-subtitle">البيانات الشخصية والاتصال</span>
                            </span>
                        </button>
                    </div>
                    <div class="line">
                        <i class="ti ti-chevron-right"></i>
                    </div>
                    <div class="step" data-target="#security-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">كلمة المرور والصورة</span>
                                <span class="bs-stepper-subtitle">الأمان والصورة الشخصية</span>
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

                    <form id="wizard-validation-form" action="{{ tenant_route_name('users.store') }}" method="POST"
                        onSubmit="return false" enctype="multipart/form-data" autocomplete="off">
                        @csrf

                        <!-- المعلومات الأساسية -->
                        <div id="basic-info-validation" class="content">
                            <div class="row g-6">
                                <div class="col-sm-6">
                                    <label class="form-label" for="name">الاسم الكامل <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        placeholder="ادخل الاسم الكامل" value="{{ old('name') }}" />
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="employee_number">الرقم الوظيفي <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="employee_number" id="employee_number" class="form-control"
                                        placeholder="ادخل الرقم الوظيفي"
                                        value="{{ old('employee_number', $generatedEmployeeNumber) }}"
                                        style="text-transform: uppercase;" />
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="email">البريد الإلكتروني <span
                                            class="text-danger">*</span></label>
                                    <input type="email" name="email" id="email" class="form-control"
                                        placeholder="ادخل البريد الإلكتروني" value="{{ old('email') }}" autocomplete="off" />
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="phone">رقم الهاتف <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="phone" id="phone" class="form-control"
                                        placeholder="ادخل رقم الهاتف" value="{{ old('phone') }}" />
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="role_id">الدور <span
                                            class="text-danger">*</span></label>
                                    <select name="role_id" id="role_id" class="form-control select2"
                                        data-placeholder="اختر الدور">
                                        <option value=""></option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}"
                                                {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="branch_id">الفرع <span
                                            class="text-danger">*</span></label>
                                    <select name="branch_id" id="branch_id" class="form-control select2"
                                        data-placeholder="اختر الفرع">
                                        <option value=""></option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}"
                                                {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev" disabled>
                                        <i class="ti ti-arrow-right ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-next">
                                        <span class="align-middle d-sm-inline-block d-none me-sm-2">التالي</span>
                                        <i class="ti ti-arrow-right ti-xs"></i>
                                    </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- كلمة المرور والصورة -->
                        <div id="security-info-validation" class="content">
                            <div class="row g-6">

                                <div class="col-sm-6">
                                    <label class="form-label" for="password">كلمة المرور <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" name="password" id="password" class="form-control"
                                            placeholder="ادخل كلمة المرور" autocomplete="new-password" />
                                        <span class="input-group-text cursor-pointer">
                                            <i class="ti ti-eye-off"></i>
                                        </span>
                                    </div>

                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="password_confirmation">تأكيد كلمة المرور <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group input-group-merge">
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                            class="form-control" placeholder="أعد إدخال كلمة المرور" autocomplete="new-password" />
                                        <span class="input-group-text cursor-pointer">
                                            <i class="ti ti-eye-off"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="form-label">الصورة الشخصية</label>
                                        <div
                                            class="d-flex align-items-start align-items-sm-center gap-6 pb-4 border-bottom">
                                            <img src="{{ asset('img/logo.svg') }}" alt="user-avatar"
                                                class="d-block w-px-100 h-px-100 rounded" id="uploadedAvatar" />
                                            <div class="button-wrapper">
                                                <label for="upload" class="btn btn-primary me-3 mb-4" tabindex="0">
                                                    <span class="d-none d-sm-block">تحديد صورة</span>
                                                    <i class="ti ti-upload d-block d-sm-none"></i>
                                                    <input type="file" id="upload" name="image"
                                                        class="account-file-input" hidden
                                                        accept="image/png, image/jpeg, image/jpg, image/gif" />
                                                </label>
                                                <div class="text-muted small">JPG، PNG أو GIF. الحد الأقصى 2MB</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev">
                                        <i class="ti ti-arrow-right ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-next btn-submit">
                                        <span class="align-middle d-sm-inline-block d-none me-sm-2">حفظ</span>
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
