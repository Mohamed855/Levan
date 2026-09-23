'use strict';

$(function () {
    initializeComponents();
    initializeDataTableEvents();
});

/*
|--------------------------------------------------------------------------
| تهيئة المكونات الأساسية
|--------------------------------------------------------------------------
*/
function initializeComponents() {
    $('.select2').select2({
        allowClear: true,
        placeholder: 'الكل',
        dir: 'rtl',
        width: '100%',
        language: 'ar'
    });
}

/*
|--------------------------------------------------------------------------
| تهيئة أحداث جدول البيانات
|--------------------------------------------------------------------------
*/
function initializeDataTableEvents() {
    $('#archived-customers-table').on('init.dt', function () {
        const table = $(this).DataTable();
        const tableWrapper = $('#archived-customers-table_wrapper');

        bindFilters(table);
        bindSelectionEvents(table, tableWrapper);
        bindTableEvents(table);

        // تهيئة نظام التحديد الجديد
        CustomerArchiveSelection.initialize();
    });
}

/*
|--------------------------------------------------------------------------
| ربط أحداث الفلاتر
|--------------------------------------------------------------------------
*/
function bindFilters(table) {
    $('#filter-customer-type, #filter-company, #filter-portfolio, #filter-manager, #filter-contact-result').on('change', function () {
        applyFilters(table);
    });

    $('#filter-search').on('keyup', debounce(function () {
        applyFilters(table);
    }, 500));

    $('#filter-company').on('change', function () {
        const selectedCompany = $(this).val();
        const portfolioSelect = $('#filter-portfolio');

        portfolioSelect.val('').trigger('change.select2');
        portfolioSelect.find('option').each(function () {
            const portfolioCompany = $(this).data('company');
            $(this).prop('disabled', !!(selectedCompany && portfolioCompany != selectedCompany && $(this).val() !== ''));
        });
        portfolioSelect.select2();
    });
}

/*
|--------------------------------------------------------------------------
| تطبيق الفلاتر على الجدول
|--------------------------------------------------------------------------
*/
function applyFilters(table) {
    const filters = {
        customerType: $('#filter-customer-type').val() || '',
        companyId: $('#filter-company').val() || '',
        portfolioId: $('#filter-portfolio').val() || '',
        managerId: $('#filter-manager').val() || '',
        contactResultId: $('#filter-contact-result').val() || '',
        search: $('#filter-search').val() || ''
    };

    const baseUrl = window.location.pathname;
    const params = new URLSearchParams();

    if (filters.customerType) params.append('customer_type', filters.customerType);
    if (filters.companyId) params.append('company_id', filters.companyId);
    if (filters.portfolioId) params.append('portfolio_id', filters.portfolioId);
    if (filters.managerId) params.append('relationship_manager_id', filters.managerId);
    if (filters.contactResultId) params.append('last_contact_result_id', filters.contactResultId);
    if (filters.search) params.append('search', filters.search);

    const query = params.toString();
    const newUrl = query ? baseUrl + '?' + query : baseUrl;

    table.ajax.url(newUrl).load();
}

/*
|--------------------------------------------------------------------------
| ربط أحداث التحديد والعلامات
|--------------------------------------------------------------------------
*/
function bindSelectionEvents(table, tableWrapper) {
    // يمكن إضافة أحداث إضافية هنا إذا لزم الأمر
}

/*
|--------------------------------------------------------------------------
| ربط أحداث الجدول العامة
|--------------------------------------------------------------------------
*/
function bindTableEvents(table) {
    table.on('draw', function () {
        // تحديث نظام التحديد بعد إعادة رسم الجدول
        CustomerArchiveSelection.updateAfterDraw();
    });
}

/*
|--------------------------------------------------------------------------
| تأكيد استرجاع عميل واحد
|--------------------------------------------------------------------------
*/
function confirmRestore(customerId) {
    if (!customerId || !validateCustomerId(customerId)) {
        showError('معرف العميل غير صحيح');
        return;
    }

    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'سيتم استرجاع هذا العميل من الأرشيف',
        icon: 'question',
        showCancelButton: true,
        showConfirmButton: true,
        showDenyButton: false,
        buttonsStyling: false,
        customClass: {
            popup: 'custom-popup',
            title: 'custom-title',
            text: 'custom-text',
            confirmButton: 'btn btn-success custom-confirm',
            cancelButton: 'btn btn-danger custom-cancel'
        },
        confirmButtonText: 'نعم، استرجع',
        cancelButtonText: 'إلغاء',
    }).then((result) => {
        if (result.isConfirmed) {
            executeRestore(customerId);
        }
    });
}

/*
|--------------------------------------------------------------------------
| تنفيذ استرجاع عميل واحد
|--------------------------------------------------------------------------
*/
function executeRestore(customerId) {
    const form = createFormForAction('PATCH', window.routes.restore.replace(':id', customerId));
    document.body.appendChild(form);
    form.submit();
}

