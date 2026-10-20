@php
    $customizerHidden = 'customizer-hide';
    $configData = App\Helpers\Helpers::appClasses();
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('tenant.layouts.layoutMaster')

@section('title', 'تحت الصيانة')

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection

@section('content')
    <div class="container-xxl container-p-y">
        <div class="misc-wrapper text-center">
            <h4 class="mb-2 mx-2">نقوم حالياً بأعمال صيانة 🚧</h4>
            <p class="mb-6 mx-2">
                نعتذر عن الإزعاج، نحن نعمل على تحسين النظام في الوقت الحالي. يرجى المحاولة لاحقًا.
            </p>

            <div class="mt-12">
                <img src="{{ asset('static/dashboard/illustrations/page-misc-under-maintenance.png') }}" alt=" تحت الصيانة"
                    width="550" class="img-fluid">
            </div>
        </div>
    </div>

    <div class="container-fluid misc-bg-wrapper misc-under-maintenance-bg-wrapper">
        <img src="{{ asset('static/dashboard/illustrations/bg-shape-image-' . $configData['style'] . '.png') }}" height="355"
            alt=" تحت الصيانة" data-app-light-img="illustrations/bg-shape-image-light.png"
            data-app-dark-img="illustrations/bg-shape-image-dark.png">
    </div>
@endsection
