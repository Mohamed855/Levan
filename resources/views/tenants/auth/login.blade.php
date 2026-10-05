@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];

    use App\Helpers\Tenants\Settings\SettingsHelper;

    $tenantLogo = SettingsHelper::get('image');
    $logoUrl = $tenantLogo ? tasset($tenantLogo) : asset('img/Levan-logo.svg');

    $currentHost = request()->getHost();
    $centralDomains = array_map(function ($domain) {
        return str_replace(['http://', 'https://', 'www.'], '', $domain);
    }, config('tenancy.central_domains', []));

    $isCentralDomain =
        in_array($currentHost, $centralDomains) || in_array('www.' . $currentHost, $centralDomains);

    $loginAction = $isCentralDomain ? url('/' . tenant('id') . '/login') : url('/login');
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'تسجيل الدخول')

@section('page-style')
    @vite(['resources/assets/css/tenants/auth/login.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenants/auth/login.js'])
@endsection

@section('content')
<div class="tenant-login-container">
    <!-- Right Side - Login Form -->
    <div class="tenant-login-form-side">
        <!-- Subtle Brand Watermark -->
        <svg class="tenant-brand-watermark" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M16.917 11.7836V0H20.7068V6.23046C20.6877 7.42477 20.3185 8.58728 19.6449 9.57423C18.9713 10.5612 18.0229 11.3293 16.917 11.7836Z" fill="currentColor"/>
            <path d="M5.41665 0.150299H0.0478516L10.4022 10.491C11.2609 11.3225 12.3448 11.8841 13.5199 12.1064C14.6949 12.3287 15.9094 12.2018 17.0129 11.7415L5.41665 0.150299Z" fill="currentColor"/>
            <path d="M17.1219 22.2164V34H13.332V27.7695C13.3509 26.5752 13.72 25.4125 14.3936 24.4255C15.0672 23.4386 16.0158 22.6705 17.1219 22.2164Z" fill="currentColor"/>
            <path d="M28.6316 33.8497H33.9883L23.6359 23.509C22.7774 22.6773 21.6935 22.1155 20.5184 21.8932C19.3433 21.6709 18.1288 21.798 17.0253 22.2585L28.6316 33.8497Z" fill="currentColor"/>
            <path d="M11.7969 17.1022H0V13.3166H6.23751C7.43323 13.3355 8.59717 13.7042 9.58528 14.377C10.5734 15.0499 11.3423 15.9974 11.7969 17.1022Z" fill="currentColor"/>
            <path d="M0.150391 28.5992V33.9499L10.5028 23.6092C11.335 22.7514 11.8971 21.6687 12.1196 20.4951C12.3421 19.3214 12.2153 18.1084 11.7547 17.006L0.150391 28.5992Z" fill="currentColor"/>
            <path d="M22.2412 16.8978H34.0381V20.6834H27.8006C26.6049 20.6642 25.4411 20.2954 24.4531 19.6226C23.465 18.9498 22.696 18.0025 22.2412 16.8978Z" fill="currentColor"/>
            <path d="M33.8876 5.41082V0.0480957L23.5352 10.3908C22.7024 11.2482 22.1398 12.3309 21.9172 13.5047C21.6947 14.6785 21.822 15.8918 22.2833 16.994L33.8876 5.41082Z" fill="currentColor"/>
        </svg>

        <div class="tenant-login-form-inner">
            <div class="tenant-login-logo-wrapper">
                <img src="{{ $logoUrl }}" alt="Logo" class="tenant-login-logo-img">
            </div>

            <h2 class="tenant-login-title">مرحباً بكم</h2>
            <p class="tenant-login-subtitle">سجل الدخول بحساب الشركة لاستخدام المنصة</p>

            @if (session('status'))
                <div class="alert alert-success py-2 px-3 mb-4 text-center" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <form id="formAuthentication" action="{{ $loginAction }}" method="POST" novalidate>
                @csrf
                <x-form.controls.input
                    name="email"
                    label="البريد الإلكتروني"
                    placeholder="أدخل البريد الإلكتروني"
                    type="email"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="username"
                    col="col-12 mb-3"
                    icon="ti ti-mail"
                />

                <x-form.controls.password
                    name="password"
                    label="كلمة المرور"
                    placeholder="أدخل كلمة المرور"
                    required
                    autocomplete="current-password"
                    col="col-12 mb-3"
                    icon="ti ti-lock"
                />

                <div class="tenant-login-options">
                    <div class="form-check d-flex align-items-center gap-2 mb-0">
                        <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                        <label class="form-check-label" for="remember_me">تذكرني في هذا الجهاز</label>
                    </div>

                    @if (tenant() && Route::has('domain.password.request'))
                        <a href="{{ tenant_route_name('password.request') }}" class="tenant-forgot-link">
                            نسيت كلمة المرور؟
                        </a>
                    @elseif (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="tenant-forgot-link">
                            نسيت كلمة المرور؟
                        </a>
                    @endif
                </div>

                <button class="btn-tenant-login" type="submit">
                    <span>تسجيل الدخول</span>
                    <i class="ti ti-arrow-left" aria-hidden="true"></i>
                </button>
            </form>
        </div>

        <div class="tenant-login-footer">
            <p class="mb-1">جميع الحقوق محفوظة 2026 ©</p>
            <a href="https://e-tec.sa" target="_blank" rel="noopener noreferrer" class="tenant-footer-link">
                <span>إتمام لتقنية نظم المعلومات</span>
                <img src="{{ asset('img/logo-footer.png') }}" alt="Logo" class="footer-logo">
            </a>
            <p class="mb-0 text-muted small">V.{{ config('app.version') }}</p>
        </div>
    </div>

    <!-- Left Side - Media & Brand Overview -->
    <div class="tenant-login-media-side">
        <div class="tenant-media-overlay"></div>

        <div class="tenant-media-content">
            <div class="tenant-media-header">
                <div class="tenant-header-line"></div>
                <div class="tenant-header-tagline">
                    <span>شريككم</span>
                    <span>في تطوير الفرص العقارية</span>
                </div>
            </div>

            <div class="tenant-media-body">
                <h1 class="tenant-media-title">
                    منصة رقمية متكاملة<br>
                    لإدارة المزادات العقارية
                </h1>
                <p class="tenant-media-desc">
                    نساعد شركاءنا على إدارة المزادات بكفاءة من خلال أدوات ذكية ومخرجات تسويقية احترافية.
                </p>
            </div>

            <div class="tenant-media-features">
                <div class="tenant-feature-card">
                    <div class="tenant-feature-icon">
                        <i class="ti ti-chart-bar" aria-hidden="true"></i>
                    </div>
                    <div class="tenant-feature-text">إدارة مزادات أكثر كفاءة</div>
                </div>

                <div class="tenant-feature-card">
                    <div class="tenant-feature-icon">
                        <i class="ti ti-file-text" aria-hidden="true"></i>
                    </div>
                    <div class="tenant-feature-text">مخرجات تسويقية جاهزة للاستخدام</div>
                </div>

                <div class="tenant-feature-card">
                    <div class="tenant-feature-icon">
                        <i class="ti ti-shield-check" aria-hidden="true"></i>
                    </div>
                    <div class="tenant-feature-text">بيانات دقيقة وموثوقة</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
