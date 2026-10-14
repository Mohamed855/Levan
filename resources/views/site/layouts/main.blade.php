<!DOCTYPE html>
<html lang="ar" dir="rtl">

@include('site.layouts.head')

<body class="site-shell">
    <x-shared.toastr />

    <!-- Page loading indicator -->
    <div class="page-loading" role="status" aria-live="polite" aria-atomic="true" aria-label="Loading...">
        <div class="grid-loader">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>

    @include('site.layouts.header')

    @yield('content')

    @include('site.layouts.footer')

    <!-- Back to top button -->
    <button type="button" class="back-to-top" aria-label="الرجوع للأعلى">
        <i class="ti ti-chevron-up"></i>
    </button>

    @include('site.layouts.script')

    @yield('toastr')

    @yield('page-script')
</body>

</html>
