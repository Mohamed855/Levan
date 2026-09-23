@php
    $customizerHidden = 'customizer-hide';
    // التحقق من إعدادات الصفحة الممررة من المتحكم
    $pageConfigs = ['myLayout' => 'blank'];
    $configData = Helper::appClasses();
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'غير مصرح - الصفحات')

@section('page-style')
    <!-- صفحة -->
    @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
    <!-- غير مصرح -->
    <div class="container-xxl container-p-y">
        <div class="misc-wrapper">
            <h1 class="mb-2 mx-2" style="line-height: 6rem;font-size: 6rem;">401</h1>
            <h4 class="mb-2 mx-2"> غير مصرح! 🔐</h4>
            <p class="mb-6 mx-2">ليس لديك إذن للوصول إلى هذه الصفحة. عُد إلى الصفحة الرئيسية!</p>
            <a href="{{ url('/dashboard') }}" class="btn btn-primary">العودة إلى الصفحة الرئيسية</a>
            <div class="mt-12">
                <img src="{{ asset('assets/img/illustrations/auth-reset-password-illustration-light.png') }}"
                    alt="صفحة-غير-مصرح-بها" width="170" class="img-fluid">
            </div>
        </div>
    </div>
    <div class="container-fluid misc-bg-wrapper">
        <img src="{{ asset('assets/img/illustrations/bg-shape-image-' . $configData['style'] . '.png') }}" height="355"
            alt="صفحة-غير-مصرح-بها" data-app-light-img="illustrations/bg-shape-image-light.png"
            data-app-dark-img="illustrations/bg-shape-image-dark.png">
    </div>
    <!-- /غير مصرح -->
@endsection
