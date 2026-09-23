@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة عميل جديد')

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
    @vite(['resources/assets/js/admin/tenant_managment/form-validation.js'])
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
                                <span class="bs-stepper-title">معلومات العميل</span>
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

                    <form id="wizard-validation-form" action="{{ route('admin.tenantManage.store') }}" method="POST"
                        enctype="multipart/form-data" onSubmit="return false">
                        @csrf

                        <div id="info-validation" class="content">
                            <div class="row g-3">
                                <!-- الاسم -->
                                <div class="col-md-6">
                                    <label for="company_name" class="form-label"> اسم المنشأة
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="company_name" name="company_name"
                                        value="{{ old('company_name') }}" placeholder="مثال:  أحمد محمد للتحصيل " required>
                                </div>

                                <!-- قيمة الخصم -->
                                <div class="col-md-6">
                                    <label for="commercial_number" class="form-label">رقم السجل التجاري
                                        <span id="discountUnit"></span>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="commercial_number"
                                        name="commercial_number" placeholder="مثال: 1010123456"
                                        value="{{ old('commercial_number') }}" min="0" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="name" class="form-label"> اسم المستخدم
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="{{ old('name') }}" placeholder="اسم المستخدم " required>
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label"> رقم الجوال
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        value="{{ old('phone') }}" placeholder="05xxxxxxxx" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="email" class="form-label"> البريد الإلكتروني
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="{{ old('email') }}" placeholder="example@domain.com" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="password">
                                        كلمة المرور
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" id="password" name="password" class="form-control"
                                        placeholder="••••••••" required />
                                </div>

                                <!-- تأكيد كلمة المرور -->
                                <div class="col-md-4">
                                    <label class="form-label" for="password_confirmation">
                                        تأكيد كلمة المرور
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" id="password_confirmation" name="password_confirmation"
                                        class="form-control" placeholder="••••••••" required />
                                </div>

                            </div>

                            <div class="col-12 d-flex justify-content-end mt-4">

                                <button type="submit" class="btn btn-primary waves-effect waves-light btn-submit">
                                    <span>حفظ </span>
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
