@extends('layouts.dashboard')

@section('title', 'إضافة سؤال شائع جديد')
@section('dashboard-scope', 'admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ti ti-help text-primary me-2"></i>
                إضافة سؤال شائع جديد
            </h4>
            <p class="text-muted mb-0">إضافة سؤال وإجابته ليظهر في قسم الأسئلة الشائعة في الموقع العام.</p>
        </div>
        <div>
            <a href="{{ route('admin.faqs.index') }}" class="btn btn-label-secondary">
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
                    <h5 class="card-title mb-0">بيانات السؤال الشائع</h5>
                </div>
                <div class="card-body pt-4">
                    <form method="POST" action="{{ route('admin.faqs.store') }}" id="faq-form">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="question">نص السؤال <span class="text-danger">*</span></label>
                                <input type="text" name="question" id="question" class="form-control" placeholder="مثال: كيف يمكنني الاشتراك في منصة لوت؟" value="{{ old('question') }}" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="answer">الإجابة <span class="text-danger">*</span></label>
                                <textarea name="answer" id="answer" class="form-control" rows="5" placeholder="أدخل تفاصيل إجابة السؤال بوضوح..." required>{{ old('answer') }}</textarea>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is-active" @checked(old('is_active', true))>
                                    <label class="form-check-label fw-bold" for="is-active">تفعيل السؤال وظهوره للزوار فوراً</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-label-secondary" onclick="window.history.back()">إلغاء</button>
                            <x-form.wizard.draft-button />
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i> حفظ السؤال
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
