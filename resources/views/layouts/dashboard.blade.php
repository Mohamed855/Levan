@extends('app')

@php($dashboardScope = trim($__env->yieldContent('dashboard-scope', 'admin')))

@section('head')
    @if ($dashboardScope === 'tenant')
        @vite([
            'resources/assets/vendor/fonts/tabler-icons.scss',
            'resources/assets/scss/shared/dashboard.scss',
            'resources/assets/scss/tenant/app.scss',
        ])
    @else
        @vite([
            'resources/assets/vendor/fonts/tabler-icons.scss',
            'resources/assets/scss/shared/dashboard.scss',
            'resources/assets/scss/admin/app.scss',
        ])
    @endif
@endsection

@section('body-class', 'layout-wrapper layout-content-navbar')

@section('body')
    <div class="layout-wrapper layout-content-navbar">
        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
            @include("partials.dashboard.{$dashboardScope}.sidebar")
        </aside>

        <div class="layout-page">
            @include("partials.dashboard.{$dashboardScope}.navbar")

            <main class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">
                    @yield('content')
                </div>
            </main>

            @include("partials.dashboard.{$dashboardScope}.footer")
        </div>
    </div>

    <x-shared.toastr />
@endsection

@section('page-script')
    @vite([
        'resources/assets/vendor/js/bootstrap.js',
        'resources/assets/js/shared/app.js',
        'resources/assets/js/shared/table-status-toggle.js',
    ])
    @stack('dashboard-scripts')
@endsection