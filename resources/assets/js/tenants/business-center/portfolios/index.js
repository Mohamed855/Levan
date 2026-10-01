'use strict';

import { initializeTableStatusToggles } from '../../../shared/table-status-toggle';

$(function () {
    initializeTableStatusToggles();

    $('.select2').select2({
        allowClear: true,
        placeholder: 'الكل',
        dir: 'rtl'
    });

    $('#portfolios-table').on('init.dt', function () {
        const table = $(this).DataTable();

        function applyFilters() {
            const filters = {
                status: $('#filter-status').val() || '',
                company_id: $('#filter-company').val() || '',
            };
            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            for (const key in filters) {
                if (filters[key]) {
                    params.append(key, filters[key]);
                }
            }

            const newUrl = baseUrl + '?' + params.toString();
            table.ajax.url(newUrl).load();
        }

        $('#filter-status, #filter-company').on('change', applyFilters);

        $('#btn-reset-filters').on('click', function () {
            $('#filter-status, #filter-company').val('').trigger('change');
        });

    
        const tableWrapper = $('#portfolios-table_wrapper');

        tableWrapper.on('click', '#select-all', function () {
            const rows = table.rows({ 'search': 'applied' }).nodes();
            $('input.row-checkbox', rows).prop('checked', this.checked);
        });

        tableWrapper.on('change', '.row-checkbox', function () {
            if (!this.checked) {
                $('#select-all').prop('checked', false);
            }
        });

        table.on('draw', function () {
            $('#select-all').prop('checked', false);
        });
    });
});

function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length < 1) {
        toastr.warning('يرجى تحديد عنصر واحد على الأقل.');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Portfolio');
}

window.handleMassDelete = handleMassDelete;
