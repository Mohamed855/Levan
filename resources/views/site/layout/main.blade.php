<!DOCTYPE html>
<html lang="ar" dir="rtl">

@include('site.layout.head')

<body>
    @include('shared.toastr')

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


    @include('site.layout.header')

    @yield('content')

    @include('site.layout.footer')

    <button type="button"
        class="inline-flex w-12 h-12 rounded-md items-center justify-center text-lg/none bg-primary text-primary-color hover:bg-primary-light-10 dark:hover:bg-primary-dark-10 focus:bg-primary-light-10 dark:focus:bg-primary-dark-10 fixed bottom-[80px] right-[20px] hover:-translate-y-1 opacity-100 visible z-50 is-hided"
        data-web-trigger="scroll-top" aria-label="Scroll to top">
        <i class="ti ti-chevron-up"></i>
    </button>

    @include('site.layout.script')

    @yield('toastr')

    @yield('page-script')

</body>

</html>
