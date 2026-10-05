@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'إنشاء حساب جديد')

@section('page-style')
    @vite(['resources/assets/css/shared/auth-layout.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth-layout.js'])
@endsection

@section('content')
    <x-auth.split-layout form-title="إنشاء حساب جديد" form-subtitle="ابدأ رحلتك معنا وسجّل حساب شركتك الآن"
        form-max-width="480px" :bg-image="asset('static/auth/backgrounds/auth-register-bg.jpg')" media-tagline="انضم إلى شركائنا | ليفان العقارية"
        media-title="ابدأ رحلتك في إدارة وتطوير | المزادات العقارية"
        media-desc="منظومة رقمية ذكية تمكّن الشركات العقارية من طرح وإدارة المزادات، إدارة فرق العمل، وإصدار التقارير بكل كفاءة واحترافية."
        :features="[
            ['icon' => 'ti ti-rocket', 'text' => 'انطلاق فوري وسهل'],
            ['icon' => 'ti ti-users', 'text' => 'إدارة فرق العمل والصلاحيات'],
            ['icon' => 'ti ti-device-laptop', 'text' => 'منظومة سحابية متكاملة'],
        ]">
        <form method="POST" action="{{ tenant_route_name('register') }}" autocomplete="off" novalidate>
            @csrf

            <x-form.controls.input name="name" label="الاسم الكامل" placeholder="أدخل الاسم الكامل" :value="old('name')"
                required autofocus autocomplete="name" col="col-12 mb-3" icon="ti ti-user" />

            <x-form.controls.input name="email" label="البريد الإلكتروني" placeholder="أدخل البريد الإلكتروني"
                type="email" :value="old('email')" required autocomplete="username" col="col-12 mb-3" icon="ti ti-mail" />

            <x-form.controls.password name="password" label="كلمة المرور" placeholder="أدخل كلمة مرور قوية" required
                autocomplete="new-password" col="col-12 mb-3" icon="ti ti-lock" />

            <x-form.controls.password name="password_confirmation" label="تأكيد كلمة المرور"
                placeholder="أعد إدخال كلمة المرور" required autocomplete="new-password" col="col-12 mb-4"
                icon="ti ti-lock-check" />

            <button class="btn-auth-primary mb-3" type="submit">
                <span>إنشاء الحساب</span>
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </button>

            <div class="text-center">
                <span class="text-muted">لديك حساب بالفعل؟</span>
                <a href="{{ tenant_route_name('login') }}" class="auth-link ms-1">
                    تسجيل الدخول
                </a>
            </div>
        </form>
    </x-auth.split-layout>
@endsection
