/**
 * Activity Logs Index - JavaScript Functions
 * ==========================================
 * إدارة تحديد الصفوف والتصدير المجمع لسجل النشاطات (Tenant)
 */

'use strict';

$(document).ready(function () {
    setupActivityLogTable();
});

function setupActivityLogTable() {
    const $table = $('#activity-logs-table');
    if ($table.length === 0) return;

    const initializeSelectionHeader = () => {
        if ($table.data('activity-selection-initialized')) return;
        $table.data('activity-selection-initialized', true);

        injectCheckboxHeader();
        if (window.ActivityLogSelection) {
            window.ActivityLogSelection.initialize();
        }
    };

    if ($.fn.DataTable && $.fn.DataTable.isDataTable($table[0])) {
        initializeSelectionHeader();
    } else {
        $table.on('init.dt.activityLogSelection', initializeSelectionHeader);
    }

    let lastFilterSignature = window.ActivityLogSelection
        ? JSON.stringify(window.ActivityLogSelection.getCurrentFilters())
        : '';

    // Bind on document after shared datatable-page-defaults so ActivityLogSelection state wins.
    $(document)
        .off('draw.dt.activityLogSelection', '#activity-logs-table')
        .on('draw.dt.activityLogSelection', '#activity-logs-table', function () {
            window.ActivityLogSelection?.updateAfterDraw();
        })
        .off('preXhr.dt.activityLogSelection', '#activity-logs-table')
        .on('preXhr.dt.activityLogSelection', '#activity-logs-table', function () {
            if (!window.ActivityLogSelection) return;

            const currentSignature = JSON.stringify(window.ActivityLogSelection.getCurrentFilters());
            if (currentSignature !== lastFilterSignature) {
                lastFilterSignature = currentSignature;
                window.ActivityLogSelection.clearAll();
                return;
            }

            if (!window.ActivityLogSelection.state.isSelectingAll) {
                window.ActivityLogSelection.clearAll();
            }
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
                    <input type="checkbox" class="selection-option-checkbox me-2" id="selectPageCheckbox" disabled>
                    <span>الصفحة</span>
                </a>
            </li>
            <li>
                <a href="#" class="dropdown-item d-flex align-items-center" id="select-all-items">
                    <input type="checkbox" class="selection-option-checkbox me-2" id="selectAllCheckbox" disabled>
                    <span>الكل</span>
                </a>
            </li>
        </ul>
    `;

    $('#activity-logs-table thead th').eq(0).html(checkboxHeaderHtml);
}

/**
 * معالجة التصدير إلى Excel
 */
function handleExportActivityLogs() {
    const selectedIds = window.ActivityLogSelection?.getSelectedIds() ?? [];

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
            ids: JSON.stringify(selectedIds),
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
        },
    });
}

window.handleExportActivityLogs = handleExportActivityLogs;

/*
|--------------------------------------------------------------------------
| Export Progress Modal
|--------------------------------------------------------------------------
*/
const ACTIVITY_LOGS_EXPORT_PROGRESS_TITLE_HTML =
    '<i class="ti ti-file-export me-2 text-primary"></i>جاري تصدير السجلات';
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
 * مباشرة عن تفاعل المستخدم؛ لذلك نعرض زر تحميل حقيقي.
 */
function showActivityLogsExportReadyModal(fileName, count) {
    if (!fileName || !window.routes.activityLogsExportDownload) {
        hideActivityLogsExportProgressModal();
        return;
    }

    const url = window.routes.activityLogsExportDownload.replace(
        'FILE_PLACEHOLDER',
        encodeURIComponent(fileName),
    );

    $('#activityLogsExportModal .modal-title').html(
        '<i class="ti ti-circle-check me-2 text-success"></i>اكتمل التصدير',
    );
    $('#activityLogsExportModal .modal-body').html(`
        <div class="text-center">
            <i class="ti ti-file-spreadsheet text-success mb-3" style="font-size: 3rem;"></i>
            <p class="mb-3">تم تصدير ${count ?? ''} سجل بنجاح</p>
            <a href="${url}" target="_blank" class="btn btn-success" id="activityLogsExportDownloadBtn">
                <i class="ti ti-download me-1"></i> تحميل الملف
            </a>
        </div>
    `);

    $('#activityLogsExportDownloadBtn')
        .off('click')
        .on('click', () => {
            setTimeout(() => hideActivityLogsExportProgressModal(), 200);
        });
}

function hideActivityLogsExportProgressModal() {
    $('#activityLogsExportModal').modal('hide');
}
