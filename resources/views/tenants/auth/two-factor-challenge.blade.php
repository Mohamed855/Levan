@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'التحقق بخطوتين')

@section('page-style')
    @vite(['resources/assets/css/shared/auth-layout.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth-layout.js'])
@endsection

@section('content')
    <x-auth.split-layout form-title="التحقق بخطوتين"
        form-subtitle="أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة لإكمال تسجيل الدخول" :bg-image="asset('static/auth/backgrounds/auth-security-bg.jpg')"
        media-tagline="أمان الحساب | ليفان العقارية" media-title="طبقة حماية إضافية | لحساب شركتك"
        media-desc="نضمن سلامة بياناتك وعملياتك من خلال بروتوكولات التحقق الثنائي المعتمدة عالمياً." :features="[
            ['icon' => 'ti ti-shield-check', 'text' => 'حماية فائقة المستوى'],
            ['icon' => 'ti ti-device-mobile', 'text' => 'تكامل مع تطبيقات المصادقة'],
            ['icon' => 'ti ti-key', 'text' => 'رموز استرداد احتياطية'],
        ]">
        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 mb-4 text-center" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <form id="twoStepsForm" action="{{ tenant_route_name('tenants.two-factor.store') }}" method="POST">
            @csrf

            <div class="mb-4" id="codeInputsWrapper">
                <label class="form-label text-center d-block mb-3" id="codeLabel">أدخل الرمز الأمني المكون من 6
                    أرقام</label>
                <div class="auth-otp-wrapper" dir="ltr">
                    <input type="tel" class="form-control auth-otp-input" maxlength="1" autofocus autocomplete="off">
                    <input type="tel" class="form-control auth-otp-input" maxlength="1" autocomplete="off">
                    <input type="tel" class="form-control auth-otp-input" maxlength="1" autocomplete="off">
                    <input type="tel" class="form-control auth-otp-input" maxlength="1" autocomplete="off">
                    <input type="tel" class="form-control auth-otp-input" maxlength="1" autocomplete="off">
                    <input type="tel" class="form-control auth-otp-input" maxlength="1" autocomplete="off">
                </div>
                <input type="hidden" name="code" id="otpHidden" />
            </div>

            <div class="mb-4 d-none" id="recoveryWrapper">
                <x-form.controls.input name="recovery_code" id="recovery_code" label="كود الاسترجاع"
                    placeholder="أدخل كود الاسترجاع الاحتياطي" col="col-12" icon="ti ti-key" />
            </div>

            <button class="btn-auth-primary mb-3" type="submit">
                <span>تأكيد الرمز</span>
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </button>

            <div class="text-center mb-3">
                <a href="javascript:void(0);" id="toggleRecoveryLink" class="auth-link">
                    استخدام كود استرجاع بدلاً من ذلك
                </a>
            </div>

            <div class="text-center">
                <a href="{{ tenant_route_name('tenants.two-factor.cancel') }}" class="auth-back-link">
                    <i class="ti ti-arrow-right me-1"></i>
                    الرجوع لتسجيل الدخول بحساب آخر
                </a>
            </div>
        </form>
    </x-auth.split-layout>
@endsection
