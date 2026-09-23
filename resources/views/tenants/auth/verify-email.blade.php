@php
    $customizerHidden = 'customizer-hide';

    // التحقق من إعدادات الصفحة الممررة من المتحكم
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'رابط كلمة المرور')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
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
                <!-- Verify Email -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-6">
                            <a href="{{ url('/') }}" class="app-brand-link">
                                <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 20, 'withbg' => 'fill: #fff;'])</span>
                                <span
                                    class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
                            </a>
                        </div>
                        <!-- /Logo -->
                        <div class="mb-4 text-sm text-muted">
                            شكراً لتسجيلك! قبل البدء، هل يمكنك التحقق من عنوان بريدك الإلكتروني من خلال النقر على الرابط
                            الذي أرسلناه إليك؟ إذا لم تتلق البريد الإلكتروني، سنقوم بسرور بإرسال رابط آخر.
                        </div>

                        @if (session('status') == 'verification-link-sent')
                            <div class="mb-4 font-medium text-sm text-success">
                                تم إرسال رابط تحقق جديد إلى عنوان البريد الإلكتروني الذي قدمته أثناء التسجيل.
                            </div>
                        @endif

                        <div class="mt-4 d-flex justify-content-between align-items-center">
                            <form method="POST" action="{{ tenant_route_name('verification.send') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    إعادة إرسال بريد التحقق
                                </button>
                            </form>

                            <form method="POST" action="{{ tenant_route_name('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-link text-muted">
                                    تسجيل الخروج
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- /Verify Email -->
            </div>
        </div>
    </div>
@endsection
