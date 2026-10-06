@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('admin.layouts.layoutMaster')

@section('title', 'حدث خطأ في الخادم')

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
    <div class="container-xxl container-p-y" dir="rtl">
        <div class="misc-wrapper text-center">
            <h1 class="mb-2 fs-xxlarge">500</h1>
            <h4 class="mb-2">حدث خطأ داخلي في الخادم</h4>
            <p class="mb-6">نعتذر عن هذا الخطأ. يرجى المحاولة مرة أخرى لاحقاً.</p>
            <a href="{{ url('/admin') }}" class="btn btn-primary">العودة إلى لوحة الإدارة</a>
        </div>
    </div>
@endsection
