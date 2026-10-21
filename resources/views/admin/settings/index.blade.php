@extends('layouts.dashboard')

@section('title', 'إعدادات النظام والمنصة')
@section('dashboard-scope', 'admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ti ti-settings text-primary me-2"></i>
                إعدادات النظام والمنصة
            </h4>
            <p class="text-muted mb-0">إدارة الإعدادات العامة، الربط البرمجي، خوادم البريد، وسياسات الأمان.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <div class="row">
            {{-- Tabs navigation --}}
            <div class="col-12 col-md-3 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body p-2">
                        <div class="nav flex-column nav-pills" id="settings-tabs" role="tablist" aria-orientation="vertical">
                            <button class="nav-link active text-start py-3" id="tab-general" data-bs-toggle="pill" data-bs-target="#content-general" type="button" role="tab">
                                <i class="ti ti-building me-2"></i> الإعدادات العامة
                            </button>
                            <button class="nav-link text-start py-3" id="tab-etmam" data-bs-toggle="pill" data-bs-target="#content-etmam" type="button" role="tab">
                                <i class="ti ti-api-app me-2"></i> تكامل ETMAM API
                            </button>
                            <button class="nav-link text-start py-3" id="tab-microsoft" data-bs-toggle="pill" data-bs-target="#content-microsoft" type="button" role="tab">
                                <i class="ti ti-brand-windows me-2"></i> Microsoft Graph
                            </button>
                            <button class="nav-link text-start py-3" id="tab-security" data-bs-toggle="pill" data-bs-target="#content-security" type="button" role="tab">
                                <i class="ti ti-shield-lock me-2"></i> الأمان والمصادقة
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabs content --}}
            <div class="col-12 col-md-9">
                <div class="tab-content p-0" id="settings-tab-content">
                    {{-- 1. General --}}
                    <div class="tab-pane fade show active" id="content-general" role="tabpanel">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header border-bottom">
                                <h5 class="card-title mb-0">بيانات المنصة والمكتب</h5>
                            </div>
                            <div class="card-body pt-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">اسم المكتب / المنصة</label>
                                        <input type="text" name="office_name" class="form-control" value="{{ old('office_name', $general['office_name'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">نص الشعار (Logo Text)</label>
                                        <input type="text" name="logo_text" class="form-control" value="{{ old('logo_text', $general['logo_text'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">البريد الإلكتروني الرسمي</label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email', $general['email'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">رقم الهاتف</label>
                                        <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $general['phone_number'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">السجل التجاري</label>
                                        <input type="text" name="commercial_register" class="form-control" value="{{ old('commercial_register', $general['commercial_register'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">الرقم الضريبي</label>
                                        <input type="text" name="tax_number" class="form-control" value="{{ old('tax_number', $general['tax_number'] ?? '') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">عنوان المكتب</label>
                                        <input type="text" name="office_address" class="form-control" value="{{ old('office_address', $general['office_address'] ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm mb-4">
                            <div class="card-header border-bottom">
                                <h5 class="card-title mb-0">حالة التشغيل وفترة التجربة</h5>
                            </div>
                            <div class="card-body pt-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" id="switch-maintenance" @checked(old('maintenance_mode', $general['maintenance_mode'] ?? false))>
                                            <label class="form-check-label fw-bold" for="switch-maintenance">وضع الصيانة (Maintenance Mode)</label>
                                        </div>
                                        <small class="text-muted d-block">إغلاق المنصة مؤقتاً أمام الزوار لإجراء التحديثات.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="trial_days_mode" value="1" id="switch-trial" @checked(old('trial_days_mode', $general['trial_days_mode'] ?? true))>
                                            <label class="form-check-label fw-bold" for="switch-trial">تفعيل الفترة التجريبية للعملاء الجدد</label>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mt-2">
                                            <label class="small text-muted mb-0">مدة التجربة (بالأيام):</label>
                                            <input type="number" name="trial_days_count" class="form-control form-control-sm w-auto" min="1" max="90" value="{{ old('trial_days_count', $general['trial_days_count'] ?? 14) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm mb-4">
                            <div class="card-header border-bottom">
                                <h5 class="card-title mb-0">روابط التواصل والخرائط</h5>
                            </div>
                            <div class="card-body pt-4">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label"><i class="ti ti-brand-x me-1"></i> منصة X</label>
                                        <input type="url" name="x_url" class="form-control" value="{{ old('x_url', $general['x_url'] ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label"><i class="ti ti-brand-facebook me-1"></i> فيسبوك</label>
                                        <input type="url" name="facebook_url" class="form-control" value="{{ old('facebook_url', $general['facebook_url'] ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label"><i class="ti ti-brand-linkedin me-1"></i> لينكد إن</label>
                                        <input type="url" name="linkedin_url" class="form-control" value="{{ old('linkedin_url', $general['linkedin_url'] ?? '') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">رابط خريطة Google Maps (Embed)</label>
                                        <input type="text" name="google_maps_embed_url" class="form-control" value="{{ old('google_maps_embed_url', $general['google_maps_embed_url'] ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. ETMAM API --}}
                    <div class="tab-pane fade" id="content-etmam" role="tabpanel">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">إعدادات بوابة إتمام (ETMAM Integration)</h5>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" name="etmam_enabled" value="1" id="switch-etmam" @checked(old('etmam_enabled', $etmam['etmam_enabled'] ?? false))>
                                    <label class="form-check-label fw-bold" for="switch-etmam">تفعيل الربط</label>
                                </div>
                            </div>
                            <div class="card-body pt-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">رابط المنظومة الأساسي (Base URL)</label>
                                        <input type="text" name="etmam_base_url" class="form-control font-monospace" value="{{ old('etmam_base_url', $etmam['etmam_base_url'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">رمز النظام (System Code)</label>
                                        <input type="text" name="etmam_system_code" class="form-control font-monospace" value="{{ old('etmam_system_code', $etmam['etmam_system_code'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">رمز التوثيق (API Token)</label>
                                        <input type="password" name="etmam_api_token" class="form-control font-monospace" value="{{ old('etmam_api_token', $etmam['etmam_api_token'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">المفتاح السري (HMAC Secret)</label>
                                        <input type="password" name="etmam_hmac_secret" class="form-control font-monospace" value="{{ old('etmam_hmac_secret', $etmam['etmam_hmac_secret'] ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Microsoft Graph --}}
                    <div class="tab-pane fade" id="content-microsoft" role="tabpanel">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header border-bottom">
                                <h5 class="card-title mb-0">إعدادات خادم البريد (Microsoft Graph API)</h5>
                            </div>
                            <div class="card-body pt-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">البريد الإلكتروني الرئيسي للإرسال</label>
                                        <input type="email" name="main_email" class="form-control" value="{{ old('main_email', $microsoft['main_email'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">معرف التطبيق (Client ID)</label>
                                        <input type="text" name="microsoft_client_id" class="form-control font-monospace" value="{{ old('microsoft_client_id', $microsoft['microsoft_client_id'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">معرف المستأجر (Tenant ID)</label>
                                        <input type="text" name="microsoft_tenant_id" class="form-control font-monospace" value="{{ old('microsoft_tenant_id', $microsoft['microsoft_tenant_id'] ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">رابط إعادة التوجيه (Redirect URI)</label>
                                        <input type="url" name="microsoft_redirect_uri" class="form-control font-monospace" value="{{ old('microsoft_redirect_uri', $microsoft['microsoft_redirect_uri'] ?? '') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">الرمز السري (Client Secret)</label>
                                        <input type="password" name="microsoft_client_secret" class="form-control font-monospace" placeholder="اتركه فارغاً للحفاظ على السري الحالي">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Security --}}
                    <div class="tab-pane fade" id="content-security" role="tabpanel">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header border-bottom">
                                <h5 class="card-title mb-0">سياسات الأمان والمصادقة</h5>
                            </div>
                            <div class="card-body pt-4">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="force_two_factor_auth" value="1" id="switch-2fa" @checked(old('force_two_factor_auth', $security['force_two_factor_auth'] ?? false))>
                                    <label class="form-check-label fw-bold" for="switch-2fa">إلزام المصادقة الثنائية (2FA) لجميع مديري النظام</label>
                                </div>
                                <small class="text-muted d-block">عند التفعيل، لن يتمكن أي مشرف من تسجيل الدخول إلا بعد إكمال إعداد المصادقة عبر تطبيق الموثق (Authenticator App).</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action button --}}
                @can('settings.update')
                    <div class="card shadow-sm">
                        <div class="card-body d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="ti ti-device-floppy me-1"></i> حفظ جميع الإعدادات
                            </button>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    </form>
@endsection
