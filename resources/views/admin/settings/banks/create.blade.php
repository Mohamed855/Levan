@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة بنك جديد')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <i class="ti ti-building-bank me-2"></i>
                إضافة بنك جديد
            </h4>
            <a href="{{ route('admin.settings.banks.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i>
                العودة للقائمة
            </a>
        </div>

        <!-- Form Card -->
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.settings.banks.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-4">
                        <!-- اسم البنك -->
                        <div class="col-md-6">
                            <label class="form-label" for="bank_name">
                                <i class="ti ti-building-bank me-1"></i>
                                اسم البنك
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('bank_name') is-invalid @enderror"
                                id="bank_name" name="bank_name" value="{{ old('bank_name') }}"
                                placeholder="مثال: البنك الأهلي السعودي" required>
                            @error('bank_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- اسم الحساب -->
                        <div class="col-md-6">
                            <label class="form-label" for="account_name">
                                <i class="ti ti-user me-1"></i>
                                اسم الحساب
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('account_name') is-invalid @enderror"
                                id="account_name" name="account_name" value="{{ old('account_name') }}"
                                placeholder="مثال: شركة لوت للمزادات" required>
                            @error('account_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- رقم الآيبان -->
                        <div class="col-md-6">
                            <label class="form-label" for="iban">
                                <i class="ti ti-credit-card me-1"></i>
                                رقم الآيبان (IBAN)
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('iban') is-invalid @enderror" id="iban"
                                name="iban" value="{{ old('iban') }}" placeholder="SA0000000000000000000000"
                                maxlength="24" required>
                            @error('iban')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                <i class="ti ti-info-circle me-1"></i>
                                يجب أن يبدأ بـ SA ويتكون من 24 رقم
                            </small>
                        </div>

                        <!-- رقم الحساب -->
                        <div class="col-md-6">
                            <label class="form-label" for="account_number">
                                <i class="ti ti-numbers me-1"></i>
                                رقم الحساب
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('account_number') is-invalid @enderror"
                                id="account_number" name="account_number" value="{{ old('account_number') }}"
                                placeholder="مثال: 1234567890" required>
                            @error('account_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- أيقونة البنك -->
                        <div class="col-12">
                            <label class="form-label" for="icon">
                                <i class="ti ti-photo me-1"></i>
                                أيقونة البنك
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control @error('icon') is-invalid @enderror" id="icon"
                                name="icon" accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                                onchange="previewImage(event)" required>
                            @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">
                                <i class="ti ti-info-circle me-1"></i>
                                الصيغ المسموحة: JPG, PNG, SVG | الحجم الأقصى: 2MB
                            </small>

                            <!-- معاينة الصورة -->
                            <div id="imagePreview" class="mt-3" style="display: none;">
                                <img id="preview" src="" alt="Preview"
                                    style="max-width: 200px; border-radius: 8px; border: 2px solid #e0e0e0;">
                            </div>
                        </div>
                    </div>

                    <!-- الأزرار -->
                    <div class="mt-4 d-flex gap-2">
                        <x-form.wizard.draft-button />
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>
                            حفظ البنك
                        </button>
                        <a href="{{ route('admin.settings.banks.index') }}" class="btn btn-secondary">
                            <i class="ti ti-x me-1"></i>
                            إلغاء
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }

        // التحقق من صيغة الآيبان
        document.getElementById('iban').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (!value.startsWith('SA')) {
                value = 'SA' + value.replace('SA', '');
            }
            e.target.value = value.substring(0, 24);
        });
    </script>
@endsection
