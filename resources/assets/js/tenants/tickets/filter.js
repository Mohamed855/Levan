$(function () {
    $('.select2').select2({
        allowClear: true,
        dir: 'rtl'
    });

    $('#tickets-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const status = $('#filter-status').val() || '';

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (status) params.append('status', status);

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

            table.ajax.url(newUrl).load();
        }

        $('#filter-status').on('change', applyFilters);

        $('#btn-reset-filters').on('click', function () {
            $('#filter-status').val('').trigger('change');
        });
    });
});