/*
|--------------------------------------------------------------------------
| تأكيد حذف عميل واحد نهائياً
|--------------------------------------------------------------------------
*/
function confirmForceDelete(customerId) {
    if (!customerId || !validateCustomerId(customerId)) {
        showError('معرف العميل غير صحيح');
        return;
    }

    Swal.fire({
        title: 'تحذير!',
        text: 'سيتم حذف هذا العميل نهائياً ولا يمكن استرجاعه',
        icon: 'warning',
        showCancelButton: true,
        showConfirmButton: true,
        showDenyButton: false,
        buttonsStyling: false,
        customClass: {
            popup: 'custom-popup',
            title: 'custom-title',
            text: 'custom-text',
            confirmButton: 'btn btn-success custom-confirm',
            cancelButton: 'btn btn-danger custom-cancel'
        },
        confirmButtonText: 'نعم، احذف نهائياً',
        cancelButtonText: 'إلغاء',
    }).then((result) => {
        if (result.isConfirmed) {
            executeForceDelete(customerId);
        }
    });
}

/*
|--------------------------------------------------------------------------
| تنفيذ حذف عميل واحد نهائياً
|--------------------------------------------------------------------------
*/
function executeForceDelete(customerId) {
    const form = createFormForAction('DELETE', window.routes.forceDelete.replace(':id', customerId));
    document.body.appendChild(form);
    form.submit();
}

/*
|--------------------------------------------------------------------------
| معالجة الاسترجاع الجماعي
|--------------------------------------------------------------------------
*/
function handleMassRestore() {
    const selectedIds = CustomerArchiveSelection.getSelectedIds();

    if (!validateSelectedIds(selectedIds)) {
        return;
    }

    Swal.fire({
        title: 'استعادة جماعية',
        text: `سيتم استرجاع ${selectedIds.length} عميل من الأرشيف`,
        icon: 'question',
        showCancelButton: true,
        showConfirmButton: true,
        showDenyButton: false,
        buttonsStyling: false,
        customClass: {
            popup: 'custom-popup',
            title: 'custom-title',
            text: 'custom-text',
            confirmButton: 'btn btn-success custom-confirm',
            cancelButton: 'btn btn-danger custom-cancel'
        },
        confirmButtonText: 'استرجع الكل',
        cancelButtonText: 'إلغاء',
    }).then((result) => {
        if (result.isConfirmed) {
            executeMassRestore(selectedIds);
        }
    });
}

/*
|--------------------------------------------------------------------------
| تنفيذ الاسترجاع الجماعي
|--------------------------------------------------------------------------
*/
function executeMassRestore(selectedIds) {
    showLoadingSpinner('جاري الاستعادة...');

    $.ajax({
        url: window.routes.massRestore,
        type: 'POST',
        data: {
            _token: window.csrfToken,
            ids: selectedIds
        },
        success: function (response) {
            hideLoadingSpinner();
            handleMassRestoreSuccess(response);
        },
        error: function (xhr) {
            hideLoadingSpinner();
            handleMassRestoreError(xhr);
        }
    });
}

/*
|--------------------------------------------------------------------------
| معالجة الحذف النهائي الجماعي - ✅ FIXED
|--------------------------------------------------------------------------
*/
function handleMassForceDelete() {
    // ✅ التصحيح: استخدام الدالة الصحيحة من CustomerArchiveSelection
    const selectedIds = CustomerArchiveSelection.getSelectedIds();

    if (!validateSelectedIds(selectedIds)) {
        return;
    }

    Swal.fire({
        title: 'تحذير خطير!',
        text: `سيتم حذف ${selectedIds.length} عميل نهائياً ولا يمكن استرجاعهم`,
        icon: 'warning',
        showCancelButton: true,
        showConfirmButton: true,
        showDenyButton: false,
        buttonsStyling: false,
        customClass: {
            popup: 'custom-popup',
            title: 'custom-title',
            text: 'custom-text',
            confirmButton: 'btn btn-success custom-confirm',
            cancelButton: 'btn btn-danger custom-cancel'
        },
        confirmButtonText: 'نعم، احذف الكل نهائياً',
        cancelButtonText: 'إلغاء',
    }).then((result) => {
        if (result.isConfirmed) {
            confirmFinalDeletion(selectedIds);
        }
    });
}

