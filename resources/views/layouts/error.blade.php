@extends('app')

@section('body-class', 'site-shell')

@push('head')
    <link rel="icon" type="image/svg+xml" href="{{ asset('static/brand/logos/logo.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&family=Outfit:wght@400;500;600;700&family=Readex+Pro:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    @vite(['resources/assets/scss/web/site.scss', 'resources/assets/vendor/fonts/tabler-icons.scss'])
@endpush

@section('body')
    <main class="site-error-page">
        <div class="container site-error-container">
            <article class="site-error-card">
                <div class="site-error-code">@yield('code', isset($exception) ? $exception->getStatusCode() : '')</div>
                <h1 class="site-error-title">@yield('heading', View::getSection('title'))</h1>
                <p class="site-error-desc">@yield('message', isset($exception) && $exception?->getMessage() ? $exception->getMessage() : '')</p>
                <div class="site-error-actions">
                    @hasSection('actions')
                        @yield('actions')
                    @else
                        <a href="{{ route('site.index') }}" class="site-error-btn">
                            <i class="ti ti-home"></i>
                            <span>العودة للرئيسية</span>
                        </a>
                        <a href="{{ route('site.index') }}#contact" class="site-error-btn-outline">
                            <i class="ti ti-message-question"></i>
                            <span>تواصل مع الدعم</span>
                        </a>
                    @endif
                </div>
            </article>
        </div>
    </main>
@endsection
