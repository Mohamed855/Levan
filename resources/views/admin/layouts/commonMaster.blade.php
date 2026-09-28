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

    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title>@yield('title') | ليفـــان LEVAN </title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('img/logo-light.svg') }}" />

    @include('admin.layouts.sections.styles' . $isFront)

    @include('admin.layouts.sections.scriptsIncludes' . $isFront)

    <meta name="csrf-token" content="{{ csrf_token() }}">

</head>

{{-- Flash data passed as data-attributes so resources/js/shared/toastr-flash.js can read them --}}
<body
    data-flash-success="{{ Session::get('success') }}"
    data-flash-error="{{ Session::get('error') }}"
    data-flash-info="{{ Session::get('info') }}"
    data-flash-warning="{{ Session::get('warning') }}"
    data-username="{{ Auth::guard('admin')->user()->name ?? '' }}"
>

    <!-- Layout Content -->
    @yield('layoutContent')

    @include('admin.layouts.sections.scripts' . $isFront)

</body>

</html>