@extends('layouts.dashboard')

@section('title', 'السداد عبر التحويل البنكي - فاتورة #' . $bill->bill_number)
@section('dashboard-scope', 'tenant')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('tenant.bills.index') }}">الفواتير</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tenant.bills.show', $bill->id) }}">فاتورة #{{ $bill->bill_number }}</a></li>
                <li class="breadcrumb-item active">التحويل البنكي</li>
            </ol>
        </nav>
        <h4 class="mb-0 fw-bold">السداد عبر التحويل البنكي لفاتورة <span class="text-primary font-monospace">#{{ $bill->bill_number }}</span></h4>
    </div>
    <div>
        <a href="{{ route('tenant.bills.show', $bill->id) }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-right me-1"></i> العودة لتفاصيل الفاتورة
        </a>
    </div>
</div>

<div class="row">
    {{-- بيانات الحسابات البنكية المتاحة --}}
    <div class="col-lg-7 col-12 mb-4">
        <div class="card mb-4">
            <div class="card-header bg-label-primary py-3">
                <h5 class="card-title mb-0 text-primary fw-bold">
                    <i class="ti ti-building-bank me-2"></i> الحسابات البنكية المعتمدة
                </h5>
            </div>
            <div class="card-body pt-4">
                <p class="text-muted mb-4">يرجى تحويل المبلغ المطلوب (<span class="fw-bold text-dark">{{ number_format((float) $bill->amount, 2) }} ر.س</span>) إلى أحد الحسابات التالية مع كتابة رقم الفاتورة في وصف الحوالة:</p>

                <div class="row g-3">
                    @forelse($banks as $bank)
                        <div class="col-12">
                            <div class="border rounded p-3 bg-body-tertiary">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($bank->logo_path)
                                            <img src="{{ asset('storage/' . $bank->logo_path) }}" alt="{{ $bank->name }}" class="rounded" style="width: 38px; height: 38px; object-fit: contain;">
                                        @else
                                            <div class="avatar avatar-sm bg-label-primary rounded d-flex align-items-center justify-content-center">
                                                <i class="ti ti-building-bank"></i>
                                            </div>
                                        @endif
                                        <h6 class="fw-bold mb-0">{{ $bank->name }}</h6>
                                    </div>
                                    <span class="badge bg-label-success">حساب معتمد</span>
                                </div>

                                <div class="row g-2 mt-2 pt-2 border-top">
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">اسم صاحب الحساب:</small>
                                        <span class="fw-semibold">{{ $bank->account_name }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <small class="text-muted d-block">رقم الحساب:</small>
                                        <span class="fw-semibold font-monospace">{{ $bank->account_number }}</span>
                                    </div>
                                    <div class="col-12">
                                        <small class="text-muted d-block">رقم الآيبان (IBAN):</small>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="fw-bold font-monospace fs-6 text-primary">{{ $bank->iban }}</span>
                                            <button type="button" class="btn btn-xs btn-label-secondary copyable-code-wrapper" onclick="navigator.clipboard.writeText('{{ $bank->iban }}'); toastr.success('تم نسخ الآيبان إلى الحافظة');" title="نسخ الآيبان">
                                                <i class="ti ti-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-4 text-muted">
                            <i class="ti ti-alert-circle fs-2 mb-2 d-block"></i>
                            لا توجد حسابات بنكية مسجلة حالياً. يرجى التواصل مع الدعم الفني.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- نموذج رفع الإيصال --}}
    <div class="col-lg-5 col-12 mb-4">
        <div class="card">
            <div class="card-header border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="ti ti-upload me-2 text-primary"></i> تأكيد السداد وإرفاق الإشعار
                </h5>
            </div>
            <div class="card-body pt-4">
                <form action="{{ route('tenant.bills.upload-receipt', $bill->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="bank_id">الحساب المحول إليه <span class="text-danger">*</span></label>
                        <select name="bank_id" id="bank_id" class="form-select @error('bank_id') is-invalid @enderror" required>
                            <option value="">اختر الحساب البنكي...</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}" {{ old('bank_id') == $bank->id ? 'selected' : '' }}>
                                    {{ $bank->name }} - {{ $bank->account_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('bank_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="receipt">صورة الإشعار / ملف التحويل <span class="text-danger">*</span></label>
                        <input type="file" name="receipt" id="receipt" class="form-control @error('receipt') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf" required>
                        <div class="form-text">الصيغ المقبولة: JPG, PNG, PDF (الحد الأقصى: 5 ميجابايت).</div>
                        @error('receipt')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-warning py-2 small mb-4">
                        <i class="ti ti-info-circle me-1"></i> سيتم تدقيق الحوالة وتفعيل الخدمات فور تأكيد وصول المبلغ من قبل الإدارة المالية.
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <x-form.wizard.draft-button />
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-send me-1"></i> إرسال الإشعار للتدقيق
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
