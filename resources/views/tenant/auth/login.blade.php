@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];

    $currentHost = request()->getHost();
    $centralDomains = array_map(function ($domain) {
        return str_replace(['http://', 'https://', 'www.'], '', $domain);
    }, config('tenancy.central_domains', []));

    $isCentralDomain = in_array($currentHost, $centralDomains) || in_array('www.' . $currentHost, $centralDomains);

    $loginAction = $isCentralDomain ? url('/' . tenant('id') . '/login') : url('/login');
@endphp

@extends('tenant.layouts.layoutMaster')

@section('title', 'تسجيل الدخول')

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/auth-layout.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth-layout.js', 'resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    <x-auth.split-layout form-title="مرحباً بكم" form-subtitle="سجل الدخول بحساب الشركة لاستخدام المنصة" :bg-image="asset('static/auth/backgrounds/tenant-login-bg.jpg')"
        media-tagline="شريككم في تطوير الفرص العقارية" media-title="منصة رقمية متكاملة | لإدارة المزادات العقارية"
        media-desc="نساعد شركاءنا على إدارة المزادات بكفاءة من خلال أدوات ذكية ومخرجات تسويقية احترافية." :features="[
            ['icon' => 'ti ti-chart-bar', 'text' => 'إدارة مزادات أكثر كفاءة'],
            ['icon' => 'ti ti-file-text', 'text' => 'مخرجات تسويقية جاهزة للاستخدام'],
            ['icon' => 'ti ti-shield-check', 'text' => 'بيانات دقيقة وموثوقة'],
        ]">
        <form id="formAuthentication" action="{{ $loginAction }}" method="POST" novalidate data-form-validate
            data-validation-url="{{ tenant_route_name('form-validation') }}">
            @csrf
            <x-form.controls.input name="email" label="البريد الإلكتروني أو رقم الجوال" placeholder="أدخل البريد الإلكتروني أو رقم الجوال"
                type="text" :value="old('email')" required autocomplete="username" col="col-12 mb-3"
                icon="ti ti-mail" />

            <x-form.controls.password name="password" label="كلمة المرور" placeholder="أدخل كلمة المرور" required
                autocomplete="current-password" col="col-12 mb-3" icon="ti ti-lock" />

            <div class="auth-options">
                <div class="form-check d-flex align-items-center gap-2 mb-0">
                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                    <label class="form-check-label" for="remember_me">تذكرني في هذا الجهاز</label>
                </div>

                @if (tenant() && Route::has('domain.password.request'))
                    <a href="{{ tenant_route_name('password.request') }}" class="auth-link">
                        نسيت كلمة المرور؟
                    </a>
                @elseif (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-link">
                        نسيت كلمة المرور؟
                    </a>
                @endif
            </div>

            <button class="btn-auth-primary" type="submit">
                <span>تسجيل الدخول</span>
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </button>
        </form>
    </x-auth.split-layout>
@endsection
