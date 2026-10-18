'use strict';

$(document).ready(function () {
    $('#companies-table').on('init.dt', function () {
        bindTableEvents();
    });
});

function bindTableEvents() {
    const table = $('#companies-table').DataTable();
    const tableWrapper = $('#companies-table_wrapper');

    tableWrapper.on('click', '#select-all', function () {
        $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
    });

    tableWrapper.on('change', '.row-checkbox', function () {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        }
    });

    $('#companies-table').on('draw.dt', function () {
        $('#select-all').prop('checked', false);
    });
}

function handleMassDelete() {
    const selectedIds = $('.row-checkbox:checked').map(function () {
        return $(this).val();
    }).get();

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد شركة واحدة على الأقل');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Tenant');
}

window.handleMassDelete = handleMassDelete;
