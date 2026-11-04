@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('tenant.layouts.layoutMaster')

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
        media-tagline="أمان الحساب | لوت LOT" media-title="طبقة حماية إضافية | لحساب شركتك"
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

        @php
            $isRecovery = old('recovery_code') || $errors->has('recovery_code');
        @endphp

        <form id="twoStepsForm" action="{{ tenant_route_name('tenants.two-factor.store') }}" method="POST" novalidate
            data-form-validate data-validation-url="{{ tenant_route_name('form-validation') }}">
            @csrf

            <div class="mb-4 {{ $isRecovery ? 'd-none' : '' }}" id="codeInputsWrapper">
                <x-form.controls.input name="code" id="code" label="أدخل الرمز الأمني المكون من 6 أرقام"
                    placeholder="XXXXXX" inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                    class="otp-input text-center fw-bold" style="font-size: 24px; letter-spacing: 6px;"
                    :disabled="$isRecovery" col="col-12" icon="ti ti-shield-check" />
            </div>

            <div class="mb-4 {{ $isRecovery ? '' : 'd-none' }}" id="recoveryWrapper">
                <x-form.controls.input name="recovery_code" id="recovery_code" label="كود الاسترجاع"
                    placeholder="أدخل كود الاسترجاع الاحتياطي" col="col-12" icon="ti ti-key"
                    :disabled="!$isRecovery" />
            </div>

            <button class="btn-auth-primary mb-3" type="submit" id="submitBtn">
                <span>تأكيد الرمز</span>
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </button>

            <div class="auth-challenge-links-row d-flex justify-content-between align-items-center gap-2 mt-4">
                <a href="{{ tenant_route_name('tenants.two-factor.cancel') }}" class="auth-back-link">
                    <i class="ti ti-arrow-right me-1"></i>
                    <span>الرجوع لتسجيل الدخول بحساب آخر</span>
                </a>

                <a href="javascript:void(0);" id="toggleRecoveryLink" class="auth-back-link auth-toggle-link">
                    <i class="ti {{ $isRecovery ? 'ti-shield-check' : 'ti-key' }} me-1" id="toggleRecoveryIcon"></i>
                    <span id="toggleRecoveryText">{{ $isRecovery ? 'استخدام رمز المصادقة' : 'استخدام كود استرجاع' }}</span>
                </a>
            </div>
        </form>
    </x-auth.split-layout>
@endsection
