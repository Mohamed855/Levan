'use strict';

$(function () {
    $('.select2').select2({
        allowClear: true,
        dir: 'rtl'
    });

    $('#paln-table').on('init.dt', function () {
        const table = $(this).DataTable();

        const filterIds = [
            '#filter-status',
            '#filter-plan-type',
            '#filter-billing-cycle',
            '#filter-limits-mode',
            '#filter-availability',
            '#filter-discounts',
            '#filter-domain',
            '#filter-most-requested',
        ];

        function applyFilters() {
            const filters = {
                is_active: $('#filter-status').val() || '',
                plan_type: $('#filter-plan-type').val() || '',
                subscription_billing_cycle: $('#filter-billing-cycle').val() || '',
                limits_mode: $('#filter-limits-mode').val() || '',
                availability: $('#filter-availability').val() || '',
                discounts: $('#filter-discounts').val() || '',
                domain_management_enabled: $('#filter-domain').val() || '',
                most_requested: $('#filter-most-requested').val() || '',
            };

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            Object.entries(filters).forEach(([key, val]) => {
                if (val !== '') {
                    params.append(key, val);
                }
            });

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');
            table.ajax.url(newUrl).load();
        }

        $(filterIds.join(', ')).on('change', applyFilters);

        $('#btn-reset-filters').on('click', function () {
            $(filterIds.join(', ')).val('').trigger('change');
        });
    });
});
