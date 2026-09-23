
$(function () {
    $('.select2').select2({
        allowClear: true,
        dir: 'rtl'
    });

    $('#paln-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const filters = {
                is_active: $('#filter-status').val() || '',
                domain_management_enabled: $('#filter-domain').val() || '',
            };

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (filters.is_active) params.append('is_active', filters.is_active);
            if (filters.domain_management_enabled) params.append('domain_management_enabled', filters.domain_management_enabled);

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

            table.ajax.url(newUrl).load();
        }

        $('#filter-status').on('change', applyFilters);
        $('#filter-domain').on('change', applyFilters);
    });
});
