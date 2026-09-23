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

    {{--
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css"
        integrity="sha384-oMy41mb/qJnpJlpXOF57hSu2KGi47l/UV9+tPNrBOs7/ap5Vubj/3phrCtjutHMQ" crossorigin="anonymous">
    --}}

    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"
        integrity="sha384-r+ljwOAhwY4/kdyzMnuBg7MEVoWpTMp5EYUDntB/E9qzNwL9dAEcNrb2XaV+mJc2" crossorigin="anonymous">
    </script> --}}

    {{--
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap" rel="stylesheet"> --}}

    <!-- Boxicons CSS -->
    {{--
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css'
        integrity='sha384-42kyIPf7HDYLkGffmxDhSx/3Z/53wGBs3nD6wEFxsbeDc7rMO6mkYbkAcpRsnMU2' crossorigin='anonymous'
        rel='stylesheet'> --}}

    <!-- Google Tag Manager -->
    {{-- <script>
        (function(w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(),
                event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s),
                dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =
                'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-NR4KRS8K');
    </script> --}}

    <style>
        :root {
            --primary-color: #372632;
        }

        body {
            font-family: "Almarai", sans-serif;
            font-weight: 300;
            font-style: normal;
        }
    </style>
</head>

<body>
    {{-- <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NR4KRS8K" height="0" width="0"
            style="display:none;visibility:hidden"></iframe></noscript> --}}
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
    </style>


    <!-- Layout Content -->
    @yield('layoutContent')

    @include('admin.layouts.sections.scripts' . $isFront)

</body>

</html>