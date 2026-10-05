'use strict';

$(document).ready(function () {

    if ($('.select2').length) {
        $('.select2').each(function () {
            $(this).select2({
                dropdownAutoWidth: true,
                width: '180px',
                minimumResultsForSearch: 5,
                language: {
                    noResults: function () {
                        return 'لا توجد نتائج';
                    }
                }
            });
        });
    }

    if ($('#overdue-tasks-table').length) {
        initializeOverdueTasksTable();
    }

    $('#overdueDaysFilter').on('change', function () {
        if ($(this).val() === 'custom') {
            $('#customDateRange').removeClass('d-none').show();
        } else {
            $('#customDateRange').addClass('d-none').hide();
            $('#overdueDateFrom, #overdueDateTo').val('');
        }

        if ($.fn.DataTable.isDataTable('#overdue-tasks-table')) {
            $('#overdue-tasks-table').DataTable().ajax.reload();
        }
    });

    $('#overdueCompanyFilter, #overduePortfolioFilter, #overdueManagerFilter, #overdueResultTypeFilter').on('change', function () {
        if ($.fn.DataTable.isDataTable('#overdue-tasks-table')) {
            $('#overdue-tasks-table').DataTable().ajax.reload();
        }
    });

    $('#overdueDateFrom, #overdueDateTo').on('change', function () {
        if ($('#overdueDaysFilter').val() === 'custom' && $.fn.DataTable.isDataTable('#overdue-tasks-table')) {
            $('#overdue-tasks-table').DataTable().ajax.reload();
        }
    });

    $('#exportOverdueTasksExcel').on('click', function () {
        const params = new URLSearchParams({
            company_id: $('#overdueCompanyFilter').val() || '',
            portfolio_id: $('#overduePortfolioFilter').val() || '',
            relationship_manager_id: $('#overdueManagerFilter').val() || '',
            result_type: $('#overdueResultTypeFilter').val() || '',
            overdue_days: $('#overdueDaysFilter').val() !== 'custom' ? $('#overdueDaysFilter').val() : '',
            date_from: $('#overdueDateFrom').val() || '',
            date_to: $('#overdueDateTo').val() || ''
        });

        window.location.href = window.routes.exportOverdueTasks + '?' + params.toString();
    });
});

function initializeOverdueTasksTable() {
    if ($.fn.DataTable.isDataTable('#overdue-tasks-table')) {
        $('#overdue-tasks-table').DataTable().destroy();
    }

    $('#overdue-tasks-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.routes.overdueTasks,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: function (d) {
                d.company_id = $('#overdueCompanyFilter').val() || '';
                d.portfolio_id = $('#overduePortfolioFilter').val() || '';
                d.relationship_manager_id = $('#overdueManagerFilter').val() || '';
                d.result_type = $('#overdueResultTypeFilter').val() || '';

                const daysFilter = $('#overdueDaysFilter').val();
                if (daysFilter && daysFilter !== 'custom') {
                    d.overdue_days = daysFilter;
                }

                if (daysFilter === 'custom') {
                    d.date_from = $('#overdueDateFrom').val() || '';
                    d.date_to = $('#overdueDateTo').val() || '';
                }
            },
            error: function () {
                $('#overdue-tasks-table tbody').html(
                    '<tr><td colspan="10" class="text-center text-danger py-4">' +
                    '<div class="mb-2">حدث خطأ في تحميل البيانات</div>' +
                    '<small class="text-muted">يرجى المحاولة مرة أخرى</small>' +
                    '</td></tr>'
                );
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center', width: '5%' },
            { data: 'customer_name', name: 'customer_name', orderable: true, searchable: true },
            { data: 'account_number', name: 'account_number', orderable: true, searchable: true, className: 'text-center' },
            { data: 'company_name', name: 'company_name', orderable: true, searchable: true, className: 'text-center' },
            { data: 'portfolio_name', name: 'portfolio_name', orderable: true, searchable: true, className: 'text-center' },
            { data: 'task_type', name: 'task_type', orderable: true, searchable: false, className: 'text-center' },
            { data: 'scheduled_date', name: 'scheduled_date', orderable: true, searchable: false, className: 'text-center' },
            { data: 'overdue_days', name: 'overdue_days', orderable: true, searchable: false, className: 'text-center' },
            { data: 'remaining_debt', name: 'remaining_debt', orderable: true, searchable: false, className: 'text-center' },
            { data: 'relationship_manager', name: 'relationship_manager', orderable: true, searchable: true, className: 'text-center' }
        ],
        order: [[7, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        responsive: true,
        autoWidth: false,
        scrollX: false,
        language: {
            url: window.assets?.arabicDataTable || '/static/i18n/ar.json',
            processing: 'جاري التحميل...',
            emptyTable: '<div class="text-center py-4 d-flex flex-column align-items-center justify-content-center">' +
                '<i class="ti ti-inbox text-muted mb-3" style="font-size: 3.5rem; line-height:1;"></i>' +
                '<div class="h5 mb-1">لا توجد مهام متأخرة</div>' +
                '<small class="text-muted">لا توجد بيانات للعرض في الوقت الحالي</small>' +
                '</div>',
            zeroRecords: '<div class="text-center py-4 d-flex flex-column align-items-center justify-content-center">' +
                '<i class="ti ti-inbox text-muted mb-3" style="font-size: 3.5rem; line-height:1;"></i>' +
                '<div class="h5 mb-1">لا توجد مهام متأخرة</div>' +
                '<small class="text-muted">لا توجد بيانات للعرض في الوقت الحالي</small>' +
                '</div>'
        }
    });
}
