<!-- BEGIN: Vendor JS-->

@vite(['resources/assets/vendor/libs/jquery/jquery.js', 'resources/assets/vendor/libs/popper/popper.js', 'resources/assets/vendor/js/bootstrap.js', 'resources/assets/vendor/libs/node-waves/node-waves.js', 'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js', 'resources/assets/vendor/libs/hammer/hammer.js', 'resources/assets/vendor/libs/typeahead-js/typeahead.js', 'resources/assets/vendor/js/menu.js', 'resources/js/app.js'])

@yield('vendor-script')
<!-- END: Page Vendor JS-->
<!-- BEGIN: Theme JS-->
@vite(['resources/assets/js/shared/basic-tamplate/main.js'])

{{-- notifications.js --}}
{{-- @vite(['resources/assets/js/tenants/notifications/notifications.js']) --}}
<!-- BEGIN: toastr -->
@yield('toastr')

<!-- END: toastr-->

<!-- BEGIN: Shared JS (extracted from layoutMaster) -->
@vite([
    'resources/js/shared/csrf-setup.js',
    'resources/js/shared/toastr-flash.js',
    'resources/js/shared/confirm-delete.js',
    'resources/js/shared/modal-close-controls.js',
    'resources/js/shared/input-guards.js',
    'resources/js/shared/datatable-search.js',
    'resources/js/shared/datatable-filter-selects.js',
    'resources/js/shared/wizard/create-wizard-enhancer.js',
])
<!-- END: Shared JS -->

<!-- END: Theme JS-->
<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->
<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->
