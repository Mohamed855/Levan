@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
    $resolvedEmail = old('email', $email ?? ($request->email ?? request('email')));
@endphp

@extends('admin.layouts.layoutMaster')

@section('title', 'تعيين كلمة المرور - الإدارة المركزية')

@section('page-style')
    @vite(['resources/assets/css/shared/auth-layout.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth-layout.js', 'resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    @include('shared.auth.reset-password', [
        'resetAction' => route('admin.password.reset.store'),
        'validationUrl' => route('admin.form-validation'),
        'backUrl' => route('admin.login'),
        'resolvedEmail' => $resolvedEmail,
        'formTitle' => 'تعيين كلمة مرور جديدة',
        'formSubtitle' => 'أدخل كلمة مرور قوية ومعقدة لحساب المشرف العام في الإدارة المركزية',
        'mediaTagline' => 'بوابة الإدارة المركزية | Super Admin',
        'mediaTitle' => 'تأمين وصول المشرف العام | إلى النظام المركزي',
        'mediaDesc' => 'اختر كلمة مرور قوية ومعقدة لحماية حساب المشرف العام وصلاحيات التحكم المركزي في المنظومة وفق أعلى معايير الأمان.',
        'features' => [
            ['icon' => 'ti ti-lock-check', 'text' => 'معايير تشفير متقدمة لكلمات مرور المشرفين'],
            ['icon' => 'ti ti-shield-cog', 'text' => 'تأمين كامل للصلاحيات والإعدادات المركزية'],
            ['icon' => 'ti ti-fingerprint', 'text' => 'حماية مشددة ضد الوصول غير المصرح به'],
        ],
    ])
@endsection
