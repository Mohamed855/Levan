@php
    $hideNav = true;
    $hideFooter = true;
@endphp

@extends('layouts.web')

@section('content')
    <main class="site-error-page">
        <div class="container site-error-container">
            <article class="site-error-card">
                <div class="site-error-brand">
                    <a href="{{ Route::has('site.index') ? route('site.index') : url('/') }}"
                        aria-label="{{ __('الرئيسية') }}">
                        <img src="{{ asset('static/brand/logos/logo.svg') }}" alt="Levan" height="48">
                    </a>
                </div>

                @hasSection('badge')
                    <div class="site-error-badge">
                        @yield('badge')
                    </div>
                @endif

                @hasSection('code')
                    <div class="site-error-code">@yield('code')</div>
                @endif

                <h1 class="site-error-title">@yield('heading', View::getSection('title') ?: __('حدث خطأ ما'))</h1>
                <p class="site-error-desc">@yield('message', __('نعتذر، تعذر إتمام طلبك في الوقت الحالي.'))</p>

                <div class="site-error-actions">
                    <a href="{{ $homeUrl ?? (Route::has('site.index') ? route('site.index') : url('/')) }}"
                        class="site-error-btn">
                        <i class="ti ti-home"></i>
                        <span>{{ __('العودة للرئيسية') }}</span>
                    </a>
                    @hasSection('secondary_action')
                        @yield('secondary_action')
                    @else
                        <a href="{{ Route::has('site.index') ? route('site.index') . '#contact' : url('/') }}"
                            class="site-error-btn-outline">
                            <i class="ti ti-headset"></i>
                            <span>{{ __('تواصل مع الدعم') }}</span>
                        </a>
                    @endif
                </div>
            </article>
        </div>
    </main>
@endsection
