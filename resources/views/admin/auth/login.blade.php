@extends('layouts.auth')

@section('title', 'تسجيل الدخول - الإدارة')

@section('content')
    <x-shared.auth.split-layout
        form-title="مرحباً بك مجدداً"
        form-subtitle="تسجيل الدخول للوحة تحكم الإدارة المركزية"
        :bg-image="asset('static/auth/backgrounds/admin-login-bg.jpg')"
        media-tagline="الإدارة المركزية | لوت LOT"
        media-title="منظومة الإدارة والتحكم الشامل | للمنصة العقارية"
        media-desc="أدوات حصرية للإدارة العامة لمتابعة الشركات العقارية، إدارة التراخيص والاشتراكات، والتحكم في إعدادات المنظومة بدقة وكفاءة عالية."
        :features="[
            ['icon' => 'ti ti-building-community', 'text' => 'إدارة الشركات والمستأجرين'],
            ['icon' => 'ti ti-chart-arrows', 'text' => 'مؤشرات وتقارير شمولية'],
            ['icon' => 'ti ti-shield-lock', 'text' => 'أمان ورقابة مركزية متقدمة'],
        ]"
    >
        <form id="formAuthentication" action="{{ route('admin.login.store') }}" method="POST" novalidate>
            @csrf

            <x-shared.form.controls.input name="email" label="البريد الإلكتروني" placeholder="أدخل البريد الإلكتروني"
                type="email" :value="old('email')" required autocomplete="username" col="col-12 mb-3" icon="ti ti-mail" />

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
