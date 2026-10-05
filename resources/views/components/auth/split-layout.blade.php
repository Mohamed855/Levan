@props([
    'logoUrl' => null,
    'formTitle' => '',
    'formSubtitle' => '',
    'status' => null,
    'formMaxWidth' => '440px',
    'bgImage' => asset('static/auth/backgrounds/tenant-login-bg.jpg'),
    'mediaTagline' => 'شريككم في تطوير الفرص العقارية',
    'mediaTitle' => "منصة رقمية متكاملة\nلإدارة المزادات العقارية",
    'mediaDesc' => 'نساعد شركاءنا على إدارة المزادات بكفاءة من خلال أدوات ذكية ومخرجات تسويقية احترافية.',
    'features' => [['icon' => 'ti ti-chart-bar', 'text' => 'إدارة مزادات أكثر كفاءة'], ['icon' => 'ti ti-file-text', 'text' => 'مخرجات تسويقية جاهزة للاستخدام'], ['icon' => 'ti ti-shield-check', 'text' => 'بيانات دقيقة وموثوقة']],
    'showFooter' => true,
    'backUrl' => null,
    'backText' => 'العودة لتسجيل الدخول',
])

@php
    use App\Helpers\Tenants\Settings\SettingsHelper;

    if (!$logoUrl) {
        $tenantLogo =
            class_exists(SettingsHelper::class) && function_exists('tenant') && tenant()
                ? SettingsHelper::get('image')
                : null;
        $logoUrl = $tenantLogo ? tasset($tenantLogo) : asset('static/brand/logos/logo.svg');
    }
@endphp

<div class="auth-split-container">
    <!-- Right Side - Form -->
    <div class="auth-form-side">
        <!-- Brand Watermark -->
        <svg class="auth-brand-watermark" viewBox="0 0 34 34" fill="none" xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true">
            <path
                d="M16.917 11.7836V0H20.7068V6.23046C20.6877 7.42477 20.3185 8.58728 19.6449 9.57423C18.9713 10.5612 18.0229 11.3293 16.917 11.7836Z"
                fill="currentColor" />
            <path
                d="M5.41665 0.150299H0.0478516L10.4022 10.491C11.2609 11.3225 12.3448 11.8841 13.5199 12.1064C14.6949 12.3287 15.9094 12.2018 17.0129 11.7415L5.41665 0.150299Z"
                fill="currentColor" />
            <path
                d="M17.1219 22.2164V34H13.332V27.7695C13.3509 26.5752 13.72 25.4125 14.3936 24.4255C15.0672 23.4386 16.0158 22.6705 17.1219 22.2164Z"
                fill="currentColor" />
            <path
                d="M28.6316 33.8497H33.9883L23.6359 23.509C22.7774 22.6773 21.6935 22.1155 20.5184 21.8932C19.3433 21.6709 18.1288 21.798 17.0253 22.2585L28.6316 33.8497Z"
                fill="currentColor" />
            <path
                d="M11.7969 17.1022H0V13.3166H6.23751C7.43323 13.3355 8.59717 13.7042 9.58528 14.377C10.5734 15.0499 11.3423 15.9974 11.7969 17.1022Z"
                fill="currentColor" />
            <path
                d="M0.150391 28.5992V33.9499L10.5028 23.6092C11.335 22.7514 11.8971 21.6687 12.1196 20.4951C12.3421 19.3214 12.2153 18.1084 11.7547 17.006L0.150391 28.5992Z"
                fill="currentColor" />
            <path
                d="M22.2412 16.8978H34.0381V20.6834H27.8006C26.6049 20.6642 25.4411 20.2954 24.4531 19.6226C23.465 18.9498 22.696 18.0025 22.2412 16.8978Z"
                fill="currentColor" />
            <path
                d="M33.8876 5.41082V0.0480957L23.5352 10.3908C22.7024 11.2482 22.1398 12.3309 21.9172 13.5047C21.6947 14.6785 21.822 15.8918 22.2833 16.994L33.8876 5.41082Z"
                fill="currentColor" />
        </svg>

        <div class="auth-form-inner" style="max-width: {{ $formMaxWidth }};">
            <div class="auth-logo-wrapper">
                <img src="{{ $logoUrl }}" alt="Logo" class="auth-logo-img">
            </div>

            @if ($formTitle)
                <h2 class="auth-form-title">{{ $formTitle }}</h2>
            @endif

            @if ($formSubtitle)
                <p class="auth-form-subtitle">{{ $formSubtitle }}</p>
            @endif

            @if ($status || session('status'))
                <div class="alert alert-success py-2 px-3 mb-4 text-center" role="alert">
                    {{ $status ?? session('status') }}
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success py-2 px-3 mb-4 text-center" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger py-2 px-3 mb-4 text-center" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}

            @if ($backUrl)
                <div class="text-center mt-4">
                    <a href="{{ $backUrl }}" class="auth-back-link">
                        <i class="ti ti-arrow-right me-1"></i>
                        {{ $backText }}
                    </a>
                </div>
            @endif
        </div>

        @if ($showFooter)
            <div class="auth-form-footer">
                <p class="mb-1">جميع الحقوق محفوظة 2026 ©</p>
                <a href="https://e-tec.sa" target="_blank" rel="noopener noreferrer" class="auth-footer-link">
                    <span>إتمام لتقنية نظم المعلومات</span>
                    <img src="{{ asset('static/brand/logos/logo-footer.png') }}" alt="Logo" class="footer-logo">
                </a>
                <p class="mb-0 text-muted small">V.{{ config('app.version') }}</p>
            </div>
        @endif
    </div>

    <!-- Left Side - Media & Brand Overview -->
    <div class="auth-media-side" style="background-image: url('{{ $bgImage }}');">
        <div class="auth-media-overlay"></div>

        <div class="auth-media-content">
            <div class="auth-media-header">
                <div class="auth-header-line"></div>
                <div class="auth-header-tagline">
                    {!! nl2br(e($mediaTagline)) !!}
                </div>
            </div>

            <div class="auth-media-body">
                <h1 class="auth-media-title">
                    {!! nl2br(e($mediaTitle)) !!}
                </h1>
                <p class="auth-media-desc">
                    {!! nl2br(e($mediaDesc)) !!}
                </p>
            </div>

            @if (!empty($features))
                <div class="auth-media-features">
                    @foreach ($features as $feature)
                        <div class="auth-feature-card">
                            <div class="auth-feature-icon">
                                <i class="{{ $feature['icon'] }}" aria-hidden="true"></i>
                            </div>
                            <div class="auth-feature-text">{{ $feature['text'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
