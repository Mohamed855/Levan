@extends('app')

@section('body-class', 'site-shell')

@push('head')
    <link rel="icon" type="image/svg+xml" href="{{ asset('static/brand/logos/logo.svg') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&family=Outfit:wght@400;500;600;700;800&family=Readex+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite([
        'resources/assets/scss/web/site.scss',
        'resources/assets/vendor/fonts/tabler-icons.scss',
        'resources/assets/js/shared/input-guards.js',
    ])
@endpush

@section('body')
    <x-shared.toastr />

    <div class="page-loading" role="status" aria-live="polite" aria-atomic="true" aria-label="جاري التحميل">
        <div class="grid-loader">
            @for ($loaderCell = 0; $loaderCell < 9; $loaderCell++)
                <div></div>
            @endfor
        </div>
    </div>

    @include('partials.web.navbar')
    @include('partials.web.sidebar')

    @yield('content')

    @include('partials.web.footer')

    <button type="button" class="back-to-top" aria-label="العودة للأعلى">
        <i class="ti ti-chevron-up"></i>
    </button>

    @vite('resources/assets/js/web/site.js')
    @yield('toastr')
@endsection
