<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Copyright" content="lot" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Page title -->
    <title>
        @yield('title')
    </title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('static/brand/logos/logo.svg') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}" />
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />

    <!-- CSS Plugins -->
    <link rel="stylesheet" href="{{ asset('static/site/css/swiper-bundle.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('static/site/css/glightbox.min.css') }}" />

    <link rel="stylesheet" href="{{ asset('static/site/css/main.css') }}" />

    @vite(['resources/css/app.css', 'resources/assets/css/site/landing.css', 'resources/js/app.js', 'resources/js/shared/input-guards.js', 'resources/assets/vendor/fonts/tabler-icons.scss'])

    @yield('page-style')

</head>
