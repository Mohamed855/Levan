@extends('layouts.auth')

@section('title', 'نسيت كلمة المرور')

@section('content')
    <x-shared.auth.split-layout
        form-title="هل نسيت كلمة المرور؟"
        form-subtitle="أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور"
        :bg-image="asset('static/auth/backgrounds/auth-security-bg.jpg')"
        media-tagline="استعادة الحساب | لوت LOT"
        media-title="استعد وصولك الآمن | إلى حساب شركتك"
        media-desc="سنرسل لك رابطاً مشفراً عبر بريدك الإلكتروني يتيح لك إعادة تعيين كلمة المرور بسهولة ومتابعة أعمالك العقارية بأمان."
        :features="[
            ['icon' => 'ti ti-lock-check', 'text' => 'استرداد آمن وموثوق'],
            ['icon' => 'ti ti-mail-fast', 'text' => 'إرسال فوري لرابط الاستعادة'],
            ['icon' => 'ti ti-shield', 'text' => 'حماية تامة للبيانات والخصوصية'],
        ]"
        :back-url="route('tenant.login')"
        back-text="العودة لتسجيل الدخول"
    >
        <form id="formAuthentication" method="POST" action="{{ route('tenant.password.email') }}" novalidate>
            @csrf

            <x-shared.form.controls.input
                name="email"
                label="البريد الإلكتروني"
                placeholder="أدخل البريد الإلكتروني"
                type="email"
                :value="old('email')"
                required
                autocomplete="email"
                col="col-12 mb-4"
                icon="ti ti-mail"
            />

            <button class="btn-auth-primary mb-3" type="submit">
                <span>إرسال رابط إعادة التعيين</span>
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </button>
        </form>
    </x-shared.auth.split-layout>
@endsection
