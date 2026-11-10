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
    use App\Helpers\Admin\Settings\AdminSettingsHelper;
    use App\Helpers\Tenants\Settings\SettingsHelper;

    if (! $logoUrl) {
        $defaultLogo = asset('static/brand/logos/lot/trans-profile.png');
        $logoUrl = $defaultLogo;

        try {
            if (function_exists('tenant') && tenant()) {
                $tenantLogo = class_exists(SettingsHelper::class) ? SettingsHelper::get('image') : null;
                if (! empty($tenantLogo) && ! in_array($tenantLogo, ['default-office.png', 'image/demo-office.jpg'], true)) {
                    $logoUrl = tasset($tenantLogo, 'static/brand/logos/lot/trans-profile.png');
                }
            } elseif (class_exists(AdminSettingsHelper::class)) {
                $adminLogo = AdminSettingsHelper::get('image');
                $normalizedAdminLogo = ltrim((string) $adminLogo, '/');
                if (! empty($adminLogo)
                    && ! in_array($adminLogo, ['default-office.png', 'image/demo-office.jpg'], true)
                    && \Illuminate\Support\Facades\Storage::disk('public')->exists($normalizedAdminLogo)) {
                    $logoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($normalizedAdminLogo);
                }
            }
        } catch (\Throwable) {
            $logoUrl = $defaultLogo;
        }
    }
@endphp

<div class="auth-split-container">
    <!-- Right Side - Form -->
    <div class="auth-form-side">
        <!-- Brand Watermark -->
        <img class="auth-brand-watermark" src="{{ asset('static/brand/logos/lot/trans-icon.png') }}" alt="" aria-hidden="true">

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
