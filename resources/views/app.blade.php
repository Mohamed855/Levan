<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name')) | لـــوت LOT</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('static/brand/logos/logo.svg') }}" />

    @yield('head')
    @stack('head')
    @yield('vendor-style')
    @yield('page-style')
    @stack('styles')
</head>
<body class="@yield('body-class')"
    data-flash-success="{{ Session::get('success') }}"
    data-flash-error="{{ Session::get('error') }}"
    data-flash-info="{{ Session::get('info') }}"
    data-flash-warning="{{ Session::get('warning') }}"
>
    @yield('body')

    <script src="{{ asset('static/shared/js/cdn/jquery-init.js') }}"></script>
    @vite([
        'resources/assets/vendor/js/bootstrap.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/js/shared/app.js',
        'resources/assets/js/shared/csrf-setup.js',
        'resources/assets/js/shared/toastr-flash.js',
        'resources/assets/js/shared/confirm-delete.js',
        'resources/assets/js/shared/table-status-toggle.js',
        'resources/assets/js/shared/datatable-filter-selects.js',
    ])
    @yield('vendor-script')
    @stack('scripts')
    @yield('page-script')
    @stack('dashboard-scripts')
</body>
</html>
