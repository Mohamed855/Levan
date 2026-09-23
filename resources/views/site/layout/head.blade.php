<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Copyright" content="levan" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Page title -->
    <title>
        @yield('title')
    </title>


    <!-- CSS Plugins -->
    <link rel="stylesheet" href="{{ asset('assets/website/css/swiper-bundle.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/website/css/glightbox.min.css') }}" />

    <link rel="stylesheet" href="{{ asset('assets/website/css/main.css') }}" />

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/assets/vendor/fonts/tabler-icons.scss'])

    @yield('page-style')

</head>
