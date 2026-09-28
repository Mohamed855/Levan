/**
 * Message Templates Index - JavaScript
 * ===================================
 */

'use strict';

// متغيرات عامة
let messageTemplatesTable;

// تهيئة التطبيق
$(document).ready(function () {
  initializeComponents();
  initializeDataTable();
  bindEvents();
  loadStatistics();
});

/**
 * تهيئة المكونات الأساسية
 */
function initializeComponents() {
  // تهيئة Select2
  $('.select2').each(function () {
    $(this).select2({
      placeholder: function () {
        return $(this).data('placeholder');
      },
      allowClear: true,
      width: '100%',
      language: 'ar',
      dir: 'rtl'
    });
  });
}

/**
 * تهيئة جدول البيانات
 */
function initializeDataTable() {
  messageTemplatesTable = $('#message-templates-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: window.routes.messageTemplatesIndex,
      data: function (d) {
        d.category = $('#filter-category').val();
        d.channel  = $('#filter-channel').val();   // ✅ فلتر القناة
        d.status   = $('#filter-status').val();
        d.search   = $('#filter-search').val();
      }
    },
    columns: [
      {
        data: "DT_RowIndex",
        name: "DT_RowIndex",
        orderable: false,
        searchable: false,
        className: 'control'
      },
      { data: 'name',            name: 'name' },
      { data: 'category_label',  name: 'category' },
      // ملاحظة: إن كان السيرفر يعيد (channel_label) كبادج استخدمه، وإلا استخدم 'channel'
      { data: 'channel_label',   name: 'channel', defaultContent: '' },
      { data: 'status',          name: 'is_active' },
      {
        data: 'creator_name',
        name: 'creator_name',
        orderable: false,
        searchable: false
      },
      { data: 'created_at',      name: 'created_at' },
      {
        data: 'actions',
        name: 'actions',
        orderable: false,
        searchable: false,
        className: 'text-center'
      }
    ],
    // created_at بالعمود السابع (index=6)
    order: [[6, 'desc']],
    dom: getDom(),
    buttons: getButtons(),
    language: { url: window.assets.arabicDataTable },
    orderCellsTop: true,
    columnDefs: [
      {
        className: "control",
        orderable: false,
        targets: 0,
        render: function () { return ""; }
      }
    ],
    responsive: {
      details: {
        display: $.fn.dataTable.Responsive.display.modal({
          header: function () { return "التفاصيل"; }
        }),
        type: "column",
        renderer: function (api, rowIdx, columns) {
          const data = $.map(columns, function (col) {
            return col.title !== "" ?
              '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
              '<td>' + col.title + ':</td>' +
              '<td>' + col.data + '</td>' +
              '</tr>' : "";
          }).join("");

          return data ? $('<table class="table"/><tbody />').append(data) : false;
        }
      }
    }
  });
}

/**
 * تعريف DOM للجدول
 */
function getDom() {
  return '<"dt-toolbar mb-3"' +
    '<"dt-toolbar-left d-flex flex-column flex-sm-row align-items-start align-items-sm-center" f l >' +
    '<"dt-toolbar-right d-flex flex-column flex-sm-row align-items-end align-items-sm-center justify-content-between" B >' +
    '>' +
    'rt' +
    '<"row mt-3"' +
    '<"d-flex flex-column flex-sm-row align-items-center justify-content-between w-100" i ' +
    '<"pagination-wrapper overflow-auto w-100" p >' +
    '>' +
    '>';
}

/**
 * تعريف أزرار الجدول
 */
function getButtons() {
  return [
    {
      text: '<i class="fas fa-plus-circle me-1"></i> إضافة قالب',
      className: 'btn btn-primary',
      action: function () {
        window.location.href = window.routes.messageTemplatesCreate;
      }
    }
  ];
}

/**
 * ربط الأحداث
 */
function bindEvents() {
  // تحديث الجدول عند تغيير الفلاتر
  $('#filter-category, #filter-channel, #filter-status').on('change', function () {
    messageTemplatesTable.draw();
  });

  // تحديث الجدول عند البحث
  $('#filter-search').on('keyup', function () {
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(function () {
      messageTemplatesTable.draw();
    }, 500);
  });
}

/**
 * تبديل حالة القالب
 */
function toggleTemplateStatus(templateId) {
  const statusElement = event.target;

  $.ajax({
    url: window.routes.toggleStatus.replace(':id', templateId),
    type: 'PATCH',
    data: { _token: window.csrfToken },
    success: function (response) {
      if (response.success) {
        updateStatusElement(statusElement, response.new_status);
        toastr.success(response.message);
        loadStatistics(); // تحديث الإحصائيات
      } else {
        toastr.error(response.message);
      }
    },
    error: function () {
      toastr.error('حدث خطأ أثناء تبديل الحالة');
    }
  });
}

/**
 * تحديث عنصر الحالة
 */
function updateStatusElement(element, newStatus) {
  if (newStatus) {
    element.className = 'badge bg-label-success cursor-pointer';
    element.textContent = 'نشط';
  } else {
    element.className = 'badge bg-label-danger cursor-pointer';
    element.textContent = 'غير نشط';
  }
}

// تصدير الدوال للاستخدام العام
window.toggleTemplateStatus = toggleTemplateStatus;
window.confirmDelete = confirmDelete;
