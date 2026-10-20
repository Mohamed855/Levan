/**
 * CSRF Setup — jQuery.ajaxSetup with CSRF token
 *
 * Replaces the inline <script> block in:
 *   - admin/layouts/layoutMaster.blade.php
 *   - tenants/layouts/layoutMaster.blade.php
 *
 * Must be loaded AFTER jQuery (included in scripts.blade.php via vendor libs).
 * csrfToken is set on <meta name="csrf-token"> by commonMaster.
 */
document.addEventListener('DOMContentLoaded', function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    if (typeof jQuery !== 'undefined') {
        jQuery.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN':    csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept':          'application/json',
            },
        });
    }
});
