/**
 * Activity Logs Index - JavaScript Functions
 * ==========================================
 * إدارة جدول سجل النشاطات والتفاعلات
 */

'use strict';

// متغيرات عامة
let activityLogsTable;

$(document).ready(function () {
    initializeComponents();
    initializeDataTable();
    bindEvents();
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
            dir: 'rtl',
            dropdownParent: $(this).closest('.card')
        });
    });

    // تهيئة التحقق من النماذج
    initializeFormValidation();
}

/**
 * تهيئة جدول البيانات
 */
function initializeDataTable() {
    activityLogsTable = $('#activity-logs-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.routes.activityLogsIndex,
            data: function (d) {
                d.user_id = $('#filter-user').val();
                d.log_name = $('#filter-log-name').val();
                d.date_from = $('#filter-date-from').val();
                d.date_to = $('#filter-date-to').val();
            }
        },
        columns: getTableColumns(),
        order: [[5, 'desc']],
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
            data: 'log_name_formatted',
            name: 'log_name',
            orderable: false,
            searchable: false
        },
        {
            data: 'description',
            name: 'description',
            className: 'table-ellipsis'
        },
        {
            data: 'causer_name',
            name: 'causer_name',
            className: 'table-ellipsis'
        },
        // {
        //     data: 'subject_info',
        //     name: 'subject_info',
        //     orderable: false,
        //     searchable: false
        // },
        {
            data: 'created_at',
            name: 'created_at',
            orderable: true,
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
        'rt' +
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
        render: function () {
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
                header: function () {
                    return "التفاصيل";
                }
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
    };
}

/**
 * معالجة إنشاء صفوف الجدول
 */
function handleTableRowCreation(row, data, dataIndex) {
    $(row).find('td.table-ellipsis').each(function () {
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
    // تحديد الكل
    $('#select-all').on('click', function () {
        const rows = activityLogsTable.rows({ 'search': 'applied' }).nodes();
        $('input[type="checkbox"]', rows).prop('checked', this.checked);
    });

    // كود تحريك النص
    $(document).on('mouseenter', '.table-ellipsis', handleTextAnimation);
    $(document).on('mouseleave', '.table-ellipsis', resetTextAnimation);
}

/**
 * معالجة الحذف الجماعي
 */
function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد نشاط واحد على الأقل');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Activity');
}

/**
 * معالجة تحريك النص
 */
function handleTextAnimation() {
    const span = $(this).find('span');
    const textWidth = span.width();
    const cellWidth = $(this).width();

    if (textWidth > cellWidth) {
        span.css('transform', 'translateX(' + (textWidth - cellWidth) + 'px)');
    }
}

/**
 * إعادة تعيين تحريك النص
 */
function resetTextAnimation() {
    $(this).find('span').css('transform', 'translateX(0)');
}

/**
 * تهيئة التحقق من النماذج
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);
    });
}
