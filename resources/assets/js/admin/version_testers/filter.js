$(function () {
    $('.select2').select2({
        allowClear: true,
        dir: 'rtl'
    });

    $('#version-testers-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const filters = {
                type: $('#filter-type').val() || '',
                is_active: $('#filter-status').val() || ''
            };

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (filters.type) params.append('type', filters.type);
            if (filters.is_active !== '') params.append('is_active', filters.is_active);

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

            table.ajax.url(newUrl).load();
        }

        $('#filter-type').on('change', applyFilters);
        $('#filter-status').on('change', applyFilters);

        $('#btn-reset-filters').on('click', function () {
            $('#filter-type, #filter-status').val('').trigger('change');
        });
    });
});
