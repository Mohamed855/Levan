@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'إعادة تعين كلمة المرور')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/auth/pages-auth.js'])

    @if (session('status'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                toastr.success("{{ session('status') }}", "نجاح");
            });
        </script>
    @endif
@endsection

@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <!-- Forgot Password -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-6">
                            <a href="{{ url('/') }}" class="app-brand-link">
                                <span class="app-brand-logo demo">
                                    @include('_partials.macros', [
                                        'height' => 20,
                                        'withbg' => 'fill: #fff;',
                                    ])
                                </span>
                                <span class="app-brand-text demo text-heading fw-bold">
                                    {{ config('variables.templateName') }}
                                </span>
                            </a>
                        </div>
                        <!-- /Logo -->
                        <h4 class="mb-1">هل نسيت كلمة المرور؟ 🔒</h4>
                        <p class="mb-6">أدخل بريدك الإلكتروني وسنرسل لك تعليمات لإعادة تعيين كلمة المرور الخاصة بك</p>

                        <!-- Session Status -->
                        <x-auth-session-status class="mb-4" :status="session('status')" />

                        <form id="formAuthentication" class="mb-6 needs-validation" method="POST"
                            action="{{ tenant_route_name('password.email') }}" novalidate>
                            @csrf

                            <div class="mb-6">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                    id="email" name="email" value="{{ old('email') }}"
                                    placeholder="أدخل البريد الإلكتروني" required autofocus>
                                <div class="invalid-feedback">
                                    @error('email')
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>

                            <button class="btn btn-primary d-grid w-100" type="submit">إرسال رابط إعادة التعيين</button>
                        </form>

                        <div class="text-center">
                            <a href="{{ url('/') }}" class="d-flex justify-content-center">
                                <i class="ti ti-chevron-left scaleX-n1-rtl me-1_5"></i>
                                العودة إلى بوابة الدخول
                            </a>
                        </div>
                    </div>
                </div>
                <!-- /Forgot Password -->
            </div>
        </div>
    </div>
@endsection
