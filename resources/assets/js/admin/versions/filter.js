$(function () {
    $('.select2').select2({
        allowClear: true,
        dir: 'rtl'
    });

    $('#versions-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const filters = {
                stage: $('#filter-stage').val() || '',
                is_active: $('#filter-status').val() || ''
            };

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (filters.stage) params.append('stage', filters.stage);
            if (filters.is_active !== '') params.append('is_active', filters.is_active);

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

            table.ajax.url(newUrl).load();
        }

        $('#filter-stage').on('change', applyFilters);
        $('#filter-status').on('change', applyFilters);

        $('#btn-reset-filters').on('click', function () {
            $('#filter-stage, #filter-status').val('').trigger('change');
        });
    });
});
