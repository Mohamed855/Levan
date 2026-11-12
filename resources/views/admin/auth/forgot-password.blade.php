@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
    $isOtp = !empty($otpStep);
@endphp

@extends('admin.layouts.layoutMaster')

@section('title', ($isOtp ? 'التحقق من رمز الأمان' : 'استعادة حساب الإدارة') . ' - الإدارة المركزية')

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
        'formTitle' => $isOtp ? 'التحقق من رمز الأمان' : 'استعادة حساب المشرف العام',
        'formSubtitle' => $isOtp
            ? 'أدخل رمز التحقق (OTP) المرسل إلى بريدك الإلكتروني لإعادة تعيين كلمة المرور'
            : 'أدخل البريد الإلكتروني المعتمد للمشرف العام لاستلام رمز التحقق (OTP) واستعادة الوصول',
        'mediaTagline' => 'بوابة الإدارة المركزية | Super Admin',
        'mediaTitle' => 'استعادة الوصول الآمن | إلى لوحة الإدارة المركزية',
        'mediaDesc' => 'بروتوكولات أمان صارمة تضمن استعادة وصول المشرفين والمديرين إلى لوحة التحكم المركزية بسرعة وموثوقية عبر رمز تحقق مباشر دون الحاجة إلى روابط خارجية.',
        'features' => [
            ['icon' => 'ti ti-shield-lock', 'text' => 'حماية بيانات وصلاحيات المنظومة المركزية'],
            ['icon' => 'ti ti-key', 'text' => 'تحقق مشفر وفوري عبر رمز أمان مباشر'],
            ['icon' => 'ti ti-user-shield', 'text' => 'معايير أمان مشددة لحسابات الإدارة العليا'],
        ],
    ])
@endsection
