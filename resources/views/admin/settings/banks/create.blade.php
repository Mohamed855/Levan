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
                        <!-- اسم البنك -->
                        <x-form.controls.input name="bank_name" id="bank_name" label="اسم البنك"
                            placeholder="مثال: البنك الأهلي السعودي" required col="col-md-6" />

                        <!-- اسم الحساب -->
                        <x-form.controls.input name="account_name" id="account_name" label="اسم الحساب"
                            placeholder="مثال: شركة لوت للمزادات" required col="col-md-6" />

                        <!-- رقم الآيبان -->
                        <x-form.controls.input name="iban" id="iban" label="رقم الآيبان (IBAN)"
                            placeholder="SA0000000000000000000000" maxlength="24"
                            help="يجب أن يبدأ بـ SA ويتكون من 24 رقم" required col="col-md-6" />

                        <!-- رقم الحساب -->
                        <x-form.controls.input name="account_number" id="account_number" label="رقم الحساب"
                            placeholder="مثال: 1234567890" required col="col-md-6" />

                        <!-- أيقونة البنك -->
                        <x-form.controls.file name="icon" id="icon" label="أيقونة البنك"
                            accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                            help="الصيغ المسموحة: JPG, PNG, SVG | الحجم الأقصى: 2MB" required col="col-md-6" />
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
