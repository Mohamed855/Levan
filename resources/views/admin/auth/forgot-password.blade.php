@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
    $isOtp = !empty($otpStep);
@endphp

@extends('admin.layouts.layoutMaster')

@section('title', ($isOtp ? 'التحقق من رمز الأمان' : 'نسيت كلمة المرور') . ' - الإدارة')

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
        'emailAction' => route('admin.password.email'),
        'verifyAction' => route('admin.password.otp.verify'),
        'resendAction' => route('admin.password.otp.resend'),
        'validationUrl' => route('admin.form-validation'),
        'backUrl' => route('admin.login'),
        'mediaTagline' => 'الإدارة المركزية | لوت LOT',
        'mediaTitle' => 'استعد وصولك الآمن | إلى لوحة الإدارة المركزية',
        'mediaDesc' => 'نظام أمان متطور يتيح لك استعادة حسابك الإداري بسرعة وأمان عبر رمز تحقق مباشر دون الحاجة إلى روابط خارجية.',
    ])
@endsection
