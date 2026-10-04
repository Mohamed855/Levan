'use strict';

$(function () {
    // Initialize Select2
    $('.select2').select2({
        allowClear: true,
        placeholder: 'الكل',
        dir: 'rtl'
    });

    // Initialize DataTable
    $('#my-tasks-table').on('init.dt', function () {
        const table = $(this).DataTable();

        // Custom Date Range Toggle
        $('#filter-overdue-days').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#custom-date-range').removeClass('d-none');
            } else {
                $('#custom-date-range').addClass('d-none');
                $('#filter-date-from, #filter-date-to').val('');
            }
        });

        $(document).on('datatable-filter:reset', function (e, data) {
            if (data && data.targetTable === '#my-tasks-table') {
                $('#custom-date-range').addClass('d-none');
            }
        });

        // Select All Checkbox
        const tableWrapper = $('#my-tasks-table_wrapper');

        tableWrapper.on('click', '#select-all', function () {
            const rows = table.rows({ 'search': 'applied' }).nodes();
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

    // Initialize Tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});