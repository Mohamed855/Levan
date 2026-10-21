<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    @yield('head')
    @stack('head')
    @yield('page-style')
</head>
<body class="@yield('body-class')">
    @yield('body')

    @stack('scripts')
    @yield('page-script')
</body>
</html>
