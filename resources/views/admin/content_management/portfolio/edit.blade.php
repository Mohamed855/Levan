@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل المحفظة')

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
    @vite(['resources/assets/js/admin/client/form-validation.js'])
    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('logo-preview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
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
                                <span class="bs-stepper-title">تعديل المحفظة</span>
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

                    <form id="wizard-validation-form"
                        action="{{ route('admin.content_management.portfolio.update', $portfolio->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div id="info-validation" class="content">
                            <div class="row g-4">

                                <!-- الاسم -->
                                <div class="col-md-6">
                                    <label for="name" class="form-label">
                                        اسم المحفظة <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name', $portfolio->name) }}" required>
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- الشعار -->
                                <div class="col-md-6">
                                    <label for="logo" class="form-label">
                                        الشعار <span class="text-muted">(صورة اختيارية)</span>
                                    </label>
                                    <input type="file" class="form-control @error('logo') is-invalid @enderror"
                                        id="logo" name="logo" accept="image/*" onchange="previewImage(event)">
                                    <small class="text-muted">صيغ مقبولة: jpg, png, gif - الحد الأقصى: 2MB</small>

                                    {{-- عرض الصورة الحالية أو البريفيو --}}
                                    <div class="mt-3" id="logo-preview-wrapper">
                                        @if ($portfolio->logo)
                                            <p class="text-muted mb-1" style="font-size:12px;">الشعار الحالي:</p>
                                            <img id="logo-preview" src="{{ asset('storage/' . $portfolio->logo) }}"
                                                alt="Logo"
                                                style="max-width: 120px; height: 80px; object-fit: contain; border: 1px solid #ddd; border-radius: 8px; padding: 4px;">
                                        @else
                                            <img id="logo-preview" src="#" alt="معاينة"
                                                style="max-width: 120px; height: 80px; object-fit: contain; border: 1px solid #ddd; border-radius: 8px; padding: 4px; display: none;">
                                        @endif
                                    </div>

                                    @error('logo')
                                        <span class="invalid-feedback d-block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- الحالة -->
                                <div class="col-md-6">
                                    <label for="status" class="form-label">
                                        الحالة <span class="text-danger">*</span>
                                    </label>
                                    <select id="status" name="status"
                                        class="form-select select2 @error('status') is-invalid @enderror" required>
                                        <option value="">اختر الحالة</option>
                                        <option value="1"
                                            {{ old('status', $portfolio->status) == 1 ? 'selected' : '' }}>نشط
                                        </option>
                                        <option value="0"
                                            {{ old('status', $portfolio->status) == 0 ? 'selected' : '' }}>غير
                                            نشط</option>
                                    </select>
                                    @error('status')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                            </div>

                            <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('admin.content_management.portfolio.index') }}" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> رجوع
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i> تحديث
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
