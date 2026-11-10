@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
    $isOtp = !empty($otpStep);
@endphp

@extends('tenant.layouts.layoutMaster')

@section('title', $isOtp ? 'التحقق من رمز الأمان' : 'نسيت كلمة المرور')

@section('page-style')
    @vite(['resources/assets/css/shared/auth-layout.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth-layout.js', 'resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    @include('shared.auth.forgot-password', [
        'otpStep' => $otpStep ?? false,
        'otpExpiresAt' => $otpExpiresAt ?? 0,
        'resendCount' => $resendCount ?? 0,
        'maxResends' => $maxResends ?? 3,
        'email' => $email ?? '',
        'emailAction' => tenant_route_name('password.email'),
        'verifyAction' => tenant_route_name('password.otp.verify'),
        'resendAction' => tenant_route_name('password.otp.resend'),
        'validationUrl' => tenant_route_name('form-validation'),
        'backUrl' => tenant_route_name('login'),
        'mediaTitle' => 'استعد وصولك الآمن | إلى حساب شركتك',
    ])
@endsection
