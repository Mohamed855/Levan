@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة إصدارة جديدة')

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
    @vite(['resources/assets/js/admin/versions/form-validation.js'])
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
                                <span class="bs-stepper-title">معلومات الإصدارة</span>
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

                    <form id="wizard-validation-form" action="{{ route('admin.versions.store') }}" method="POST"
                        enctype="multipart/form-data" onSubmit="return false">
                        @csrf

                        <div id="info-validation" class="content">
                            <div class="row g-3">
                                <!-- رقم الإصدار -->
                                <div class="col-md-6">
                                    <label for="version" class="form-label">رقم الإصدارة <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="version" name="version"
                                        value="{{ old('version') }}" placeholder="مثال: v2.0.0">
                                </div>

                                <!-- تاريخ الإصدار -->
                                <div class="col-md-6">
                                    <label for="released_at" class="form-label">تاريخ الإصدار</label>
                                    <input type="date" class="form-control" id="released_at" name="released_at"
                                        value="{{ old('released_at') }}">
                                </div>

                                <!-- سجل التغييرات -->
                                <div class="col-12">
                                    <label for="changelog" class="form-label">سجل التغييرات</label>
                                    <textarea class="form-control" id="changelog" name="changelog" rows="4"
                                        placeholder="مثال: - تحسين الأداء العام
- إصلاح مشكلة تسجيل الدخول
- تحديث واجهات المستخدم">{{ old('changelog') }}</textarea>
                                </div>

                                <div class="col-md-12">
                                    <label for="file_path" class="form-label">مسار الإصدارة <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="file_path" name="file_path"
                                        placeholder="المسار يتم توفيره من قبل الفريق التقني " required>
                                </div>

                                <!-- المرحلة -->
                                <div class="col-md-6">
                                    <label for="stage" class="form-label">مرحلة الإصدار <span
                                            class="text-danger">*</span></label>
                                    <select id="stage" name="stage" class="form-select select2" required
                                        data-placeholder="اختر المرحلة">
                                        <option value=""> </option>
                                        @foreach ($versionStages as $item)
                                            <option value="{{ $item->value }}">
                                                {{ $item->label() }} - ({{ $item->description() }})
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <!-- الحالة -->
                                <div class="col-md-6 mb-4">
                                    <label for="is_active" class="form-label">حالة الإصدارة <span
                                            class="text-danger">*</span></label>
                                    <select id="is_active" name="is_active" class="form-select select2">
                                        <option value="">اختر الحالة</option>
                                        <option value="1" {{ old('is_active') == 1 ? 'selected' : '' }}>مفعلة
                                        </option>
                                        <option value="0" {{ old('is_active') == 0 ? 'selected' : '' }}>غير مفعلة
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary waves-effect waves-light btn-submit">
                                    <i class="bx bx-save me-1"></i>
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
