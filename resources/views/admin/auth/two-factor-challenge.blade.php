@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('admin.layouts.layoutMaster')

@section('title', 'التحقق بخطوتين - الإدارة المركزية')

@section('page-style')
    @vite(['resources/assets/css/shared/auth-layout.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth-layout.js'])
@endsection

@section('content')
    <x-auth.split-layout form-title="التحقق بخطوتين"
        form-subtitle="أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة لإكمال تسجيل الدخول للوحة الإدارة"
        :bg-image="asset('static/auth/backgrounds/auth-security-bg.jpg')"
        media-tagline="أمان الإدارة المركزية | Super Admin Security"
        media-title="طبقة حماية إضافية | لحسابات المشرفين والمنظومة"
        media-desc="تأمين العمليات الإدارية الحساسة والتحكم المركزي في النظام عبر بروتوكولات التحقق الثنائي المعتمدة عالمياً."
        :features="[
            ['icon' => 'ti ti-shield-check', 'text' => 'حماية متقدمة لحساب المشرف العام والمديرين'],
            ['icon' => 'ti ti-device-mobile', 'text' => 'تكامل موثوق مع تطبيقات المصادقة الثنائية (2FA)'],
            ['icon' => 'ti ti-key', 'text' => 'رموز استرداد احتياطية مشفرة وصارمة'],
        ]">
        @php
            $isRecovery = old('recovery_code') || $errors->has('recovery_code');
        @endphp

        <form id="twoStepsForm" action="{{ route('admin.two-factor.store') }}" method="POST" novalidate
            data-form-validate data-validation-url="{{ route('admin.form-validation') }}">
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
                <a href="{{ route('admin.two-factor.cancel') }}" class="auth-back-link">
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
