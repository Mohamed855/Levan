'use strict';

$(function () {
    $('.select2').select2({
        allowClear: true,
        placeholder: 'الكل',
        dir: 'rtl'
    });

    $('#my-tasks-table').on('init.dt', function () {
        $('#filter-overdue-days').on('change', function () {
            if ($(this).val() === 'custom') {
                $('#custom-date-range').removeClass('d-none');

                return;
            }

            $('#custom-date-range').addClass('d-none');
            $('#filter-date-from, #filter-date-to').val('');
        });

        $(document).on('datatable-filter:reset', function (event, data) {
            if (data?.targetTable === '#my-tasks-table') {
                $('#custom-date-range').addClass('d-none');
            }
        });
    });

    $('[data-bs-toggle="tooltip"]').tooltip();
});
