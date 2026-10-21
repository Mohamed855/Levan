@extends('layouts.dashboard')

@section('title', 'إضافة حساب بنكي جديد')
@section('dashboard-scope', 'admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ti ti-building-bank text-primary me-2"></i>
                إضافة حساب بنكي جديد
            </h4>
            <p class="text-muted mb-0">إدخال بيانات الحساب البنكي المعتمد للتحويلات في المنصة.</p>
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
                    <h5 class="card-title mb-0">بيانات الحساب البنكي</h5>
                </div>
                <div class="card-body pt-4">
                    <form method="POST" action="{{ route('admin.banks.store') }}" enctype="multipart/form-data" id="bank-form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="bank-name">اسم البنك <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="bank-name" class="form-control" placeholder="مثال: مصرف الراجحي" value="{{ old('name') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="account-name">اسم صاحب الحساب <span class="text-danger">*</span></label>
                                <input type="text" name="account_name" id="account-name" class="form-control" placeholder="مثال: شركة المنصة لتقنية المعلومات" value="{{ old('account_name') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="iban">رقم الآيبان (IBAN) <span class="text-danger">*</span></label>
                                <input type="text" name="iban" id="iban" class="form-control font-monospace" placeholder="SA..." value="{{ old('iban') }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="account-number">رقم الحساب (اختياري)</label>
                                <input type="text" name="account_number" id="account-number" class="form-control font-monospace" placeholder="رقم الحساب البنكي" value="{{ old('account_number') }}">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label" for="logo">شعار البنك (اختياري)</label>
                                <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
                                <small class="text-muted">الامتدادات المدعومة: PNG, JPG, SVG بحد أقصى 2 ميجابايت.</small>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is-active" @checked(old('is_active', true))>
                                    <label class="form-check-label fw-bold" for="is-active">تفعيل الحساب فوراً للتحويلات</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-label-secondary" onclick="window.history.back()">إلغاء</button>
                            <x-form.wizard.draft-button />
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i> حفظ الحساب البنكي
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
