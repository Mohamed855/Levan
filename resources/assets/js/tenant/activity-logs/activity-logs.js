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
            dir: 'rtl'
        });
    });

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
        responsive: false,
        createdRow: handleTableRowCreation
    });

    // حقن قائمة "تحديد الصفحة / تحديد الكل" داخل رأس عمود الاختيار
    $('#activity-logs-table').on('init.dt', function () {
        injectCheckboxHeader();
        ActivityLogSelection.initialize();
    });

    // تحديث حالة التحديد بعد كل رسم للجدول (تغيير صفحة، إلخ)
    activityLogsTable.on('draw.dt', function () {
        ActivityLogSelection.updateAfterDraw();
    });
}

/**
 * حقن قائمة "تحديد الصفحة / تحديد الكل" في رأس عمود الاختيار
 */
function injectCheckboxHeader() {
    const checkboxHeaderHtml = `
        <div class="d-flex justify-content-center h-100 w-100" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false" tabindex="0" id="selectAllDropdownCell">
            <input type="checkbox" id="select-all" disabled style="pointer-events: none;">
            <i class="ti ti-chevron-down ms-3"></i>
        </div>
        <ul class="dropdown-menu p-2" aria-labelledby="selectAllDropdownCell" style="min-width: 150px;">
            <li>
                <a href="#" class="dropdown-item d-flex align-items-center" id="select-page">
                    <input type="checkbox" class="row-checkbox me-2" id="selectPageCheckbox" disabled>
                    <span>الصفحة</span>
                </a>
            </li>
            <li>
                <a href="#" class="dropdown-item d-flex align-items-center" id="select-all-items">
                    <input type="checkbox" class="row-checkbox me-2" id="selectAllCheckbox" disabled>
                    <span>الكل</span>
                </a>
            </li>
        </ul>
    `;

    $('#activity-logs-table thead th').eq(0).html(checkboxHeaderHtml);
}

/**
 * تعريف أعمدة الجدول
 */
function getTableColumns() {
    return [
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
        {
            data: 'subject_info',
            name: 'subject_info',
            orderable: false,
            searchable: false
        },
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
            extend: 'excelHtml5',
            className: 'btn btn-export',
            text: 'تصدير Excel',
            action: handleExportActivityLogs
        }
    ];
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
    $('#activity-logs-table').on('preXhr.dt', function () {
        ActivityLogSelection.clearAll();
    });

}

/**
 * معالجة التصدير إلى Excel
 */
function handleExportActivityLogs() {
    const selectedIds = ActivityLogSelection.getSelectedIds();

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد سجل واحد على الأقل للتصدير');
        return;
    }

    showActivityLogsExportProgressModal();

    $.ajax({
        url: window.routes.activityLogsExport,
        type: 'POST',
        data: {
            _token: window.csrfToken,
            ids: JSON.stringify(selectedIds)
        },
        success: (response) => {
            if (!response.success) {
                hideActivityLogsExportProgressModal();
                toastr.error(response.message || 'حدث خطأ أثناء تصدير البيانات');
                return;
            }

            showActivityLogsExportReadyModal(response.file_name, response.count);
        },
        error: (xhr) => {
            hideActivityLogsExportProgressModal();
            const message = xhr.responseJSON?.message || 'حدث خطأ أثناء تصدير البيانات';
            toastr.error(message);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Export Progress Modal
|--------------------------------------------------------------------------
*/
const ACTIVITY_LOGS_EXPORT_PROGRESS_TITLE_HTML = '<i class="ti ti-file-export me-2 text-primary"></i>جاري تصدير السجلات';
const ACTIVITY_LOGS_EXPORT_PROGRESS_BODY_HTML = `
    <div class="progress mb-3 position-relative" style="height: 25px; border-radius: 0.75rem; overflow: hidden;">
        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 100%;"></div>
    </div>
    <div class="d-flex justify-content-center align-items-center mt-3">
        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
        <small class="text-primary fw-medium">الرجاء الانتظار حتى تحضير الملف</small>
    </div>
`;

function showActivityLogsExportProgressModal() {
    if ($('#activityLogsExportModal').length === 0) {
        $('body').append(`
        <div class="modal fade" id="activityLogsExportModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                    </div>
                    <div class="modal-body text-center"></div>
                </div>
            </div>
        </div>`);
    }

    $('#activityLogsExportModal .modal-title').html(ACTIVITY_LOGS_EXPORT_PROGRESS_TITLE_HTML);
    $('#activityLogsExportModal .modal-body').html(ACTIVITY_LOGS_EXPORT_PROGRESS_BODY_HTML);

    $('#activityLogsExportModal').modal('show');
}

/**
 * لا نفتح التنزيل تلقائيًا لأن المتصفحات تحجب النوافذ المنبثقة غير الناتجة
 * مباشرة عن تفاعل المستخدم؛ لذلك نعرض زر تحميل حقيقي بدلاً من ذلك.
 */
function showActivityLogsExportReadyModal(fileName, count) {
    if (!fileName || !window.routes.activityLogsExportDownload) {
        hideActivityLogsExportProgressModal();
        return;
    }

    const url = window.routes.activityLogsExportDownload.replace('FILE_PLACEHOLDER', encodeURIComponent(fileName));

    $('#activityLogsExportModal .modal-title').html('<i class="ti ti-circle-check me-2 text-success"></i>اكتمل التصدير');
    $('#activityLogsExportModal .modal-body').html(`
        <div class="text-center">
            <i class="ti ti-file-spreadsheet text-success mb-3" style="font-size: 3rem;"></i>
            <p class="mb-3">تم تصدير ${count ?? ''} سجل بنجاح</p>
            <a href="${url}" target="_blank" class="btn btn-success" id="activityLogsExportDownloadBtn">
                <i class="ti ti-download me-1"></i> تحميل الملف
            </a>
        </div>
    `);

    $('#activityLogsExportDownloadBtn').off('click').on('click', () => {
        setTimeout(() => hideActivityLogsExportProgressModal(), 200);
    });
}

function hideActivityLogsExportProgressModal() {
    $('#activityLogsExportModal').modal('hide');
}

/**
 * معالجة الحذف الجماعي
 */
function handleMassDelete() {
    const selectedIds = ActivityLogSelection.getSelectedIds();

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد نشاط واحد على الأقل');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Activity');
}
