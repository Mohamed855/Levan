@php
    $customizerHidden = 'customizer-hide';
    $configData = App\Helpers\Helpers::appClasses();
    $pageConfigs = ['myLayout' => 'blank'];
@endphp
@extends('tenants.layouts.layoutMaster')

@section('title', 'خطأ - الصفحة غير موجودة')

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
    <div class="container-xxl container-p-y" dir="rtl">
        <div class="misc-wrapper text-right">
            <h1 class="mb-2 mx-2 fs-xxlarge" style="line-height: 6rem;font-size: 6rem;">404</h1>
            <h4 class="mb-2 mx-2">الصفحة غير موجودة </h4>
            <p class="mb-6 mx-2">لم نتمكن من العثور على الصفحة التي تبحث عنها</p>
            <div class="mb-4">
                <a href="{{ tenant() ? url(tenant('id') . '/dashboard') : url('/') }}" class="btn btn-primary">العودة إلى الصفحة الرئيسية</a>
            </div>
            <div class="mt-4">
                <img src="{{ asset('static/dashboard/illustrations/boy-with-laptop-light.png') }}" alt="page-misc-error"
                    width="250" class="img-fluid">
            </div>
        </div>
    </div>
    <div class="container-fluid misc-bg-wrapper">
        <img src="{{ asset('static/dashboard/illustrations/bg-shape-image-' . $configData['style'] . '.png') }}" height="355"
            alt="page-misc-error" data-app-light-img="illustrations/bg-shape-image-light.png"
            data-app-dark-img="illustrations/bg-shape-image-dark.png">
    </div>
@endsection
