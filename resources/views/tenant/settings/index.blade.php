@extends('layouts.dashboard')

@section('title', 'إعدادات المنشأة')
@section('dashboard-scope', 'tenant')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard') }}">الرئيسية</a></li>
                <li class="breadcrumb-item active">إعدادات المنشأة</li>
            </ol>
        </nav>
        <h4 class="mb-0 fw-bold">إعدادات المنشأة والنظام</h4>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="nav-align-top mb-4">
            <ul class="nav nav-pills mb-3" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#tab-general" aria-controls="tab-general" aria-selected="true">
                        <i class="ti ti-building me-1"></i> الإعدادات العامة
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-sms" aria-controls="tab-sms" aria-selected="false">
                        <i class="ti ti-message-dots me-1"></i> إعدادات الرسائل (SMS)
                    </button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-system" aria-controls="tab-system" aria-selected="false">
                        <i class="ti ti-shield-lock me-1"></i> الأمان وتفضيلات النظام
                    </button>
                </li>
            </ul>

            <form action="{{ route('tenant.settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="active_tab" id="active_tab" value="general">

                <div class="tab-content border-0 p-0 shadow-none">
                    {{-- تبويب الإعدادات العامة --}}
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                        <div class="card">
                            <div class="card-header border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold">هوية المكتب والبيانات الأساسية</h5>
                            </div>
                            <div class="card-body pt-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label" for="office_name">اسم المكتب / المنشأة <span class="text-danger">*</span></label>
                                        <input type="text" name="office_name" id="office_name" class="form-control @error('office_name') is-invalid @enderror" value="{{ old('office_name', $settings->office_name) }}" placeholder="أدخل الاسم التجاري للمنشأة">
                                        @error('office_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="logo_text">النص التعريفي المختصر (Logo Text)</label>
                                        <input type="text" name="logo_text" id="logo_text" class="form-control @error('logo_text') is-invalid @enderror" value="{{ old('logo_text', $settings->logo_text) }}" placeholder="نص مختصر بجانب الشعار">
                                        @error('logo_text')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label" for="image">شعار المنشأة</label>
                                        <div class="d-flex align-items-center gap-3">
                                            @if($settings->image)
                                                <img src="{{ asset('storage/' . $settings->image) }}" alt="الشعار الحالي" class="rounded border p-1" style="width: 80px; height: 80px; object-fit: contain;">
                                            @else
                                                <div class="avatar avatar-xl bg-label-secondary rounded d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-photo fs-2"></i>
                                                </div>
                                            @endif
                                            <div class="flex-grow-1">
                                                <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                                                <div class="form-text">الصيغ المدعومة: PNG, JPG, WEBP (الحد الأقصى: 2 ميجابايت). يُفضل أبعاد 300x300 بكسل بخلفية شفافة.</div>
                                                @error('image')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- تبويب إعدادات الرسائل SMS --}}
                    <div class="tab-pane fade" id="tab-sms" role="tabpanel">
                        <div class="card">
                            <div class="card-header border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold">بوابة إرسال الرسائل النصية (SMS)</h5>
                            </div>
                            <div class="card-body pt-4">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <div class="form-check form-switch mb-2">
                                            <input type="hidden" name="sms_enabled" value="0">
                                            <input class="form-check-input" type="checkbox" name="sms_enabled" id="sms_enabled" value="1" {{ old('sms_enabled', $settings->getValue('sms_enabled')) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold" for="sms_enabled">تفعيل إعدادات الرسائل النصية للمستأجر</label>
                                        </div>
                                        <small class="text-warning d-block">لا يوجد حالياً adapter لمزوّد SMS في التطبيق؛ الحفظ لا يرسل رسائل حتى اعتماد المزود وتوصيل التكامل.</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="sms_provider">مزود خدمة الرسائل</label>
                                        <select name="sms_provider" id="sms_provider" class="form-select">
                                            <option value="unifonic" {{ old('sms_provider', $settings->getValue('sms_provider')) == 'unifonic' ? 'selected' : '' }}>يوني فونك (Unifonic)</option>
                                            <option value="msegat" {{ old('sms_provider', $settings->getValue('sms_provider')) == 'msegat' ? 'selected' : '' }}>مسجات (Msegat)</option>
                                            <option value="taqnyat" {{ old('sms_provider', $settings->getValue('sms_provider')) == 'taqnyat' ? 'selected' : '' }}>تقنيات (Taqnyat)</option>
                                            <option value="oursms" {{ old('sms_provider', $settings->getValue('sms_provider')) == 'oursms' ? 'selected' : '' }}>أور إس إم إس (OurSMS)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="sms_sender_id">اسم المرسل المعتمد (Sender Name)</label>
                                        <input type="text" name="sms_sender_id" id="sms_sender_id" class="form-control" value="{{ old('sms_sender_id', $settings->getValue('sms_sender_id')) }}" placeholder="مثال: LOT-AUCTION">
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label" for="sms_api_key">مفتاح الربط البرمجي (API Key / Token)</label>
                                        <input type="password" name="sms_api_key" id="sms_api_key" class="form-control font-monospace" value="" placeholder="اتركه فارغاً للحفاظ على المفتاح الحالي">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- تبويب تفضيلات النظام والأمان --}}
                    <div class="tab-pane fade" id="tab-system" role="tabpanel">
                        <div class="card">
                            <div class="card-header border-bottom py-3">
                                <h5 class="card-title mb-0 fw-bold">الأمان والتحكم بالنظام</h5>
                            </div>
                            <div class="card-body pt-4">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <div class="form-check form-switch mb-2">
                                            <input type="hidden" name="maintenance_mode" value="0">
                                            <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" {{ old('maintenance_mode', $settings->getValue('maintenance_mode')) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold" for="maintenance_mode">وضع الصيانة المؤقت للمستأجر</label>
                                        </div>
                                        <small class="text-warning d-block">لا يوجد middleware يطبق الحجب حالياً؛ الحفظ يسجل التفضيل فقط إلى أن تعتمد سياسة الحجب ومساراتها.</small>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-check form-switch mb-2">
                                            <input type="hidden" name="breadcrumb_visibility" value="0">
                                            <input class="form-check-input" type="checkbox" name="breadcrumb_visibility" id="breadcrumb_visibility" value="1" {{ old('breadcrumb_visibility', $settings->getValue('breadcrumb_visibility', true)) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold" for="breadcrumb_visibility">إظهار شريط التتبع (Breadcrumb) في الشاشات</label>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-check form-switch mb-2">
                                            <input type="hidden" name="force_two_factor_auth" value="0">
                                            <input class="form-check-input" type="checkbox" name="force_two_factor_auth" id="force_two_factor_auth" value="1" {{ old('force_two_factor_auth', $settings->getValue('force_two_factor_auth')) ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold" for="force_two_factor_auth">فرض المصادقة الثنائية (2FA) على جميع موظفي المنشأة</label>
                                        </div>
                                        <small class="text-muted d-block">إلزام جميع المستخدمين بإدخال رمز تحقق يُرسل إلى بريدهم الإلكتروني عند تسجيل الدخول.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end mt-4">
                    <x-form.wizard.draft-button />
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> حفظ الإعدادات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
