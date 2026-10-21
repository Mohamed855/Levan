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
    'features' => [],
    'showFooter' => true,
    'backUrl' => null,
    'backText' => 'العودة لتسجيل الدخول',
])

@php
    $logoUrl ??= asset('static/brand/logos/lot/trans-profile.png');
@endphp

<div class="auth-split-container">
    <div class="auth-form-side">
        <img class="auth-brand-watermark" src="{{ asset('static/brand/logos/lot/trans-icon.png') }}" alt="" aria-hidden="true">

        <div class="auth-form-inner" style="max-width: {{ $formMaxWidth }};">
            <div class="auth-logo-wrapper">
                <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}" class="auth-logo-img">
            </div>

            @if ($formTitle)
                <h1 class="auth-form-title">{{ $formTitle }}</h1>
            @endif

            @if ($formSubtitle)
                <p class="auth-form-subtitle">{{ $formSubtitle }}</p>
            @endif

            @if ($status ?? session('status'))
                <div class="alert alert-success py-2 px-3 mb-4 text-center" role="alert">
                    {{ $status ?? session('status') }}
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success py-2 px-3 mb-4 text-center" role="alert">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger py-2 px-3 mb-4 text-center" role="alert">{{ session('error') }}</div>
            @endif

            {{ $slot }}

            @if ($backUrl)
                <div class="text-center mt-4">
                    <a href="{{ $backUrl }}" class="auth-back-link"><i class="ti ti-arrow-right me-1"></i>{{ $backText }}</a>
                </div>
            @endif
        </div>

        @if ($showFooter)
            <div class="auth-form-footer">
                <p class="mb-1">جميع الحقوق محفوظة {{ now()->year }} ©</p>
                <a href="https://e-tec.sa" target="_blank" rel="noopener noreferrer" class="auth-footer-link">
                    <span>إتمام لتقنية نظم المعلومات</span>
                    <img src="{{ asset('static/brand/logos/logo-footer.png') }}" alt="إتمام" class="footer-logo">
                </a>
                <p class="mb-0 text-muted small">V.{{ config('app.version') }}</p>
            </div>
        @endif
    </div>

    <div class="auth-media-side" style="background-image: url('{{ $bgImage }}');">
        <div class="auth-media-overlay"></div>
        <div class="auth-media-content">
            <div class="auth-media-header">
                <div class="auth-header-line"></div>
                <div class="auth-header-tagline">{!! nl2br(e($mediaTagline)) !!}</div>
            </div>
            <div class="auth-media-body">
                <h1 class="auth-media-title">{!! nl2br(e($mediaTitle)) !!}</h1>
                <p class="auth-media-desc">{!! nl2br(e($mediaDesc)) !!}</p>
            </div>
            @if ($features)
                <div class="auth-media-features">
                    @foreach ($features as $feature)
                        <div class="auth-feature-card">
                            <div class="auth-feature-icon"><i class="{{ $feature['icon'] }}" aria-hidden="true"></i></div>
                            <div class="auth-feature-text">{{ $feature['text'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
