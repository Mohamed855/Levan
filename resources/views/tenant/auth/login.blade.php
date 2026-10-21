@extends('layouts.auth')

@section('title', 'تسجيل الدخول')

@section('content')
    <x-shared.auth.split-layout
        form-title="مرحباً بكم"
        form-subtitle="سجل الدخول بحساب الشركة لاستخدام المنصة"
        :bg-image="asset('static/auth/backgrounds/tenant-login-bg.jpg')"
        media-tagline="شريككم في تطوير الفرص العقارية"
        media-title="منصة رقمية متكاملة | لإدارة المزادات العقارية"
        media-desc="نساعد شركاءنا على إدارة المزادات بكفاءة من خلال أدوات ذكية ومخرجات تسويقية احترافية."
        :features="[
            ['icon' => 'ti ti-chart-bar', 'text' => 'إدارة مزادات أكثر كفاءة'],
            ['icon' => 'ti ti-file-text', 'text' => 'مخرجات تسويقية جاهزة للاستخدام'],
            ['icon' => 'ti ti-shield-check', 'text' => 'بيانات دقيقة وموثوقة'],
        ]"
    >
        <form id="formAuthentication" action="{{ route('tenant.login.store') }}" method="POST" novalidate>
            @csrf

            <x-shared.form.controls.input name="email" label="البريد الإلكتروني أو رقم الجوال" placeholder="أدخل البريد الإلكتروني أو رقم الجوال"
                type="text" :value="old('email')" required autocomplete="username" col="col-12 mb-3" icon="ti ti-mail" />

            <x-shared.form.controls.password name="password" label="كلمة المرور" placeholder="أدخل كلمة المرور" required
                autocomplete="current-password" col="col-12 mb-3" icon="ti ti-lock" />

            <div class="auth-options">
                <div class="form-check d-flex align-items-center gap-2 mb-0">
                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember" @checked(old('remember'))>
                    <label class="form-check-label" for="remember_me">تذكرني في هذا الجهاز</label>
                </div>
            </div>

            <button class="btn-auth-primary" type="submit"><span>تسجيل الدخول</span><i class="ti ti-arrow-left" aria-hidden="true"></i></button>
        </form>
    </x-shared.auth.split-layout>
@endsection
