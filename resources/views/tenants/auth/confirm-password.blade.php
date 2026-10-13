@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'تأكيد كلمة المرور')

@section('page-style')
    @vite(['resources/assets/css/shared/auth-layout.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth-layout.js', 'resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    <x-auth.split-layout form-title="تأكيد كلمة المرور" form-subtitle="يرجى تأكيد كلمة المرور الخاصة بك قبل المتابعة"
        :bg-image="asset('static/auth/backgrounds/auth-security-bg.jpg')" media-tagline="حماية الحساب | لوت LOT" media-title="تحقق من هويتك | لمواصلة العملية"
        media-desc="لضمان أمان حسابك، نطلب منك تأكيد كلمة المرور قبل الوصول إلى هذه الصفحة الحساسة." :features="[
            ['icon' => 'ti ti-shield-lock', 'text' => 'حماية البيانات الحساسة'],
            ['icon' => 'ti ti-lock-check', 'text' => 'التحقق من الهوية'],
            ['icon' => 'ti ti-user-check', 'text' => 'وصول آمن ومُتحكَّم به'],
        ]">
        <form method="POST" action="{{ tenant_route_name('password.confirm') }}" novalidate data-form-validate
            data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
            @csrf

            <x-form.controls.password name="password" id="password" label="كلمة المرور" placeholder="أدخل كلمة المرور الحالية"
                required autocomplete="current-password" col="col-12 mb-4" icon="ti ti-lock" />

            <button type="submit" class="btn-auth-primary">
                <span>تأكيد</span>
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </button>
        </form>
    </x-auth.split-layout>
@endsection
