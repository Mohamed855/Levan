@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
    $resetAction = tenant()
        ? tenant_route_name('password.store')
        : (Route::has('admin.password.store')
            ? route('admin.password.store')
            : route('password.store'));
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
    <x-auth.split-layout form-title="إعادة تعيين كلمة المرور"
        form-subtitle="الرجاء إدخال بريدك الإلكتروني واختيار كلمة مرور جديدة قوية" :bg-image="asset('static/auth/backgrounds/auth-security-bg.jpg')"
        media-tagline="حماية الحساب | لوت LOT" media-title="تحديث وتأمين | كلمة المرور الخاصة بك"
        media-desc="اختر كلمة مرور قوية ومعقدة لحماية حساب شركتك وبيانات عملائك ومزاداتك العقارية وفق أعلى معايير الأمان."
        :features="[
            ['icon' => 'ti ti-key', 'text' => 'تشفير متقدم لكلمات المرور'],
            ['icon' => 'ti ti-shield-check', 'text' => 'حماية الحساب والبيانات'],
            ['icon' => 'ti ti-circle-check', 'text' => 'توافق مع معايير الأمان العالمية'],
        ]" :back-url="tenant_route_name('login')" back-text="العودة لتسجيل الدخول">
        <form method="POST" action="{{ $resetAction }}" novalidate data-form-validate
            data-validation-url="{{ tenant() ? tenant_route_name('form-validation') : route('admin.wizard-drafts.validate-step') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $request->token ?? request('token') }}">
            @if (isset($request->tenant_id) || request('tenant_id'))
                <input type="hidden" name="tenant_id" value="{{ $request->tenant_id ?? request('tenant_id') }}">
            @endif

            <x-form.controls.input name="email" label="البريد الإلكتروني" placeholder="أدخل البريد الإلكتروني"
                type="email" :value="old('email', $request->email ?? request('email'))" required autocomplete="username" col="col-12 mb-3"
                icon="ti ti-mail" />

            <x-form.controls.password name="password" id="password" label="كلمة المرور الجديدة"
                placeholder="أدخل كلمة المرور الجديدة" required autocomplete="new-password" col="col-12 mb-2"
                icon="ti ti-lock" />

            <x-form.controls.password name="password_confirmation" id="password_confirmation"
                label="تأكيد كلمة المرور الجديدة" placeholder="أعد إدخال كلمة المرور للتأكيد" required
                autocomplete="new-password" col="col-12 mb-2" icon="ti ti-lock-check" />
            <button type="submit" class="btn-auth-primary mb-3">
                <span>إعادة تعيين كلمة المرور</span>
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </button>
        </form>
    </x-auth.split-layout>
@endsection
