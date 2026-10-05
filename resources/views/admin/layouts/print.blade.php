<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title') | ليفـــان LEVAN</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('static/brand/logos/logo-light.svg') }}" />

    @vite(['resources/assets/vendor/fonts/tabler-icons.scss', 'resources/scss/admin/print.scss'])

    @yield('print-style')
</head>

<body>

    <div class="print-layout">
        @yield('content')
    </div>

</body>

</html>
