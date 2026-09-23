<!DOCTYPE html>
@php
    $menuFixed =
        $configData['layout'] === 'vertical'
            ? $menuFixed ?? ''
            : ($configData['layout'] === 'front'
                ? ''
                : $configData['headerType']);
    $navbarType =
        $configData['layout'] === 'vertical'
            ? $configData['navbarType'] ?? ''
            : ($configData['layout'] === 'front'
                ? 'layout-navbar-fixed'
                : '');
    $isFront = ($isFront ?? '') == true ? 'Front' : '';
    $contentLayout = isset($container) ? ($container === 'container-xxl' ? 'layout-compact' : 'layout-wide') : '';
@endphp

<html lang="{{ session()->get('locale') ?? app()->getLocale() }}"
    class="{{ $configData['style'] }}-style {{ $contentLayout ?? '' }} {{ $navbarType ?? '' }} {{ $menuFixed ?? '' }} {{ $menuCollapsed ?? '' }} {{ $menuFlipped ?? '' }} {{ $menuOffcanvas ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}"
    dir="{{ $configData['textDirection'] }}" data-theme="{{ $configData['theme'] }}"
    data-assets-path="{{ asset('/assets') . '/' }}" data-base-url="{{ url('/') }}" data-framework="laravel"
    data-template="{{ $configData['layout'] . '-menu-' . $configData['themeOpt'] . '-' . $configData['styleOpt'] }}"
    data-style="{{ $configData['styleOptVal'] }}">

<head>
    <style>
        :root {
            --primary-color: #372632;
        }
    </style>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>@yield('title') |ليفـــان LEVAN </title>



    <meta name="csrf-token" content="{{ csrf_token() }}">

    @auth
        <meta name="user-id" content="{{ auth()->id() }}">
    @endauth

    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/branding/logo.svg') }}" />

    <!-- Include Styles -->
    <!-- $isFront is used to append the front layout styles only on the front layout otherwise the variable will be blank -->
    @include('tenants/layouts.sections/styles' . $isFront)

    <!-- Include Scripts for customizer, helper, analytics, config -->
    <!-- $isFront is used to append the front layout scriptsIncludes only on the front layout otherwise the variable will be blank -->
    @include('tenants/layouts/sections/scriptsIncludes' . $isFront)
    {{-- for Tostr --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" integrity="sha384-oMy41mb/qJnpJlpXOF57hSu2KGi47l/UV9+tPNrBOs7/ap5Vubj/3phrCtjutHMQ" crossorigin="anonymous"> --}}

    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js" integrity="sha384-r+ljwOAhwY4/kdyzMnuBg7MEVoWpTMp5EYUDntB/E9qzNwL9dAEcNrb2XaV+mJc2" crossorigin="anonymous"></script> --}}

    {{-- font page --}}
    {{--
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet"> --}}
    {{-- font page --}}


</head>

<body>
    <style>
        body {
            font-family: "Almarai", sans-serif;
            font-weight: 300;
            font-style: normal;
        }

        /* إضافة بعض التنسيقات للتنبيه الثابت */
        .fixed-alert {
            position: fixed;
            bottom: 0px;
            left: 1%;
            width: 80%;
            max-width: 500px;
            z-index: 9999;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .badge-custom {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            color: #fff;
            border-radius: 5px;
            text-align: center;
            font-size: 0.8rem;
            white-space: normal;
            word-wrap: break-word;
            word-break: break-word;
            min-height: 40px;
            max-height: 40px;
        }

        /* لون  القائمة الجانبية  */
        [dir=rtl] .bg-menu-theme.menu-vertical .menu-item.active>.menu-link:not(.menu-toggle) {
            background: #e3d8d1 !important;
            color: #372632 !important;
        }

        /* لون  القائمة الجانبية  */
    </style>


    <!-- Layout Content -->
    @yield('layoutContent')

    @include('tenants/layouts/sections/scripts' . $isFront)

</body>

</html>
