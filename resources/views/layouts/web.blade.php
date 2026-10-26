<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Copyright" content="lot" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Page title -->
    <title>
        @yield('title', 'لوت LOT - المنصة المتقدمة لإدارة المزادات والأصول')
    </title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('static/brand/logos/logo.svg') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />

    <!-- Brand Fonts: Almarai, Readex Pro, Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&family=Outfit:wght@400;500;600;700;800&family=Readex+Pro:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Core Site Styles -->
    @vite(['resources/assets/scss/web/site.scss', 'resources/assets/vendor/fonts/tabler-icons.scss', 'resources/assets/js/shared/input-guards.js'])

    @yield('page-style')
</head>

<body class="site-shell">
    <x-shared.toastr />

    <div class="page-loading" role="status" aria-live="polite" aria-atomic="true" aria-label="Loading...">
        <div class="grid-loader">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>

    @unless ($hideNav ?? false)
        @include('web.partials.header')
    @endunless

    @yield('content')

    @unless ($hideFooter ?? false)
        @include('web.partials.footer')

        <button type="button" class="back-to-top" aria-label="الرجوع للأعلى">
            <i class="ti ti-chevron-up"></i>
        </button>
    @endunless

    @include('web.partials.script')

    @yield('toastr')

    @yield('page-script')
</body>

</html>
