/*
|--------------------------------------------------------------------------
| Companies Index: Single Source of Truth for DataTable Filters (status, authorized_count)
|--------------------------------------------------------------------------
| Initializes Select2, wires filters to DataTables AJAX source, and handles
| select-all / mass actions and status toggling.
*/

'use strict';

import { initializeTableStatusToggles } from '../../../shared/table-status-toggle';

$(function () {
    initializeTableStatusToggles();

    $('.select2').select2({
        allowClear: true,
        placeholder: 'الكل',
        dir: 'rtl'
    });

    $('#companies-table').on('init.dt', function () {
        const table = $(this).DataTable();


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

window.handleMassDelete = handleMassDelete;
