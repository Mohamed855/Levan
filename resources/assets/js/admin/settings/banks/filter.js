$(function () {
    $('.select2').select2({
        allowClear: true,
        dir: 'rtl'
    });

    $('#banks-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const is_active = $('#filter-status').val() || '';

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (is_active !== '') params.append('is_active', is_active);

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

            table.ajax.url(newUrl).load();
        }

        $('#filter-status').on('change', applyFilters);

        $('#btn-reset-filters').on('click', function () {
            $('#filter-status').val('').trigger('change');
        });
    });
});
