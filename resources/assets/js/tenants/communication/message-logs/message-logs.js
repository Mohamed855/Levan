/**
 * Message Logs Index - JavaScript Functions
 * ==========================================
 * إدارة جدول سجل الرسائل والتفاعلات
 */

'use strict';

// متغيرات عامة
let messageLogsTable;

$(document).ready(function() {
    initializeComponents();
    initializeDataTable();
    bindEvents();
});

/**
 * تهيئة المكونات الأساسية
 */
function initializeComponents() {
    // تهيئة Select2
    $('.select2').each(function() {
        $(this).select2({
            placeholder: function() {
                return $(this).data('placeholder');
            },
            allowClear: true,
            width: '100%',
            language: 'ar',
            dir: 'rtl',
            dropdownParent: $(this).closest('.card')
        });
    });

}

/**
 * تهيئة جدول البيانات
 */
function initializeDataTable() {
    messageLogsTable = $('#message-logs-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.routes.messageLogsIndex,
            data: function(d) {
                d.status = $('#filter-status').val();
                d.message_type = $('#filter-message-type').val();
                d.sender_id = $('#filter-sender').val();
                d.search = $('#filter-search').val();
            }
        },
        columns: getTableColumns(),
        order: [[8, 'desc']],
        dom: getTableDom(),
        buttons: getTableButtons(),
        language: {
            url: window.assets.arabicDataTable
        },
        orderCellsTop: true,
        columnDefs: getColumnDefs(),
        responsive: getResponsiveConfig(),
        createdRow: handleTableRowCreation
    });
}

/**
 * تعريف أعمدة الجدول
 */
function getTableColumns() {
    return [
        {
            data: "DT_RowIndex",
            name: "DT_RowIndex",
            orderable: false,
            searchable: false,
            className: 'control'
        },
        {
            data: 'checkbox',
            name: 'checkbox',
            orderable: false,
            searchable: false,
            className: 'custom-checkbox'
        },
        {
            data: 'sender_name',
            name: 'sender_name',
            className: 'table-ellipsis'
        },
        {
            data: 'message_preview',
            name: 'message_content',
            orderable: false,
            searchable: false,
            className: 'table-ellipsis'
        },
        {
            data: 'recipients_info',
            name: 'recipients_count',
            orderable: false,
            searchable: false
        },
        {
            data: 'status_badge',
            name: 'status'
        },
        {
            data: 'success_stats',
            name: 'success_stats',
            orderable: false,
            searchable: false
        },
        {
            data: 'timing_info',
            name: 'created_at',
            orderable: false,
            searchable: false
        },
        {
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false,
            className: 'text-center'
        },
        {
            data: 'id',
            name: 'id',
            visible: false
        }
    ];
}

/**
 * تعريف DOM للجدول
 */
function getTableDom() {
    return '<"dt-toolbar mb-3"' +
        '<"dt-toolbar-left d-flex flex-column flex-sm-row align-items-start align-items-sm-center"' +
        'f' +
        'l' +
        '>' +
        '<"dt-toolbar-right d-flex flex-column flex-sm-row align-items-end align-items-sm-center justify-content-between"' +
        'B' +
        '>' +
        '>' +
        'r<"table-responsive"t>' +
        '<"row mt-3"' +
        '<"d-flex flex-column flex-sm-row align-items-center justify-content-between w-100"' +
        'i' +
        '<"pagination-wrapper overflow-auto w-100"' +
        'p' +
        '>' +
        '>' +
        '>';
}

/**
 * تعريف أزرار الجدول
 */
function getTableButtons() {
    return [
        {
            extend: 'collection',
            className: 'btn btn-export',
            text: 'الإجراءات',
            buttons: [
                {
                    extend: 'copy',
                    text: 'نسخ'
                },
                {
                    extend: 'excel',
                    text: 'إكسل'
                },
                {
                    text: 'حذف المحدد',
                    className: 'btn btn-default btn-delete-selected',
                    action: handleMassDelete
                }
            ]
        }
    ];
}

/**
 * تعريف أعمدة الجدول
 */
function getColumnDefs() {
    return [{
        className: "control",
        orderable: false,
        targets: 0,
        render: function() {
            return "";
        }
    }];
}

/**
 * تعريف الاستجابة للجدول
 */
function getResponsiveConfig() {
    return {
        details: {
            display: $.fn.dataTable.Responsive.display.modal({
                header: function() {
                    return "التفاصيل";
                }
            }),
            type: "column",
            renderer: function(api, rowIdx, columns) {
                const data = $.map(columns, function(col) {
                    return col.title !== "" ?
                        '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                        '<td>' + col.title + ':</td>' +
                        '<td>' + col.data + '</td>' +
                        '</tr>' : "";
                }).join("");

                return data ? $('<table class="table"/><tbody />').append(data) : false;
            }
        }
    };
}

/**
 * معالجة إنشاء صفوف الجدول
 */
function handleTableRowCreation(row, data, dataIndex) {
    $(row).find('td.table-ellipsis').each(function() {
        const cellContent = $(this).html();
        if ($(this).find('a').length > 0) {
            const linkElement = $(this).find('a');
            const linkText = linkElement.text();
            linkElement.html('<div class="cell-content"><span>' + linkText + '</span></div>');
        } else {
            $(this).html('<div class="cell-content"><span>' + cellContent + '</span></div>');
        }
    });
}

/**
 * ربط الأحداث
 */
function bindEvents() {
    // تحديث الجدول عند البحث
    $('#filter-search').on('keyup', function() {
        clearTimeout(window.searchTimeout);
        window.searchTimeout = setTimeout(function() {
            messageLogsTable.draw();
        }, 500);
    });

    // تحديد الكل
    $('#select-all').on('click', function() {
        const rows = messageLogsTable.rows({ 'search': 'applied' }).nodes();
        $('input[type="checkbox"]', rows).prop('checked', this.checked);
    });

}

/**
 * معالجة الحذف الجماعي
 */
function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function() {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد رسالة واحدة على الأقل');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'MessageLog');
}

/**
 * حذف رسالة واحدة
 */
function deleteMessage(id) {
    confirmDelete(id, window.routes.messageLogsDestroy.replace(':id', id), 'MessageLog');
}

// تصدير الدوال للاستخدام العام
window.deleteMessage = deleteMessage;
