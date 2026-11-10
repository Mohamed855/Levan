@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
    $resetAction = tenant()
        ? tenant_route_name('password.store')
        : (Route::has('admin.password.store')
            ? route('admin.password.store')
            : route('password.store'));
    $resolvedEmail = old('email', $email ?? ($request->email ?? request('email')));
@endphp

@extends('tenant.layouts.layoutMaster')

@section('title', 'إعادة تعيين كلمة المرور')

@section('page-style')
    @vite(['resources/assets/css/shared/auth-layout.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth-layout.js', 'resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    @include('shared.auth.reset-password', [
        'resetAction' => $resetAction,
        'validationUrl' => tenant() ? tenant_route_name('form-validation') : route('admin.wizard-drafts.validate-step'),
        'backUrl' => tenant_route_name('login'),
        'resolvedEmail' => $resolvedEmail,
        'mediaDesc' => 'اختر كلمة مرور قوية ومعقدة لحماية حساب شركتك وبيانات عملائك ومزاداتك العقارية وفق أعلى معايير الأمان.',
    ])
@endsection
