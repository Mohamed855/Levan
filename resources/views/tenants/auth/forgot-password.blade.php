@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'نسيت كلمة المرور')

@section('page-style')
    @vite(['resources/assets/css/shared/auth-layout.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth-layout.js'])
@endsection

@section('content')
    <x-auth.split-layout form-title="هل نسيت كلمة المرور؟"
        form-subtitle="أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور" :bg-image="asset('static/auth/backgrounds/auth-security-bg.jpg')"
        media-tagline="استعادة الحساب | ليفان العقارية" media-title="استعد وصولك الآمن | إلى حساب شركتك"
        media-desc="سنرسل لك رابطاً مشفراً عبر بريدك الإلكتروني يتيح لك إعادة تعيين كلمة المرور بسهولة ومتابعة أعمالك العقارية بأمان."
        :features="[
            ['icon' => 'ti ti-lock-check', 'text' => 'استرداد آمن وموثوق'],
            ['icon' => 'ti ti-mail-fast', 'text' => 'إرسال فوري لرابط الاستعادة'],
            ['icon' => 'ti ti-shield', 'text' => 'حماية تامة للبيانات والخصوصية'],
        ]" :back-url="tenant_route_name('login')" back-text="العودة لتسجيل الدخول">
        <form id="formAuthentication" method="POST" action="{{ tenant_route_name('password.email') }}" novalidate>
            @csrf

            <x-form.controls.input name="email" label="البريد الإلكتروني" placeholder="أدخل البريد الإلكتروني"
                type="email" :value="old('email')" required autofocus autocomplete="email" col="col-12 mb-4"
                icon="ti ti-mail" />

            <button class="btn-auth-primary mb-3" type="submit">
                <span>إرسال رابط إعادة التعيين</span>
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </button>
        </form>
    </x-auth.split-layout>
@endsection
