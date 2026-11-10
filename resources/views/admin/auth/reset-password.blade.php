@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
    $resolvedEmail = old('email', $email ?? ($request->email ?? request('email')));
@endphp

@extends('admin.layouts.layoutMaster')

@section('title', 'إعادة تعيين كلمة المرور - الإدارة')

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
        'mediaTagline' => 'الإدارة المركزية | لوت LOT',
        'mediaDesc' => 'اختر كلمة مرور قوية ومعقدة لحماية حسابك الإداري وصلاحيات المنظومة المركزية وفق أعلى معايير الأمان.',
    ])
@endsection
