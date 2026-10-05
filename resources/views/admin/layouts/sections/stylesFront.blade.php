 
@vite(['resources/assets/vendor/fonts/tabler-icons.scss'])

<!-- Core CSS -->
@vite(['resources/assets/vendor/scss/rtl/core.scss', 'resources/assets/vendor/scss/rtl/theme-semi-dark.scss', 'resources/assets/vendor/libs/node-waves/node-waves.scss', 'resources/assets/vendor/scss/pages/front-page.scss'])

<!-- Vendor Styles -->
@yield('vendor-style')


<!-- Page Styles -->
@yield('page-style')
<style>
    body {
        font-family: "Readex Pro", sans-serif !important;
        font-optical-sizing: auto !important;
        font-weight: 300 !important;
        font-style: normal !important;
    }
</style>
