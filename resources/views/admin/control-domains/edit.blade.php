@extends('layouts.dashboard')

@section('title', 'تعديل النطاق')
@section('dashboard-scope', 'admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ti ti-world text-primary me-2"></i>
                تعديل النطاق: <span class="font-monospace text-primary" dir="ltr">{{ $controlDomain->domain }}</span>
            </h4>
            <p class="text-muted mb-0">تحديث إعدادات النطاق أو حالة التفعيل.</p>
        </div>
        <div>
            <a href="{{ route('admin.control-domains.index') }}" class="btn btn-label-secondary">
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
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">بيانات النطاق</h5>
                </div>
                <div class="card-body pt-4">
                    <form method="POST" action="{{ route('admin.control-domains.update', $controlDomain->id) }}" id="control-domain-form">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="domain">اسم النطاق أو الامتداد <span class="text-danger">*</span></label>
                                <input type="text" name="domain" id="domain" class="form-control font-monospace" dir="ltr" placeholder="مثال: app.lot.sa أو .sa" value="{{ old('domain', $controlDomain->domain) }}" required>
                                <small class="text-muted">أدخل اسم النطاق بالكامل أو امتداد النطاق (مثل: .com, .sa).</small>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is-active" @checked(old('is_active', $controlDomain->is_active))>
                                    <label class="form-check-label fw-bold" for="is-active">النطاق نشط ومتاح للاستخدام</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="{{ route('admin.control-domains.index') }}" class="btn btn-label-secondary">إلغاء</a>
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
