<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title') | لـــوت LOT</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('static/brand/logos/logo.svg') }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}" />
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />

    @vite(['resources/assets/vendor/fonts/tabler-icons.scss', 'resources/assets/scss/admin/print.scss'])

    @yield('print-style')
</head>

<body>

    <div class="print-layout">
        @yield('content')
    </div>

</body>

</html>
