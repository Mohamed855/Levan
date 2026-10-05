@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'تأكيد البريد الإلكتروني')

@section('page-style')
    @vite(['resources/assets/css/shared/auth-layout.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth-layout.js'])
@endsection

@section('content')
    <x-auth.split-layout form-title="تحقق من بريدك الإلكتروني"
        form-subtitle="شكراً لتسجيلك! قبل البدء، يرجى التحقق من بريدك الإلكتروني بالنقر على الرابط الذي أرسلناه إليك."
        :bg-image="asset('img/tenant-login-bg.jpg')" media-tagline="التحقق من الحساب | ليفان العقارية" media-title="خطوة أخيرة | لتفعيل حسابك"
        media-desc="أرسلنا إليك بريداً إلكترونياً يحتوي على رابط التحقق. انقر عليه لتفعيل حسابك والبدء في استخدام المنصة."
        :features="[
            ['icon' => 'ti ti-mail-check', 'text' => 'تحقق آمن عبر البريد الإلكتروني'],
            ['icon' => 'ti ti-shield-check', 'text' => 'حماية الحساب من الوصول غير المصرح'],
            ['icon' => 'ti ti-rocket', 'text' => 'انطلق فوراً بعد التحقق'],
        ]" :show-footer="true">
        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success py-2 px-3 mb-4 text-center" role="alert">
                تم إرسال رابط تحقق جديد إلى عنوان البريد الإلكتروني الذي قدمته أثناء التسجيل.
            </div>
        @endif

        <div class="d-flex flex-column gap-3">
            <form method="POST" action="{{ tenant_route_name('verification.send') }}">
                @csrf
                <button type="submit" class="btn-auth-primary">
                    <span>إعادة إرسال بريد التحقق</span>
                    <i class="ti ti-mail-forward" aria-hidden="true"></i>
                </button>
            </form>

            <form method="POST" action="{{ tenant_route_name('logout') }}" class="text-center">
                @csrf
                <button type="submit" class="auth-back-link" style="background: none; border: none; cursor: pointer;">
                    <i class="ti ti-arrow-right me-1"></i>
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </x-auth.split-layout>
@endsection
