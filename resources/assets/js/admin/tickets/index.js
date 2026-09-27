'use strict';

$(document).ready(function () {
    initializeComponents();

    $('#admin-tickets-table').on('init.dt', function () {
        bindTableEvents();
    });
});

function initializeComponents() {
    $('.filters .select2').each(function () {
        $(this).select2({
            placeholder: $(this).data('placeholder') || 'اختر',
            allowClear: true,
            width: '100%',
            language: 'ar',
            dir: 'rtl',
            dropdownParent: $(this).closest('.card-body')
        });
    });
}

function bindTableEvents() {
    const table = $('#admin-tickets-table').DataTable();

    const tableWrapper = $('#admin-tickets-table_wrapper');

    // تحديد الكل
    tableWrapper.on('click', '#select-all', function () {
        $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
    });

    tableWrapper.on('change', '.row-checkbox', function () {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        } else {
            const allChecked = $('.row-checkbox:checked', table.rows({ search: 'applied' }).nodes()).length ===
                               $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).length;
            $('#select-all').prop('checked', allChecked);
        }
    });
}

window.handleMassDelete = function () {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('يرجى تحديد تذكرة واحدة على الأقل');
        } else {
            alert('يرجى تحديد تذكرة واحدة على الأقل');
        }
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Ticket');
};
