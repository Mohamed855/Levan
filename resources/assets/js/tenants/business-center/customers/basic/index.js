/**
 * Customers Index - Updated for Ultra Fast Selection
 * ==================================================
 * الملف المحدث لنظام التحديد فائق السرعة
 */

'use strict';

/*
|--------------------------------------------------------------------------
| Global Variables
|--------------------------------------------------------------------------
| متغيرات عامة
*/
const currentViewType = window.viewType || 'all';
let customersExportPollInterval = null;

/*
|--------------------------------------------------------------------------
| Application Initialization
|--------------------------------------------------------------------------
| تهيئة التطبيق
*/
$(document).ready(function () {
    initializeComponents();

    // انتظار تهيئة DataTable
    $('#customers-table').on('init.dt', function () {

        // 👇 حل مشكلة ظهور الكود في الهيدر
        const checkboxHeaderHtml = `
        <div class="d-flex justify-content-center h-100 w-100" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false" tabindex="0" id="selectAllDropdownCell">
            <input type="checkbox" id="select-all" disabled style="pointer-events: none;">
            <i class="ti ti-chevron-down ms-3"></i>
        </div>
        <ul class="dropdown-menu p-2" aria-labelledby="selectAllDropdownCell" style="min-width: 180px;">
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

        // 👇 نحط الهيدر في العمود التاني (checkbox column)
        $('#customers-table thead th').eq(1).html(checkboxHeaderHtml);

        // تهيئة النظام الجديد فوراً
        CustomerSelection.initialize();
        CustomerRowColors.initialize();

        // إعداد الفلاتر بدون تداخل مع البحث
        setupOptimizedFilters();

        // ربط الأحداث
        bindTableEvents();
    });
});

/*
|--------------------------------------------------------------------------
| Component Initialization
|--------------------------------------------------------------------------
| تهيئة المكونات
*/
function initializeComponents() {
    // ✅ تهيئة Select2
    $('.filters .select2').each(function () {
        $(this).select2({
            placeholder: $(this).data('placeholder') || 'اختر',
            allowClear: true,
            width: '100%',
            language: 'ar',
            dir: 'rtl',
            dropdownParent: $(this).closest('.card-body')
        });
    });

    // ✅ معالج فلتر نتيجة الاتصال
    $('#filter-contact-result').on('change', function () {
        const table = $('#customers-table').DataTable();
        table.page(0);
        table.ajax.reload(null, false);
        CustomerSelection.clearAll();
    });

    // ✅ تفعيل التحكم في حقول تاريخ الإرسال
    setupSMSDateFieldsControl();
}

/*
|--------------------------------------------------------------------------
| SMS Date Fields Control
|--------------------------------------------------------------------------
| التحكم في حقول تاريخ الإرسال بناءً على حالة الإرسال
*/
function setupSMSDateFieldsControl() {
    const smsStatusFilter = $('#filter-sms-status');
    const smsDateFrom = $('#filter-sms-date-from');
    const smsDateTo = $('#filter-sms-date-to');

    // دالة التحكم في حالة حقول التاريخ
    function updateDateFieldsState() {
        const selectedStatus = smsStatusFilter.val();

        if (selectedStatus === 'not_sent') {
            // ✅ تعطيل حقول التاريخ (لكن تبقى ظاهرة)
            smsDateFrom.prop('disabled', true).val('');
            smsDateTo.prop('disabled', true).val('');

            // إضافة تنسيق بصري للتوضيح
            smsDateFrom.addClass('bg-lighter text-muted');
            smsDateTo.addClass('bg-lighter text-muted');
        } else {
            // ✅ تفعيل حقول التاريخ
            smsDateFrom.prop('disabled', false);
            smsDateTo.prop('disabled', false);

            // إزالة التنسيق البصري
            smsDateFrom.removeClass('bg-lighter text-muted');
            smsDateTo.removeClass('bg-lighter text-muted');
        }
    }

    // ✅ تطبيق التحكم عند تغيير حالة الإرسال
    smsStatusFilter.on('change', function () {
        updateDateFieldsState();
    });

    // ✅ تطبيق الحالة الأولية عند تحميل الصفحة
    updateDateFieldsState();
}

/*
|--------------------------------------------------------------------------
| Optimized Filters Setup - No Interference with Search
|--------------------------------------------------------------------------
| إعداد الفلاتر المحسنة - بدون تداخل مع البحث
*/
function setupOptimizedFilters() {
    const table = $('#customers-table').DataTable();
    let filterTimeout;

    // معالج الفلاتر المخصصة
    $('#filter-status, #filter-customer-type, #filter-company, #filter-portfolio, #filter-nationality, #filter-manager, #filter-contact-result, #filter-debt-min, #filter-debt-max, #filter-sms-status, #filter-sms-date-from, #filter-sms-date-to')
        .on('change keyup', function () {
            // ✅ منع التحديث إذا كان الحقل معطل
            if ($(this).prop('disabled')) {
                return;
            }

            // مسح أي تأخير سابق
            clearTimeout(filterTimeout);

            // تطبيق الفلاتر مع تأخير بسيط
            filterTimeout = setTimeout(() => {
                // مسح البحث العام عند استخدام الفلاتر
                table.search('');

                // إعادة تحميل الجدول
                table.page(0);
                table.ajax.reload(null, false);

                // مسح التحديد فوراً
                CustomerSelection.clearAll();
            }, 200);
        });

    $('#btn-reset-filters').on('click', function () {
        $('#filter-status, #filter-customer-type, #filter-company, #filter-portfolio, #filter-nationality, #filter-manager, #filter-contact-result, #filter-sms-status').val('').trigger('change');
        $('#filter-debt-min, #filter-debt-max, #filter-sms-date-from, #filter-sms-date-to').val('');
        table.search('');
        table.page(0);
        table.ajax.reload(null, false);
        CustomerSelection.clearAll();
    });

    // معالج تغيير الشركة والمحفظة
    $('#filter-company').on('change', function () {
        const selectedCompany = $(this).val();
        const portfolioSelect = $('#filter-portfolio');

        // إعادة تعيين المحفظة فوراً
        portfolioSelect.val('').trigger('change');

        // فلترة خيارات المحفظة
        portfolioSelect.find('option').each(function () {
            const portfolioCompany = $(this).data('company');
            if (!selectedCompany || portfolioCompany == selectedCompany || $(this).val() === '') {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        // إعادة تهيئة Select2
        portfolioSelect.select2();
    });

    // معالج إرسال البيانات للخادم
    table.on('preXhr.dt', function (e, settings, data) {
        data.customer_status_id = $('#filter-status').val();
        data.customer_type = $('#filter-customer-type').val();
        data.company_id = $('#filter-company').val();
        data.portfolio_id = $('#filter-portfolio').val();
        data.nationality = $('#filter-nationality').val();
        data.relationship_manager_id = $('#filter-manager').val();
        data.last_contact_result_id = $('#filter-contact-result').val();
        data.debt_min = $('#filter-debt-min').val();
        data.debt_max = $('#filter-debt-max').val();
        data.sms_status = $('#filter-sms-status').val();

        // ✅ إرسال تواريخ الإرسال فقط إذا كانت الحقول مفعّلة
        const smsDateFrom = $('#filter-sms-date-from');
        const smsDateTo = $('#filter-sms-date-to');

        if (!smsDateFrom.prop('disabled')) {
            data.sms_date_from = smsDateFrom.val();
        }

        if (!smsDateTo.prop('disabled')) {
            data.sms_date_to = smsDateTo.val();
        }

        data.view_type = window.viewType || 'all';
    });
}

/*
|--------------------------------------------------------------------------
| Table Events Binding
|--------------------------------------------------------------------------
| ربط أحداث الجدول
*/
function bindTableEvents() {
    const table = $('#customers-table').DataTable();

    // حدث إعادة رسم الجدول
    table.on('draw', function () {
        // تحديث سريع للتحديد وألوان الصفوف
        CustomerSelection.updateAfterDraw();
        CustomerRowColors.applyColors();
    });

    // أحداث التصفح
    table.on('page', function () {
        CustomerSelection.updateAfterDraw();
    });

    // حدث البحث
    table.on('search', function () {
        CustomerSelection.clearAll();
    });
}

/*
|--------------------------------------------------------------------------
| Action Handlers - Ultra Fast
|--------------------------------------------------------------------------
| معالجات الإجراءات فائقة السرعة
*/

/**
 * معالج إرسال الرسائل النصية - محسن
 */
function handleSendSMS() {
    const selectedIds = CustomerSelection.getSelectedIds();

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد عميل واحد على الأقل');
        return;
    }

    CustomerSMS.show(selectedIds);
}

/**
 * معالج التصدير - محسن
 */
function handleExport() {
    const selectedIds = CustomerSelection.getSelectedIds();

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد عميل واحد على الأقل');
        return;
    }

    showCustomersExportFieldsModal(selectedIds);
}

/**
 * معالج الحذف الجماعي - محسن
 */
function handleMassDelete() {
    const selectedIds = CustomerSelection.getSelectedIds();

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد عميل واحد على الأقل');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Customer');
}

/**
 * معالج التحديث الجماعي
 */
function handleBulkUpdateCustomers() {
    window.location.href = window.routes.customersBulkUpdate;
}

/**
 * معالج استيراد العملاء
 */
function handleImportCustomers() {
    window.location.href = window.routes.customersImport;
}

/*
|--------------------------------------------------------------------------
| Export Fields Catalog
|--------------------------------------------------------------------------
| حقل اسم العميل إلزامي دائمًا (locked) ولا يمكن إلغاء تحديده.
*/
const CUSTOMERS_EXPORT_FIELDS = [
    { key: 'name', label: 'اسم العميل', selected: true, locked: true },
    { key: 'customer_type', label: 'نوع العميل', selected: true },
    { key: 'identity_number', label: 'رقم الهوية', selected: true },
    { key: 'phone', label: 'رقم الهاتف', selected: true },
    { key: 'account_number', label: 'رقم الحساب', selected: true },
    { key: 'company_name', label: 'الشركة', selected: true },
    { key: 'portfolio_name', label: 'المحفظة', selected: true },
    { key: 'nationality', label: 'الجنسية', selected: false },
    { key: 'customer_status', label: 'حالة العميل', selected: true },
    { key: 'manager_name', label: 'مسؤول العلاقة', selected: true },
    { key: 'commission_rate', label: 'نسبة العمولة', selected: false },
    { key: 'contract_date', label: 'تاريخ العقد', selected: false },
    { key: 'default_date', label: 'تاريخ التعثر', selected: false },
    { key: 'debt_amount', label: 'إجمالي المديونية', selected: true },
    { key: 'total_credit', label: 'إجمالي المدفوع', selected: true },
    { key: 'remaining_debt', label: 'المتبقي', selected: true },
    { key: 'created_by', label: 'أنشئ بواسطة', selected: false },
    { key: 'created_at', label: 'تاريخ الإضافة', selected: false },
];

/*
|--------------------------------------------------------------------------
| Truncation Criteria Catalog
|--------------------------------------------------------------------------
| يجب أن يطابق الحد الأقصى EXPORT_ABSOLUTE_MAX ومعايير TRUNCATE_CRITERIA_LABELS
| في CustomerService.php الخاص بالباك-إند.
*/
const CUSTOMERS_EXPORT_ABSOLUTE_MAX = 25000;
const CUSTOMERS_EXPORT_TRUNCATE_CRITERIA = [
    { key: 'created_desc', label: 'الأحدث إنشاءً' },
    { key: 'created_asc', label: 'الأقدم إنشاءً' },
    { key: 'debt_desc', label: 'الأعلى مديونية' },
    { key: 'debt_asc', label: 'الأقل مديونية' },
    { key: 'paid_desc', label: 'الأعلى دفعًا' },
    { key: 'paid_asc', label: 'الأقل دفعًا' },
];

/*
|--------------------------------------------------------------------------
| Show Export Fields Modal
|--------------------------------------------------------------------------
| يعرض للمستخدم قائمة الحقول قبل التصدير. حقل اسم العميل مقفل ومحدد دائمًا.
| إذا تجاوز التحديد الحد الأقصى، يظهر أيضًا معيار اختيار من يُستبعد عند القص.
*/
function showCustomersExportFieldsModal(customerIds) {
    if ($('#customersExportFieldsModal').length === 0) {
        $('body').append(`
        <div class="modal fade" id="customersExportFieldsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="ti ti-table-export me-2 text-primary"></i>
                            اختر أعمدة التصدير
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="customersExportTruncateBox"></div>
                        <div class="text-center mb-3">
                            <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2 mx-1" id="customersExportSelectAll">تحديد الكل</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 mx-1" id="customersExportDeselectAll">إلغاء التحديد</button>
                        </div>
                        <div class="row g-2" id="customersExportFieldsGrid"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="button" class="btn btn-primary" id="customersExportFieldsConfirm">تصدير</button>
                    </div>
                </div>
            </div>
        </div>`);
    }

    // if (customerIds.length > CUSTOMERS_EXPORT_ABSOLUTE_MAX) {
    const options = CUSTOMERS_EXPORT_TRUNCATE_CRITERIA
        .map(c => `<option value="${c.key}">${c.label}</option>`)
        .join('');

    $('#customersExportTruncateBox').html(`
            <div class="alert alert-warning d-flex justify-between">
                <div class="">
                 اختر طريقة الترتيب
                </div>
                <select class="form-select form-select-sm" id="customersExportTruncateBy" >
                    ${options}
                </select>
            </div>
        `);


    const grid = CUSTOMERS_EXPORT_FIELDS.map(field => `
        <div class="col-md-4 col-sm-6">
            <div class="form-check">
                <input class="form-check-input customers-export-field" type="checkbox"
                       value="${field.key}" id="customersExportField_${field.key}"
                       ${field.selected ? 'checked' : ''} ${field.locked ? 'checked disabled' : ''}>
                <label class="form-check-label" for="customersExportField_${field.key}">
                    ${field.label}${field.locked ? ' <span class="text-muted">(إلزامي)</span>' : ''}
                </label>
            </div>
        </div>
    `).join('');

    $('#customersExportFieldsGrid').html(grid);

    $('#customersExportSelectAll').off('click').on('click', () => {
        $('.customers-export-field').prop('checked', true);
    });

    $('#customersExportDeselectAll').off('click').on('click', () => {
        $('.customers-export-field').not(':disabled').prop('checked', false);
    });

    $('#customersExportFieldsConfirm').off('click').on('click', () => {
        const selectedFields = $('.customers-export-field:checked')
            .map(function () { return $(this).val(); })
            .get();

        const truncateBy = $('#customersExportTruncateBy').val() || null;

        $('#customersExportFieldsModal').modal('hide');
        exportCustomersOptimized(customerIds, selectedFields, truncateBy);
    });

    $('#customersExportFieldsModal').modal('show');
}

/*
|--------------------------------------------------------------------------
| Optimized Export Function
|--------------------------------------------------------------------------
| ترسل المعرفات كنص JSON في حقل واحد بدلاً من مصفوفة حقول مخفية،
| لتفادي حد max_input_vars في PHP الذي كان يقصّ التحديد عند 1000 عميل فقط.
*/
function exportCustomersOptimized(customerIds, selectedFields = [], truncateBy = null) {
    if (customersExportPollInterval) {
        clearInterval(customersExportPollInterval);
        customersExportPollInterval = null;
        hideCustomersExportProgressModal();
    }

    toastr.info(`جاري تجهيز تصدير ${customerIds.length} عميل...`);

    $.ajax({
        url: window.routes.customersExport,
        type: 'POST',
        data: {
            _token: window.csrfToken,
            customer_ids: JSON.stringify(customerIds),
            selected_fields: selectedFields,
            truncate_by: truncateBy
        },
        success: (response) => {
            if (!response.success) {
                toastr.error(response.message || 'حدث خطأ أثناء تصدير البيانات');
                return;
            }

            if (response.truncated) {
                toastr.warning(response.message);
            }

            if (response.is_async) {
                showCustomersExportProgressModal();
                updateCustomersExportProgress(0, response.message);
                pollCustomersExportProgress(response.export_id);
            } else {
                if (!response.truncated) {
                    toastr.success(response.message);
                }
                downloadCustomersExportFile(response.file_name);
            }
        },
        error: (xhr) => {
            const message = xhr.responseJSON?.message || 'حدث خطأ أثناء تصدير البيانات';
            toastr.error(message);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Poll Export Progress
|--------------------------------------------------------------------------
| تتبع حالة التصدير الجاري في الخلفية حتى الانتهاء أو الفشل.
*/
function pollCustomersExportProgress(exportId) {
    customersExportPollInterval = setInterval(() => {
        $.ajax({
            url: window.routes.customersExportCheckProgress,
            type: 'POST',
            data: {
                _token: window.csrfToken,
                export_id: exportId
            },
            success: (response) => {
                if (!response.success || !response.data) {
                    return;
                }

                const data = response.data;

                if (data.status === 'completed') {
                    clearInterval(customersExportPollInterval);
                    customersExportPollInterval = null;
                    toastr.success(`تم تصدير ${data.total_records ?? ''} عميل بنجاح`);
                    showCustomersExportReadyModal(data.file_name, data.total_records);
                } else if (data.status === 'failed') {
                    clearInterval(customersExportPollInterval);
                    customersExportPollInterval = null;
                    hideCustomersExportProgressModal();
                    toastr.error(data.error || 'فشل في تصدير البيانات');
                } else if (data.status === 'processing') {
                    updateCustomersExportProgress(data.percentage || 0, data.message || 'جاري المعالجة...');
                }
            },
            error: () => {
                clearInterval(customersExportPollInterval);
                customersExportPollInterval = null;
                hideCustomersExportProgressModal();
                toastr.error('حدث خطأ أثناء تتبع حالة التصدير');
            }
        });
    }, 3000);
}

/*
|--------------------------------------------------------------------------
| Download Export File
|--------------------------------------------------------------------------
*/
function downloadCustomersExportFile(fileName) {
    if (!fileName || !window.routes.customersExportDownload) {
        return;
    }

    const url = window.routes.customersExportDownload.replace('FILE_PLACEHOLDER', encodeURIComponent(fileName));
    // فتح في تبويب جديد بدل التنقل بنفس الصفحة، لأن التنقل المباشر يُطلق
    // beforeunload فيمسح حالة تحديد العملاء (CustomerSelection.cleanup) بشكل دائم.
    window.open(url, '_blank');
}

/*
|--------------------------------------------------------------------------
| Export Progress Modal
|--------------------------------------------------------------------------
| يظهر فقط لمسار التصدير الكبير (job في الخلفية) حتى لا يُثقل التصدير السريع.
*/
const CUSTOMERS_EXPORT_PROGRESS_TITLE_HTML = '<i class="ti ti-file-export me-2 text-primary"></i>جاري تصدير العملاء';
const CUSTOMERS_EXPORT_PROGRESS_BODY_HTML = `
    <div class="progress mb-3 position-relative" style="height: 25px; border-radius: 12px; overflow: hidden;">
        <div id="customersExportProgressBar"
             class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
             role="progressbar"
             style="width: 0%; transition: width 0.6s ease;"
             aria-valuenow="0"
             aria-valuemin="0"
             aria-valuemax="100">
        </div>
        <div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center">
            <span id="customersExportProgressText" class="text-white fw-bold" style="font-size: 12px;">0%</span>
        </div>
    </div>
    <div id="customersExportProgressMessage" class="text-muted mb-2" style="font-size: 14px;">
        بدء عملية التصدير...
    </div>
    <div class="d-flex justify-content-center align-items-center mt-3">
        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
        <small class="text-primary fw-medium">الرجاء الانتظار حتى تحضير الملف</small>
    </div>
`;

function showCustomersExportProgressModal() {
    if ($('#customersExportProgressModal').length === 0) {
        $('body').append(`
        <div class="modal fade" id="customersExportProgressModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
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

    // إعادة ضبط المحتوى في كل مرة، لأن اكتمال التصدير يستبدله بزر التحميل
    $('#customersExportProgressModal .modal-title').html(CUSTOMERS_EXPORT_PROGRESS_TITLE_HTML);
    $('#customersExportProgressModal .modal-body').html(CUSTOMERS_EXPORT_PROGRESS_BODY_HTML);

    $('#customersExportProgressModal').modal('show');
}

function updateCustomersExportProgress(percentage, message) {
    const safePercentage = Math.min(Math.max(percentage, 0), 100);
    const roundedPercentage = Math.round(safePercentage);

    $('#customersExportProgressBar')
        .css('width', safePercentage + '%')
        .attr('aria-valuenow', safePercentage);

    $('#customersExportProgressText').text(roundedPercentage + '%');

    if (message) {
        $('#customersExportProgressMessage').text(message);
    }
}

/*
|--------------------------------------------------------------------------
| Show Export Ready Modal
|--------------------------------------------------------------------------
| لا نفتح التنزيل تلقائيًا من داخل setInterval لأن المتصفحات تحجب النوافذ
| المنبثقة غير الناتجة مباشرة عن تفاعل المستخدم؛ لذلك نعرض زر تحميل حقيقي.
*/
function showCustomersExportReadyModal(fileName, totalRecords) {
    if (!fileName || !window.routes.customersExportDownload) {
        hideCustomersExportProgressModal();
        return;
    }

    const url = window.routes.customersExportDownload.replace('FILE_PLACEHOLDER', encodeURIComponent(fileName));

    $('#customersExportProgressModal .modal-title').html('<i class="ti ti-circle-check me-2 text-success"></i>اكتمل التصدير');
    $('#customersExportProgressModal .modal-body').html(`
        <div class="text-center">
            <i class="ti ti-file-spreadsheet text-success mb-3" style="font-size: 48px;"></i>
            <p class="mb-3">تم تصدير ${totalRecords ?? ''} عميل بنجاح</p>
            <a href="${url}" target="_blank" class="btn btn-success" id="customersExportDownloadBtn">
                <i class="ti ti-download me-1"></i> تحميل الملف
            </a>
        </div>
    `);

    // لا نستخدم data-bs-dismiss هنا: مستمع Bootstrap العام يستدعي
    // event.preventDefault() على أي عنصر بهذه الخاصية، فيمنع فتح رابط href
    // الحقيقي. لذلك نُغلق المودال يدويًا بعد نقرة التحميل.
    $('#customersExportDownloadBtn').off('click').on('click', () => {
        setTimeout(() => hideCustomersExportProgressModal(), 200);
    });
}

function hideCustomersExportProgressModal() {
    $('#customersExportProgressModal').modal('hide');
}

/*
|--------------------------------------------------------------------------
| Global Function Exports
|--------------------------------------------------------------------------
| تصدير الدوال للاستخدام العام
*/
window.handleSendSMS = handleSendSMS;
window.handleExport = handleExport;
window.handleMassDelete = handleMassDelete;
window.handleBulkUpdateCustomers = handleBulkUpdateCustomers;
window.handleImportCustomers = handleImportCustomers;

/*
|--------------------------------------------------------------------------
| Cleanup on Page Unload
|--------------------------------------------------------------------------
| تنظيف عند مغادرة الصفحة
*/
$(window).on('beforeunload', function () {
    if (typeof CustomerSelection !== 'undefined') {
        CustomerSelection.cleanup();
    }

    if (typeof CustomerRowColors !== 'undefined') {
        CustomerRowColors.cleanup();
    }
});
