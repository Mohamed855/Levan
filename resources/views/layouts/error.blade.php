@php
    $hideNav = true;
    $hideFooter = true;
    $hidePreloader = true;
    $hideLoader = true;

    if (!isset($homeUrl)) {
        if (function_exists('tenant') && tenant()) {
            $homeUrl = url('/');
        } elseif (request()->is('superadmin/*') || request()->is('superadmin') || request()->is('admin/*') || request()->is('admin')) {
            $homeUrl = Route::has('admin.dashboard.index') ? route('admin.dashboard.index') : (Route::has('admin.dashboard') ? route('admin.dashboard') : url('/superadmin'));
        } elseif (Route::has('site.index')) {
            $homeUrl = route('site.index');
        } else {
            $homeUrl = url('/');
        }
    }
@endphp

@extends('layouts.web')

@section('content')
    <main class="site-error-page">
        <div class="container site-error-container">
            <article class="site-error-card">
                <div class="site-error-brand">
                    <a href="{{ $homeUrl }}"
                        aria-label="{{ __('الرئيسية') }}">
                        <img src="{{ asset('static/brand/logos/lot/trans-profile.png') }}" alt="Levan">
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
                    <a href="{{ $homeUrl }}"
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
