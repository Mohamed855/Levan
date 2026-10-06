@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل العميل')

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
                                <span class="bs-stepper-title">تعديل العميل</span>
                                <span class="bs-stepper-subtitle">تحديث البيانات الأساسية</span>
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
                        action="{{ route('admin.content_management.client.update', $client->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div id="info-validation" class="content">
                            <div class="row g-4">
                                <!-- الاسم -->
                                <div class="col-md-6">
                                    <label for="name" class="form-label">اسم العميل <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name', $client->name) }}" required>
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- الشعار -->
                                <div class="col-md-6">
                                    <label for="logo" class="form-label">الشعار <span class="text-muted">(صورة
                                            اختيارية)</span></label>
                                    <input type="file" class="form-control @error('logo') is-invalid @enderror"
                                        id="logo" name="logo" accept="image/*">
                                    <small class="text-muted">صيغ مقبولة: jpg, png, gif - الحد الأقصى: 2MB</small>
                                    @if ($client->logo)
                                        <div class="mt-2">
                                            <img src="{{ asset('storage/' . $client->logo) }}" alt="Logo"
                                                style="max-width: 100px; height: auto;">
                                        </div>
                                    @endif
                                    @error('logo')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- الحالة -->
                                <div class="col-md-6">
                                    <label for="status" class="form-label">الحالة <span
                                            class="text-danger">*</span></label>
                                    <select id="status" name="status"
                                        class="form-select select2 @error('status') is-invalid @enderror" required>
                                        <option value="">اختر الحالة</option>
                                        <option value="1" {{ old('status', $client->status) == 1 ? 'selected' : '' }}>
                                            نشط</option>
                                        <option value="0" {{ old('status', $client->status) == 0 ? 'selected' : '' }}>
                                            غير نشط</option>
                                    </select>
                                    @error('status')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('admin.content_management.client.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i>
                                    رجوع
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>
                                    تحديث
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
