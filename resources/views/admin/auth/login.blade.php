@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('admin.layouts.layoutMaster')

@section('title', 'تسجيل الدخول')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth/pages-auth.js'])
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
                            <a href="{{ url('/') }}" class="app-brand-link">
                                <span class="app-brand-logo demo">
                                    @include('_partials.macros', [
                                        'height' => 40,
                                        'logoPath' => asset('img/Levan-logo.svg'),
                                        'withbg' => 'fill: #fff;',
                                    ])
                                </span>

                            </a>
                        </div>
                        <!-- /Logo -->
                        <h4 class="mb-1"> مرحبـا بـك 👋</h4>
                        <p class="mb-6">لوحة تحكم الادارة </p>

                        <!-- Session Status -->
                        <x-auth-session-status class="mb-4" :status="session('status')" />

                        <form id="formAuthentication" class="mb-4" action="{{ route('admin.login') }}" method="POST">
                            @csrf
                            <div class="mb-6">
                                <label for="email" class="form-label">البريد الإلكتروني </label>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="الرجاء إدخال البريد الإلكتروني  " value="{{ old('email') }}" required
                                    autofocus autocomplete="username">
                                <x-input-error :messages="$errors->get('email')" class="mt-2 text-danger" />
                            </div>
                            <div class="mb-6 form-password-toggle">
                                <label class="form-label" for="password">كلمة المرور</label>
                                <div class="input-group input-group-merge">
                                    <input type="password" id="password" class="form-control" name="password"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        required autocomplete="current-password" />
                                    <span class="input-group-text cursor-pointer">
                                        <i class="ti ti-eye-off"></i>
                                    </span>
                                </div>
                                <x-input-error :messages="$errors->get('password')" class="mt-2 text-danger" />
                            </div>
                            <div class="my-8">
                                <div class="d-flex justify-content-between">
                                    <div class="form-check mb-0 ms-2">
                                        <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                                        <label class="form-check-label" for="remember_me">
                                            تذكرني
                                        </label>
                                    </div>
                                    {{-- @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}">
                                            <p class="mb-0">هل نسيت كلمة المرور ؟</p>
                                        </a>
                                    @endif --}}
                                </div>
                            </div>
                            <div class="mb-6">
                                <button class="btn btn-primary d-grid w-100" type="submit">تسجيل الدخول</button>
                            </div>
                        </form>

                        <p class="text-center">
                        <div class="text-body text-center pt-4">
                            جميع الحقوق محفوظة 2026 @
                            <a href="https://e-tec.sa" target="_blank" class="footer-link">
                                إتمام لتقنية نظم المعلومات ❤️
                            </a>
                            <p>V.{{ config('app.version') }}</p>
                        </div>
                        </p>
                    </div>
                </div>
                <!-- /Login -->
            </div>
        </div>
    </div>
@endsection
