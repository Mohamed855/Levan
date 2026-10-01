
$(function () {
    $('.select2').select2({
        allowClear: true,
        dir: 'rtl'
    });

    $('#bill-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const filters = {
                type: $('#filter-status').val() || '',
                status: $('#filter-paid').val() || '',
                payment_method: $('#filter-type').val() || ''
            };

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (filters.type) params.append('type', filters.type);
            if (filters.status) params.append('status', filters.status);
            if (filters.payment_method) params.append('payment_method', filters.payment_method);

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

            table.ajax.url(newUrl).load();
        }

        $('#filter-status').on('change', applyFilters);
        $('#filter-paid').on('change', applyFilters);
        $('#filter-type').on('change', applyFilters);

        $('#btn-reset-filters').on('click', function () {
            $('#filter-status, #filter-paid, #filter-type').val('').trigger('change');
        });
    });
});