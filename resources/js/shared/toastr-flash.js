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
document.addEventListener('DOMContentLoaded', function () {
    if (typeof toastr === 'undefined') return;

    var body = document.body;

    var success = body.getAttribute('data-flash-success');
    var error   = body.getAttribute('data-flash-error');
    var info    = body.getAttribute('data-flash-info');
    var warning = body.getAttribute('data-flash-warning');

    if (success) toastr.success(success);
    if (error)   toastr.error(error);
    if (info)    toastr.info(info);
    if (warning) toastr.warning(warning);
});
