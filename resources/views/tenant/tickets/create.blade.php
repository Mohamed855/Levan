@extends('layouts.dashboard')

@section('title', 'فتح تذكرة دعم فني جديدة')
@section('dashboard-scope', 'tenant')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('tenant.tickets.index') }}">الدعم الفني</a></li>
                <li class="breadcrumb-item active">فتح تذكرة جديدة</li>
            </ol>
        </nav>
        <h4 class="mb-0 fw-bold">فتح تذكرة دعم فني جديدة</h4>
    </div>
    <div>
        <a href="{{ route('tenant.tickets.index') }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-right me-1"></i> العودة للقائمة
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8 col-12">
        <div class="card">
            <div class="card-header border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="ti ti-help me-2 text-primary"></i> بيانات المشكلة / الاستفسار
                </h5>
            </div>
            <div class="card-body pt-4">
                <form action="{{ route('tenant.tickets.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="subject">عنوان التذكرة <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror" value="{{ old('subject') }}" placeholder="أدخل ملخصاً موجزاً للمشكلة أو الاستفسار" required>
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="department">القسم المعني <span class="text-danger">*</span></label>
                        <select name="department" id="department" class="form-select @error('department') is-invalid @enderror" required>
                            <option value="">اختر القسم المختص...</option>
                            <option value="technical" {{ old('department') == 'technical' ? 'selected' : '' }}>الدعم الفني والتقني</option>
                            <option value="billing" {{ old('department') == 'billing' ? 'selected' : '' }}>الفواتير والمالية</option>
                            <option value="auctions" {{ old('department') == 'auctions' ? 'selected' : '' }}>إدارة المزادات والعمليات</option>
                            <option value="general" {{ old('department') == 'general' ? 'selected' : '' }}>استفسارات عامة</option>
                        </select>
                        @error('department')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="description">تفاصيل المشكلة / الطلب <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" rows="5" class="form-control @error('description') is-invalid @enderror" placeholder="اشرح بالتفصيل ما واجهته أو الاستفسار المطلوب مع ذكر أرقام العمليات ذات الصلة إن وُجدت" required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex gap-2 justify-content-end pt-3 border-top">
                        <x-form.wizard.draft-button />
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-send me-1"></i> إرسال التذكرة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
