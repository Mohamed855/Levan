
$(function () {
    $('.select2').select2({
        allowClear: true,
        dir: 'rtl'
    });

    $('#coupon-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const filters = {
                is_active: $('#filter-status').val() || '',
                discount_type: $('#filter-type').val() || '',
                // employee: $('#filter-employee').val() || ''
            };

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (filters.is_active) params.append('is_active', filters.is_active);
            if (filters.discount_type) params.append('discount_type', filters.discount_type);
            // if (filters.employee) params.append('employee', filters.employee);

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

            table.ajax.url(newUrl).load();
        }

        $('#filter-status').on('change', applyFilters);
        $('#filter-type').on('change', applyFilters);

        $('#btn-reset-filters').on('click', function () {
            $('#filter-status, #filter-type').val('').trigger('change');
        });
    });
});