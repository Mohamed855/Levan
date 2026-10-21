@extends('app')

@section('body-class', 'site-shell')

@push('head')
    <link rel="icon" type="image/svg+xml" href="{{ asset('static/brand/logos/logo.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&family=Outfit:wght@400;500;600;700&family=Readex+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite([
        'resources/assets/scss/web/site.scss',
        'resources/assets/vendor/fonts/tabler-icons.scss',
    ])
@endpush

@section('body')
    @yield('content')
@endsection
