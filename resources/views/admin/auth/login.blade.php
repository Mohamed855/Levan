@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('admin.layouts.layoutMaster')

@section('title', 'تسجيل الدخول - الإدارة المركزية')

@section('page-style')
    @vite(['resources/assets/css/shared/auth-layout.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth-layout.js', 'resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    <x-auth.split-layout form-title="مرحباً بك مجدداً" form-subtitle="تسجيل الدخول للوحة تحكم الإدارة المركزية (Super Admin)"
        :bg-image="asset('static/auth/backgrounds/admin-login-bg.jpg')" media-tagline="بوابة الإدارة المركزية | Super Admin"
        media-title="منظومة الإدارة والتحكم الشامل | إدارة وتشغيل المنصة المركزية"
        media-desc="مركز التحكم القيادي الموحد لإدارة المشتركين والمنشآت، إدارة الخطط والاشتراكات، ومراقبة أداء وأمان المنظومة بدقة وكفاءة عالية."
        :features="[
            ['icon' => 'ti ti-server-cog', 'text' => 'إدارة شاملة للمنشآت والمستأجرين (Tenants)'],
            ['icon' => 'ti ti-chart-arrows', 'text' => 'مؤشرات أداء وتقارير تشغيلية مركزية'],
            ['icon' => 'ti ti-shield-lock', 'text' => 'حوكمة وأمان ورقابة صلاحيات متقدمة'],
        ]">
        <form id="formAuthentication" action="{{ route('admin.login') }}" method="POST" novalidate data-form-validate
            data-validation-url="{{ route('admin.form-validation') }}">
            @csrf
            <x-form.controls.input name="email" label="البريد الإلكتروني" placeholder="أدخل البريد الإلكتروني"
                type="email" :value="old('email')" required autocomplete="username" col="col-12 mb-3"
                icon="ti ti-mail" />

            <x-form.controls.password name="password" label="كلمة المرور" placeholder="أدخل كلمة المرور" required
                autocomplete="current-password" col="col-12 mb-3" icon="ti ti-lock" />

            <div class="auth-options">
                <div class="form-check d-flex align-items-center gap-2 mb-0">
                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                    <label class="form-check-label" for="remember_me">تذكرني في هذا الجهاز</label>
                </div>

                <a href="{{ route('admin.password.request') }}" class="auth-link">
                    نسيت كلمة المرور؟
                </a>
            </div>

            <button class="btn-auth-primary" type="submit">
                <span>تسجيل الدخول</span>
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </button>
        </form>
    </x-auth.split-layout>
@endsection
