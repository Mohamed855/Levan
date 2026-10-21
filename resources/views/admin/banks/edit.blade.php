@extends('layouts.dashboard')

@section('title', 'تعديل الحساب البنكي: ' . $bank->name)
@section('dashboard-scope', 'admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ti ti-edit text-primary me-2"></i>
                تعديل بيانات الحساب البنكي
            </h4>
            <p class="text-muted mb-0">{{ $bank->name }} ({{ $bank->iban }})</p>
        </div>
        <div>
            <a href="{{ route('admin.banks.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">تعديل بيانات الحساب</h5>
                </div>
                <div class="card-body pt-4">
                    <form method="POST" action="{{ route('admin.banks.update', $bank) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="bank-name">اسم البنك <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="bank-name" class="form-control" value="{{ old('name', $bank->name) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="account-name">اسم صاحب الحساب <span class="text-danger">*</span></label>
                                <input type="text" name="account_name" id="account-name" class="form-control" value="{{ old('account_name', $bank->account_name) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="iban">رقم الآيبان (IBAN) <span class="text-danger">*</span></label>
                                <input type="text" name="iban" id="iban" class="form-control font-monospace" value="{{ old('iban', $bank->iban) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="account-number">رقم الحساب</label>
                                <input type="text" name="account_number" id="account-number" class="form-control font-monospace" value="{{ old('account_number', $bank->account_number) }}">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label" for="logo">شعار البنك</label>
                                @if ($bank->logo_path)
                                    <div class="mb-2">
                                        <img src="{{ asset('storage/' . $bank->logo_path) }}" alt="{{ $bank->name }}" class="rounded border p-1" style="max-height: 50px;">
                                    </div>
                                @endif
                                <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
                                <small class="text-muted">اتركه فارغاً للحفاظ على الشعار الحالي.</small>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is-active" @checked(old('is_active', $bank->is_active))>
                                    <label class="form-check-label fw-bold" for="is-active">الحساب نشط ومتاح للتحويلات</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('admin.banks.index') }}" class="btn btn-label-secondary">إلغاء</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i> حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
