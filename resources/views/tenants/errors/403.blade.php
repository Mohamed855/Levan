@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
    $configData = App\Helpers\Helpers::appClasses();
@endphp

@extends('tenants.layouts.layoutMaster')

@section('title', 'خطأ 403 - غير مصرح لك بالدخول')

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
    <div class="container-xxl container-p-y" dir="rtl">
        <div class="misc-wrapper text-right">
            <h1 class="mb-2 mx-2 fs-xxlarge" style="line-height: 6rem;font-size: 6rem;">403</h1>
            <h4 class="mb-2 mx-2">غير مصرح لك بالدخول 🔐</h4>
            <p class="mb-6 mx-2">ليس لديك الصلاحيات الكافية للوصول إلى هذه الصفحة.</p>
            <div class="mb-4">
                <a href="{{ tenant() ? url(tenant('id') . '/dashboard') : url('/') }}" class="btn btn-primary">العودة إلى الصفحة الرئيسية</a>
            </div>
            <div class="mt-4">
                <img src="{{ asset('assets/img/illustrations/page-misc-you-are-not-authorized.png') }}"
                    alt="غير مصرح لك بالدخول" width="250" class="img-fluid">
            </div>
        </div>
    </div>
    <div class="container-fluid misc-bg-wrapper">
        <img src="{{ asset('assets/img/illustrations/bg-shape-image-' . $configData['style'] . '.png') }}" height="355"
            alt="غير مصرح لك بالدخول" data-app-light-img="illustrations/bg-shape-image-light.png"
            data-app-dark-img="illustrations/bg-shape-image-dark.png">
    </div>
@endsection
