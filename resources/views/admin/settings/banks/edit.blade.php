@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل بنك')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <i class="ti ti-edit me-2"></i>
                تعديل بنك: {{ $bank->bank_name }}
            </h4>
            <a href="{{ route('admin.settings.banks.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i>
                العودة للقائمة
            </a>
        </div>

        <!-- Form Card -->
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.settings.banks.update', $bank) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-4">
                        <!-- اسم البنك -->
                        <x-form.controls.input name="bank_name" id="bank_name" label="اسم البنك" :value="old('bank_name', $bank->bank_name)" required
                            col="col-md-6" />

                        <!-- اسم الحساب -->
                        <x-form.controls.input name="account_name" id="account_name" label="اسم الحساب" :value="old('account_name', $bank->account_name)"
                            required col="col-md-6" />

                        <!-- رقم الآيبان -->
                        <x-form.controls.input name="iban" id="iban" label="رقم الآيبان (IBAN)" :value="old('iban', $bank->iban)"
                            maxlength="24" help="يجب أن يبدأ بـ SA ويتكون من 24 رقم" required col="col-md-6" />

                        <!-- رقم الحساب -->
                        <x-form.controls.input name="account_number" id="account_number" label="رقم الحساب"
                            :value="old('account_number', $bank->account_number)" required col="col-md-6" />

                        <!-- أيقونة البنك -->
                        <x-form.controls.file name="icon" id="icon" label="أيقونة البنك" :value="$bank->icon ? asset('storage/' . $bank->icon) : null"
                            accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                            help="اترك الحقل فارغاً للإبقاء على الصورة الحالية" :max-size="2" col="col-md-6" />
                    </div>

                    <!-- الأزرار -->
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>
                            حفظ التغييرات
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
        document.getElementById('iban').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (!value.startsWith('SA')) {
                value = 'SA' + value.replace('SA', '');
            }
            e.target.value = value.substring(0, 24);
        });
    </script>
@endsection
