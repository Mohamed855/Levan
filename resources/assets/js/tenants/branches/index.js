/*
|--------------------------------------------------------------------------
| Branches Index: Single Source of Truth for DataTable Filters (status, city)
|--------------------------------------------------------------------------
| Initializes Select2, wires filters to DataTables AJAX source, and handles
| select-all / mass actions and status toggling.
*/

'use strict';

$(function () {
    /*
    |--------------------------------------------------------------------------
    | تهيئة Select2
    |--------------------------------------------------------------------------
    */
    $('.select2').select2({
        allowClear: true,
        placeholder: 'الكل',
        dir: 'rtl'
    });

    /*
    |--------------------------------------------------------------------------
    | تهيئة الفلاتر بعد تحميل الجدول
    |--------------------------------------------------------------------------
    */
    $('#branches-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const status = $('#filter-status').val() || '';
            const city = $('#filter-city').val() || '';
            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (status) params.append('status', status);
            if (city) params.append('city', city);

            const query = params.toString();
            const newUrl = query ? baseUrl + '?' + query : baseUrl;

            table.ajax.url(newUrl).load();
        }

        $('#filter-status').on('change', applyFilters);
        $('#filter-city').on('keyup', applyFilters);

        const tableWrapper = $('#branches-table_wrapper');

        /*
        |--------------------------------------------------------------------------
        | تحديد الكل
        |--------------------------------------------------------------------------
        */
        tableWrapper.on('click', '#select-all', function () {
            const rows = table.rows({ search: 'applied' }).nodes();
            $('input.row-checkbox', rows).prop('checked', this.checked);
        });

        tableWrapper.on('change', '.row-checkbox', function () {
            if (!this.checked) {
                $('#select-all').prop('checked', false);
            }
        });

        table.on('draw', function () {
            $('#select-all').prop('checked', false);
        });
    });
});

/*
|--------------------------------------------------------------------------
| تبديل الحالة (نشط / غير نشط)
|--------------------------------------------------------------------------
*/
function toggleStatus(branchId) {
    const url = window.routes.toggleStatus.replace(':id', branchId);

    $.ajax({
        url: url,
        type: 'PATCH',
        data: { _token: window.csrfToken },
        success: function (response) {
            if (response.success) {
                $('#branches-table').DataTable().draw(false);
                if (typeof toastr !== 'undefined') {
                    toastr.success(response.message, {
                    });
                }
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error(response.message, {
                    });
                }
            }
        },
        error: function () {
            if (typeof toastr !== 'undefined') {
                toastr.error('حدث خطأ أثناء تبديل الحالة', {
                });
            }
        }
    });
}

/*
|--------------------------------------------------------------------------
| الحذف الجماعي
|--------------------------------------------------------------------------
*/
function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length < 1) {
        if (typeof toastr !== 'undefined') toastr.warning('يرجى تحديد عنصر واحد على الأقل.');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Branch');
}

/*
|--------------------------------------------------------------------------
| تصدير الدوال للاستخدام العام
|--------------------------------------------------------------------------
*/
window.toggleStatus = toggleStatus;
window.handleMassDelete = handleMassDelete;