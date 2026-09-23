
$(function () {
    $('.select2').select2({
        allowClear: true,
        dir: 'rtl'
    });

    $('#tenant-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const filters = {
                status: $('#filter-status').val() || '',
                statusClient: $('#filter-client').val() || '',
                // discount_type: $('#filter-type').val() || '',
                // employee: $('#filter-employee').val() || ''
            };

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (filters.status) params.append('status', filters.status);
            if (filters.statusClient) params.append('statusClient', filters.statusClient);
            // if (filters.discount_type) params.append('discount_type', filters.discount_type);
            // if (filters.employee) params.append('employee', filters.employee);

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

            table.ajax.url(newUrl).load();
        }

        $('#filter-status').on('change', applyFilters);
        $('#filter-client').on('change', applyFilters);
        // $('#filter-type').on('change', applyFilters);
        // $('#filter-employee').on('change', applyFilters);
    });
});