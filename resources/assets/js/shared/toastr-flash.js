/**
 * Toastr Flash Messages
 *
 * Replaces the inline <script> block in:
 *   - admin/layouts/layoutMaster.blade.php
 *   - tenants/layouts/layoutMaster.blade.php
 *
 * Reads flash data from <body> data-* attributes set by the layout:
 *   data-flash-success, data-flash-error, data-flash-info, data-flash-warning
 *
 * The layout sets these via:
 *   <body data-flash-success="{{ Session::get('success') }}" ...>
 */
(function initToastrFlash() {
    var maxAttempts = 60; // 3 seconds maximum wait time
    var attempts = 0;

    function displayFlashes() {
        var body = document.body;
        if (!body) return;

        var success = body.getAttribute('data-flash-success');
        var error   = body.getAttribute('data-flash-error');
        var info    = body.getAttribute('data-flash-info');
        var warning = body.getAttribute('data-flash-warning');

        // Exit early if there are no flash messages to display
        if (!success && !error && !info && !warning) {
            return;
        }

        // Wait until toastr is loaded
        if (typeof toastr === 'undefined') {
            if (attempts < maxAttempts) {
                attempts++;
                setTimeout(displayFlashes, 50);
            }
            return;
        }

        // Display and clean up attributes to prevent duplicate toasts
        if (success) {
            toastr.success(success);
            body.removeAttribute('data-flash-success');
        }
        if (error) {
            toastr.error(error);
            body.removeAttribute('data-flash-error');
        }
        if (info) {
            toastr.info(info);
            body.removeAttribute('data-flash-info');
        }
        if (warning) {
            toastr.warning(warning);
            body.removeAttribute('data-flash-warning');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', displayFlashes);
    } else {
        displayFlashes();
    }
})();
