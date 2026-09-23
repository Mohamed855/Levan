
$(function () {
    $('.select2').select2({
        allowClear: true,
        dir: 'rtl'
    });

    $('#client-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const filters = {
                status: $('#filter-status').val() || '',
            };

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (filters.status) params.append('status', filters.status);

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

            table.ajax.url(newUrl).load();
        }

        $('#filter-status').on('change', applyFilters);
    });
});
