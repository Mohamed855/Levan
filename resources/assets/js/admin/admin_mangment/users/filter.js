
$(function () {
    $('.select2').select2({
        allowClear: true,
        dir: 'rtl'
    });

    $('#users-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const filters = {
                status: $('#filter-status').val() || '',
                role: $('#filter-role').val() || '',
                // employee: $('#filter-employee').val() || ''
            };

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (filters.status) params.append('status', filters.status);
            if (filters.role) params.append('role', filters.role);
            // if (filters.employee) params.append('employee', filters.employee);

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

            table.ajax.url(newUrl).load();
        }

        $('#filter-status').on('change', applyFilters);
        $('#filter-role').on('change', applyFilters);
        // $('#filter-employee').on('change', applyFilters);
    });
});