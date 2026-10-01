$(function () {
    $('.select2').select2({
        allowClear: true,
        dir: 'rtl'
    });

    $('#bill-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const filters = {
                type: $('#filter-type').val() || '',
                status: $('#filter-status').val() || '',
                payment_method: $('#filter-payment-method').val() || ''
            };

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (filters.type) params.append('type', filters.type);
            if (filters.status) params.append('status', filters.status);
            if (filters.payment_method) params.append('payment_method', filters.payment_method);

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');

            table.ajax.url(newUrl).load();
        }

        $('#filter-type').on('change', applyFilters);
        $('#filter-status').on('change', applyFilters);
        $('#filter-payment-method').on('change', applyFilters);

        $('#btn-reset-filters').on('click', function () {
            $('#filter-type, #filter-status, #filter-payment-method').val('').trigger('change');
        });
    });
});
