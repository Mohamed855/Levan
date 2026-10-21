@extends('app')

@section('body-class', 'auth-shell')

@section('page-style')
    @vite([
        'resources/assets/vendor/fonts/tabler-icons.scss',
        'resources/assets/scss/shared/auth.scss',
        'resources/assets/css/shared/auth-layout.css',
    ])
    @yield('auth-page-style')
@endsection

@section('body')
    @yield('content')
@endsection

@section('page-script')
    @vite([
        'resources/assets/js/shared/auth-layout.js',
        'resources/assets/js/shared/form-validation.js',
    ])
    @yield('auth-page-script')
@endsection
