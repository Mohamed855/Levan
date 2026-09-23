/*
|--------------------------------------------------------------------------
| Companies Index: Single Source of Truth for DataTable Filters (status, authorized_count)
|--------------------------------------------------------------------------
| Initializes Select2, wires filters to DataTables AJAX source, and handles
| select-all / mass actions and status toggling.
*/

'use strict';

$(function () {
    $('.select2').select2({
        allowClear: true,
        placeholder: 'الكل',
        dir: 'rtl'
    });

    $('#companies-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const status = $('#filter-status').val() || '';
            const authorizedCount = $('#filter-authorized-count').val() || '';
            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (status) params.append('status', status);
            if (authorizedCount) params.append('authorized_count', authorizedCount);

            const query = params.toString();
            const newUrl = query ? baseUrl + '?' + query : baseUrl;

            table.ajax.url(newUrl).load();
        }

        $('#filter-status, #filter-authorized-count').on('change', applyFilters);

        const tableWrapper = $('#companies-table_wrapper');

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


document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-toggle-btn');
    if (!btn) return;

    const userId = btn.dataset.id;
    const url = btn.dataset.url;

    if (!userId || !url) {
        toastr.error('بيانات المستخدم غير مكتملة');
        return;
    }

    toggleStatus(userId, url);
});

function toggleStatus(companyId, url) {
    const targetUrl = url || window.routes.toggleStatus.replace(':id', companyId);

    $.ajax({
        url: targetUrl,
        type: 'PATCH',
        data: { _token: window.csrfToken },
        success: function (response) {
            if (response.success) {
                $('#companies-table').DataTable().draw(false);
                if (typeof toastr !== 'undefined') toastr.success(response.message);
            } else {
                if (typeof toastr !== 'undefined') toastr.error(response.message);
            }
        },
        error: function () {
            if (typeof toastr !== 'undefined') toastr.error('حدث خطأ أثناء تبديل الحالة');
        }
    });
}

function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length < 1) {
        if (typeof toastr !== 'undefined') toastr.warning('يرجى تحديد عنصر واحد على الأقل.');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Company');
}

window.toggleStatus = toggleStatus;
window.handleMassDelete = handleMassDelete;
