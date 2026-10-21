@extends('layouts.auth')

@section('title', 'إعادة تعيين كلمة المرور')

@section('content')
    <x-shared.auth.split-layout
        form-title="إعادة تعيين كلمة المرور"
        form-subtitle="الرجاء إدخال بريدك الإلكتروني واختيار كلمة مرور جديدة قوية"
        :bg-image="asset('static/auth/backgrounds/auth-security-bg.jpg')"
        media-tagline="حماية الحساب | لوت LOT"
        media-title="تحديث وتأمين | كلمة المرور الخاصة بك"
        media-desc="اختر كلمة مرور قوية ومعقدة لحماية حساب شركتك وبيانات عملائك ومزاداتك العقارية وفق أعلى معايير الأمان."
        :features="[
            ['icon' => 'ti ti-key', 'text' => 'تشفير متقدم لكلمات المرور'],
            ['icon' => 'ti ti-shield-check', 'text' => 'حماية الحساب والبيانات'],
            ['icon' => 'ti ti-circle-check', 'text' => 'توافق مع معايير الأمان العالمية'],
        ]"
        :back-url="route('tenant.login')"
        back-text="العودة لتسجيل الدخول"
    >
        <form id="formAuthentication" method="POST" action="{{ route('tenant.password.store') }}" novalidate>
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <x-shared.form.controls.input
                name="email"
                label="البريد الإلكتروني"
                placeholder="أدخل البريد الإلكتروني"
                type="email"
                :value="old('email', $email)"
                required
                autocomplete="username"
                col="col-12 mb-3"
                icon="ti ti-mail"
            />

            <x-shared.form.controls.password
                name="password"
                id="password"
                label="كلمة المرور الجديدة"
                placeholder="أدخل كلمة المرور الجديدة"
                required
                autocomplete="new-password"
                col="col-12 mb-3"
                icon="ti ti-lock"
            />

            <x-shared.form.controls.password
                name="password_confirmation"
                id="password_confirmation"
                label="تأكيد كلمة المرور الجديدة"
                placeholder="أعد إدخال كلمة المرور للتأكيد"
                required
                autocomplete="new-password"
                col="col-12 mb-4"
                icon="ti ti-lock-check"
            />

            <button type="submit" class="btn-auth-primary mb-3">
                <span>إعادة تعيين كلمة المرور</span>
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </button>
        </form>
    </x-shared.auth.split-layout>
@endsection
