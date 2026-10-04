/**
 * Customer Distribution Index - JavaScript
 * ========================================
 * معالجة صفحة فهرس توزيع العملاء مع DataTables والفلاتر
 */

'use strict';

$(function () {
    $('.select2').select2({
        allowClear: true,
        placeholder: 'الكل',
        dir: 'rtl'
    });

    $('#distributions-table').on('init.dt', function () {
        const table = $(this).DataTable();



        const tableWrapper = $('#distributions-table_wrapper');

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

        // النقر على الصفوف للتنقل
        tableWrapper.on('click', '#distributions-table tbody tr', function (e) {
            // تجاهل النقر على checkbox
            if ($(e.target).is('input[type="checkbox"]') || $(e.target).closest('td:first-child').length) {
                return;
            }

            const data = table.row(this).data();
            if (data && data.batch_id) {
                window.location.href = `${window.distributionConfig.routes.distributionStatistics}?batch_id=${data.batch_id}`;
            }
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

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'CustomerDistribution');
}

window.handleMassDelete = handleMassDelete;