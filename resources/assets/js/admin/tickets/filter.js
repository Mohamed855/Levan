
$(function () {
    $('.select2').select2({
        allowClear: true,
        dir: 'rtl'
    });

    $('#admin-tickets-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const filters = {
                status: $('#filter-status').val() || '',
                section: $('#filter-section').val() || '',
                tenant_id: $('#filter-tenant').val() || ''
            };

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (filters.status) params.append('status', filters.status);
            if (filters.section) params.append('section', filters.section);
            if (filters.tenant_id) params.append('tenant_id', filters.tenant_id);

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

            table.ajax.url(newUrl).load();
        }

        $('#filter-status').on('change', applyFilters);
        $('#filter-section').on('change', applyFilters);
        $('#filter-tenant').on('change', applyFilters);
    });
});
