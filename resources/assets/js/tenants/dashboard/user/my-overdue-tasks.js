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

  if ($('#my-overdue-tasks-table').length) {
    initializeMyOverdueTasksTable();
  }

  $('#myOverdueDaysFilter').on('change', function () {
    if ($(this).val() === 'custom') {
      $('#myCustomDateRange').removeClass('d-none').show();
    } else {
      $('#myCustomDateRange').addClass('d-none').hide();
      $('#myOverdueDateFrom, #myOverdueDateTo').val('');
    }

    if ($.fn.DataTable.isDataTable('#my-overdue-tasks-table')) {
      $('#my-overdue-tasks-table').DataTable().ajax.reload();
    }
  });

  $('#myOverdueResultTypeFilter').on('change', function () {
    if ($.fn.DataTable.isDataTable('#my-overdue-tasks-table')) {
      $('#my-overdue-tasks-table').DataTable().ajax.reload();
    }
  });

  $('#myOverdueDateFrom, #myOverdueDateTo').on('change', function () {
    if ($('#myOverdueDaysFilter').val() === 'custom' && $.fn.DataTable.isDataTable('#my-overdue-tasks-table')) {
      $('#my-overdue-tasks-table').DataTable().ajax.reload();
    }
  });

  $('#exportMyOverdueTasksExcel').on('click', function () {
    const params = new URLSearchParams({
      result_type: $('#myOverdueResultTypeFilter').val() || '',
      overdue_days: $('#myOverdueDaysFilter').val() !== 'custom' ? $('#myOverdueDaysFilter').val() : '',
      date_from: $('#myOverdueDateFrom').val() || '',
      date_to: $('#myOverdueDateTo').val() || ''
    });

    window.location.href = window.userRoutes.exportMyOverdueTasks + '?' + params.toString();
  });
});

function initializeMyOverdueTasksTable() {
  if ($.fn.DataTable.isDataTable('#my-overdue-tasks-table')) {
    $('#my-overdue-tasks-table').DataTable().destroy();
  }

  $('#my-overdue-tasks-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: window.userRoutes.myOverdueTasks,
      type: 'GET',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      data: function (d) {
        d.result_type = $('#myOverdueResultTypeFilter').val() || '';

        const daysFilter = $('#myOverdueDaysFilter').val();
        if (daysFilter && daysFilter !== 'custom') {
          d.overdue_days = daysFilter;
        }

        if (daysFilter === 'custom') {
          d.date_from = $('#myOverdueDateFrom').val() || '';
          d.date_to = $('#myOverdueDateTo').val() || '';
        }
      },
      error: function () {
        $('#my-overdue-tasks-table tbody').html(
          '<tr><td colspan="9" class="text-center text-danger py-4">' +
          '<div class="mb-2">حدث خطأ في تحميل البيانات</div>' +
          '<small class="text-muted">يرجى المحاولة مرة أخرى</small>' +
          '</td></tr>'
        );
      }
    },
    columns: [
      { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center', width: '6%' },
      { data: 'customer_name', name: 'customer_name', orderable: true, searchable: true },
      { data: 'account_number', name: 'account_number', orderable: true, searchable: true, className: 'text-center' },
      { data: 'company_name', name: 'company_name', orderable: true, searchable: true, className: 'text-center' },
      { data: 'portfolio_name', name: 'portfolio_name', orderable: true, searchable: true, className: 'text-center' },
      { data: 'task_type', name: 'task_type', orderable: true, searchable: false, className: 'text-center' },
      { data: 'scheduled_date', name: 'scheduled_date', orderable: true, searchable: false, className: 'text-center' },
      { data: 'overdue_days', name: 'overdue_days', orderable: true, searchable: false, className: 'text-center' },
      { data: 'remaining_debt', name: 'remaining_debt', orderable: true, searchable: false, className: 'text-center' }
    ],
    order: [[7, 'desc']],
    pageLength: 10,
    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
    responsive: true,
    autoWidth: false,
    scrollX: false,
    language: {
      url: window.assets?.arabicDataTable || '/assets/json/ar.json',
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