/*
|--------------------------------------------------------------------------
| تأكيد الحذف النهائي النهائي
|--------------------------------------------------------------------------
*/
function confirmFinalDeletion(selectedIds) {
    Swal.fire({
        title: 'تأكيد أخير',
        text: 'هل أنت متأكد تماماً من هذا الإجراء؟',
        icon: 'warning',
        showCancelButton: true,
        showConfirmButton: true,
        showDenyButton: false,
        buttonsStyling: false,
        customClass: {
            popup: 'custom-popup',
            title: 'custom-title',
            text: 'custom-text',
            confirmButton: 'btn btn-success custom-confirm',
            cancelButton: 'btn btn-danger custom-cancel'
        },
        confirmButtonText: 'نعم، احذف نهائياً',
        cancelButtonText: 'إلغاء',
    }).then((result) => {
        if (result.isConfirmed) {
            executeMassForceDelete(selectedIds);
        }
    });
}

/*
|--------------------------------------------------------------------------
| تنفيذ الحذف النهائي الجماعي
|--------------------------------------------------------------------------
*/
function executeMassForceDelete(selectedIds) {
    showLoadingSpinner('جاري الحذف النهائي...');

    $.ajax({
        url: window.routes.massForceDelete,
        type: 'DELETE',
        data: {
            _token: window.csrfToken,
            ids: selectedIds
        },
        success: function (response) {
            hideLoadingSpinner();
            handleMassForceDeleteSuccess(response);
        },
        error: function (xhr) {
            hideLoadingSpinner();
            handleMassForceDeleteError(xhr);
        }
    });
}

/*
|--------------------------------------------------------------------------
| الدوال المساعدة - Helper Functions
|--------------------------------------------------------------------------
*/

/*
| التحقق من صحة المعرفات المحددة
*/
function validateSelectedIds(selectedIds) {
    if (selectedIds.length < 1) {
        showWarning('يرجى تحديد عميل واحد على الأقل');
        return false;
    }
    return true;
}

/*
| التحقق من صحة معرف العميل
*/
function validateCustomerId(customerId) {
    return customerId && !isNaN(customerId) && parseInt(customerId) > 0;
}

/*
| إنشاء نموذج للإجراء
*/
function createFormForAction(method, actionUrl) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = actionUrl;

    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = window.csrfToken;
    form.appendChild(csrfToken);

    if (method !== 'POST') {
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = method;
        form.appendChild(methodField);
    }

    return form;
}

/*
| تحديث الجدول وإعادة تعيين التحديد
*/
function refreshTableAndResetSelection() {
    CustomerArchiveSelection.clearAll();

    if ($.fn.DataTable.isDataTable('#archived-customers-table')) {
        $('#archived-customers-table').DataTable().draw(false);
    }
}

/*
| معالجة نجاح الاسترجاع الجماعي
*/
function handleMassRestoreSuccess(response) {
    if (response.success) {
        showSuccess(response.message);
        refreshTableAndResetSelection();
    } else {
        showError(response.message || 'حدث خطأ غير متوقع');
    }
}

/*
| معالجة خطأ الاسترجاع الجماعي
*/
function handleMassRestoreError(xhr) {
    const message = xhr.responseJSON?.message || 'حدث خطأ أثناء الاستعادة الجماعية';
    showError(message);
}

/*
| معالجة نجاح الحذف النهائي الجماعي
*/
function handleMassForceDeleteSuccess(response) {
    if (response.success) {
        showSuccess(response.message);
        refreshTableAndResetSelection();
    } else {
        showError(response.message || 'حدث خطأ غير متوقع');
    }
}

/*
| معالجة خطأ الحذف النهائي الجماعي
*/
function handleMassForceDeleteError(xhr) {
    const message = xhr.responseJSON?.message || 'حدث خطأ أثناء الحذف النهائي الجماعي';
    showError(message);
}

/*
| تأخير تنفيذ الدالة (Debounce)
*/
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/*
|--------------------------------------------------------------------------
| دوال عرض الرسائل - Toast Messages
|--------------------------------------------------------------------------
*/
function showSuccess(message) {
    if (typeof toastr !== 'undefined') {
        toastr.success(message);
    }
}

function showError(message) {
    if (typeof toastr !== 'undefined') {
        toastr.error(message);
    }
}

function showWarning(message) {
    if (typeof toastr !== 'undefined') {
        toastr.warning(message);
    }
}

/*
|--------------------------------------------------------------------------
| دوال شاشة التحميل - Loading Spinner
|--------------------------------------------------------------------------
*/
function showLoadingSpinner(message = 'جاري المعالجة...') {
    Swal.fire({
        title: message,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        buttonsStyling: false,
        customClass: {
            popup: 'custom-popup',
            title: 'custom-title'
        },
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

function hideLoadingSpinner() {
    Swal.close();
}

/*
|--------------------------------------------------------------------------
| تصدير الدوال للنطاق العام - Global Exports
|--------------------------------------------------------------------------
*/
window.confirmRestore = confirmRestore;
window.confirmForceDelete = confirmForceDelete;
window.handleMassRestore = handleMassRestore;
window.handleMassForceDelete = handleMassForceDelete;