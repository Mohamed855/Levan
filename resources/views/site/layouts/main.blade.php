<!DOCTYPE html>
<html lang="ar" dir="rtl">

@include('site.layouts.head')

<body class="site-shell">
    <x-shared.toastr />

    <!-- Page loading -->
    <div class="page-loading fixed top-0 bottom-0 left-0 right-0 z-[99999] flex items-center justify-center bg-primary-light-1 dark:bg-primary-dark-1 opacity-100 visible pointer-events-auto"
        role="status" aria-live="polite" aria-atomic="true" aria-label="Loading...">
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

    <button type="button"
        class="back-to-top inline-flex w-12 h-12 rounded-full items-center justify-center text-lg/none fixed bottom-[24px] right-[24px] opacity-100 visible z-50 is-hided"
        data-web-trigger="scroll-top" aria-label="Scroll to top">
        <i class="ti ti-chevron-up"></i>
    </button>

    @include('site.layouts.script')

    @yield('toastr')

    @yield('page-script')

</body>

</html>
