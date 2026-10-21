'use strict';

$(document).ready(function () {
    $('#clients-table').on('init.dt', function () {
        bindTableEvents();
    });
});

function bindTableEvents() {
    const table = $('#clients-table').DataTable();
    const tableWrapper = $('#clients-table_wrapper');

    tableWrapper.on('click', '#select-all', function () {
        $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
    });

    tableWrapper.on('change', '.row-checkbox', function () {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        }
    });

    $('#clients-table').on('draw.dt', function () {
        $('#select-all').prop('checked', false);
    });
}

function handleMassDelete() {
    const selectedIds = $('.row-checkbox:checked').map(function () {
        return $(this).val();
    }).get();

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد عميل واحد على الأقل');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Tenant');
}

window.handleMassDelete = handleMassDelete;
