@php
$customizerHidden = 'customizer-hide';

// التحقق من إعدادات الصفحة الممررة من المتحكم
$pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'تسجيل الدخول ')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/@form-validation/form-validation.scss'
])
@endsection

@section('page-style')
@vite([
'resources/assets/vendor/scss/pages/page-auth.scss'
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js'
])
@endsection

@section('page-script')
@vite([
'resources/assets/js/shared/auth/pages-auth.js'
])
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <!-- Login -->
      <div class="card">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center mb-6">
            <a href="{{url('/')}}" class="app-brand-link">
              <span class="app-brand-logo demo">@include('_partials.macros',['height'=>20,'withbg' => "fill: #fff;"])</span>
              <span class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
            </a>
          </div>
          <!-- /Logo -->
          <h4 class="mb-1"> مرحبـا بـك {{ config('variables.templateName') }}! 👋</h4>
          <p class="mb-6">سجل الدخول الى حسابك وابدء المغامرة</p>

          <!-- Session Status -->
          <x-auth-session-status class="mb-4" :status="session('status')" />

          <form id="formAuthentication" class="mb-4" action="{{ tenant_route_name('login') }}" method="POST">
            @csrf
            <div class="mb-6">
              <label for="email" class="form-label">إسم المستخدم او رقم الهاتف </label>
              <input type="email" class="form-control" id="email" name="email" placeholder="الرجاء إدخال إسم المستخدم او الهاتف" :value="old('email')" required autofocus autocomplete="username">
              <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div class="mb-6 form-password-toggle">
              <label class="form-label" for="password">كلمة المرور</label>
              <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control" name="password" placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required autocomplete="current-password" />
                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
              </div>
              <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div class="my-8">
              <div class="d-flex justify-content-between">
                <div class="form-check mb-0 ms-2">
                  <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                  <label class="form-check-label" for="remember_me">
                    تذكرني
                  </label>
                </div>
                @if (Route::has('password.request'))
                <a href="{{ tenant_route_name('password.request') }}">
                  <p class="mb-0">هل نسيت كلمة المرور ؟</p>
                </a>
                @endif
              </div>
            </div>
            <div class="mb-6">
              <button class="btn btn-primary d-grid w-100" type="submit">تسجيل الدخول</button>
            </div>
          </form>

          <p class="text-center">
            <span>هل لديك حساب ؟</span>
            <a href="{{ tenant_route_name('register') }}">
              <span>حسـاب جديد</span>
            </a>
          </p>

          <div class="divider my-6">
            <div class="divider-text">او</div>
          </div>

          <div class="d-flex justify-content-center">
            <a href="javascript:;" class="btn btn-sm btn-icon rounded-pill btn-text-facebook me-1_5">
              <i class="tf-icons ti ti-brand-facebook-filled"></i>
            </a>

            <a href="javascript:;" class="btn btn-sm btn-icon rounded-pill btn-text-twitter me-1_5">
              <i class="tf-icons ti ti-brand-twitter-filled"></i>
            </a>

            <a href="javascript:;" class="btn btn-sm btn-icon rounded-pill btn-text-github me-1_5">
              <i class="tf-icons ti ti-brand-github-filled"></i>
            </a>

            <a href="javascript:;" class="btn btn-sm btn-icon rounded-pill btn-text-google-plus">
              <i class="tf-icons ti ti-brand-google-filled"></i>
            </a>
          </div>
        </div>
      </div>
      <!-- /Register -->
    </div>
  </div>
</div>
@endsection