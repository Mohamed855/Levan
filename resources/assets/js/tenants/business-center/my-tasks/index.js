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

        // Apply Filters Function
        function applyFilters() {
            const filters = {
                company_id: $('#filter-company').val() || '',
                portfolio_id: $('#filter-portfolio').val() || '',
                task_type: $('#filter-task-type').val() || '',
                status: $('#filter-status').val() || '',
                overdue_days: $('#filter-overdue-days').val() || '',
                date_from: $('#filter-date-from').val() || '',
                date_to: $('#filter-date-to').val() || '',
            };

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            for (const key in filters) {
                if (filters[key]) {
                    params.append(key, filters[key]);
                }
            }

            const newUrl = baseUrl + '?' + params.toString();
            table.ajax.url(newUrl).load();
        }

        // Filter Change Events
        $('#filter-company, #filter-portfolio, #filter-task-type, #filter-status, #filter-overdue-days').on('change', applyFilters);
        $('#filter-date-from, #filter-date-to').on('change', applyFilters);

        // Custom Date Range Toggle
        $('#filter-overdue-days').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#custom-date-range').removeClass('d-none');
            } else {
                $('#custom-date-range').addClass('d-none');
                $('#filter-date-from, #filter-date-to').val('');
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