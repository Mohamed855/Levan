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
    $('#archived-contracts-table').on('init.dt', function () {
        const table = $(this).DataTable();
        const tableWrapper = $('#archived-contracts-table_wrapper');

        bindFilters(table);
        bindSelectionEvents(table, tableWrapper);
        bindTableEvents(table);
    });
}

/*
|--------------------------------------------------------------------------
| ربط أحداث الفلاتر
|--------------------------------------------------------------------------
*/
function bindFilters(table) {
    $('#filter-status, #filter-company, #filter-commission-type, #filter-auto-renewal').on('change', function () {
        applyFilters(table);
    });

    $('#filter-search').on('keyup', debounce(function () {
        applyFilters(table);
    }, 500));
}

/*
|--------------------------------------------------------------------------
| تطبيق الفلاتر على الجدول
|--------------------------------------------------------------------------
*/
function applyFilters(table) {
    const filters = {
        status: $('#filter-status').val() || '',
        companyId: $('#filter-company').val() || '',
        commissionType: $('#filter-commission-type').val() || '',
        autoRenewal: $('#filter-auto-renewal').val() || '',
        search: $('#filter-search').val() || ''
    };

    const baseUrl = window.location.pathname;
    const params = new URLSearchParams();

    if (filters.status) params.append('status', filters.status);
    if (filters.companyId) params.append('company_id', filters.companyId);
    if (filters.commissionType) params.append('commission_type', filters.commissionType);
    if (filters.autoRenewal) params.append('auto_renewal', filters.autoRenewal);
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
    tableWrapper.on('click', '#select-all', function () {
        const isChecked = this.checked;
        const rows = table.rows({ search: 'applied' }).nodes();
        $('input.row-checkbox', rows).prop('checked', isChecked);
    });

    tableWrapper.on('change', '.row-checkbox', function () {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        }
    });
}

/*
|--------------------------------------------------------------------------
| ربط أحداث الجدول العامة
|--------------------------------------------------------------------------
*/
function bindTableEvents(table) {
    table.on('draw', function () {
        $('#select-all').prop('checked', false);
    });
}

/*
|--------------------------------------------------------------------------
| تأكيد استرجاع عقد واحد
|--------------------------------------------------------------------------
*/
function confirmRestore(contractId) {
    if (!contractId || !validateContractId(contractId)) {
        showError('معرف العقد غير صحيح');
        return;
    }

    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'سيتم استرجاع هذا العقد من الأرشيف',
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
            executeRestore(contractId);
        }
    });
}

/*
|--------------------------------------------------------------------------
| تنفيذ استرجاع عقد واحد
|--------------------------------------------------------------------------
*/
function executeRestore(contractId) {
    const form = createFormForAction('PATCH', window.routes.restore.replace(':id', contractId));
    document.body.appendChild(form);
    form.submit();
}

/*
|--------------------------------------------------------------------------
| تأكيد حذف عقد واحد نهائياً
|--------------------------------------------------------------------------
*/
function confirmForceDelete(contractId) {
    if (!contractId || !validateContractId(contractId)) {
        showError('معرف العقد غير صحيح');
        return;
    }

    Swal.fire({
        title: 'تحذير!',
        text: 'سيتم حذف هذا العقد نهائياً ولا يمكن استرجاعه',
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
            executeForceDelete(contractId);
        }
    });
}

/*
|--------------------------------------------------------------------------
| تنفيذ حذف عقد واحد نهائياً
|--------------------------------------------------------------------------
*/
function executeForceDelete(contractId) {
    const form = createFormForAction('DELETE', window.routes.forceDelete.replace(':id', contractId));
    document.body.appendChild(form);
    form.submit();
}

/*
|--------------------------------------------------------------------------
| معالجة الاسترجاع الجماعي
|--------------------------------------------------------------------------
*/
function handleMassRestore() {
    const selectedIds = getSelectedContractIds();

    if (!validateSelectedIds(selectedIds)) {
        return;
    }

    Swal.fire({
        title: 'استعادة جماعية',
        text: `سيتم استرجاع ${selectedIds.length} عقد من الأرشيف`,
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
| معالجة الحذف النهائي الجماعي
|--------------------------------------------------------------------------
*/
function handleMassForceDelete() {
    const selectedIds = getSelectedContractIds();

    if (!validateSelectedIds(selectedIds)) {
        return;
    }

    Swal.fire({
        title: 'تحذير خطير!',
        text: `سيتم حذف ${selectedIds.length} عقد نهائياً ولا يمكن استرجاعهم`,
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
| الدوال المساعدة
|--------------------------------------------------------------------------
*/
function getSelectedContractIds() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });
    return selectedIds;
}

function validateSelectedIds(selectedIds) {
    if (selectedIds.length < 1) {
        showWarning('يرجى تحديد عقد واحد على الأقل');
        return false;
    }
    return true;
}

function validateContractId(contractId) {
    return contractId && !isNaN(contractId) && parseInt(contractId) > 0;
}

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

function refreshTableAndResetSelection() {
    if ($.fn.DataTable.isDataTable('#archived-contracts-table')) {
        $('#archived-contracts-table').DataTable().draw(false);
        $('#select-all').prop('checked', false);
    }
}

function handleMassRestoreSuccess(response) {
    if (response.success) {
        showSuccess(response.message);
        refreshTableAndResetSelection();
    } else {
        showError(response.message || 'حدث خطأ غير متوقع');
    }
}

function handleMassRestoreError(xhr) {
    const message = xhr.responseJSON?.message || 'حدث خطأ أثناء الاستعادة الجماعية';
    showError(message);
}

function handleMassForceDeleteSuccess(response) {
    if (response.success) {
        showSuccess(response.message);
        refreshTableAndResetSelection();
    } else {
        showError(response.message || 'حدث خطأ غير متوقع');
    }
}

function handleMassForceDeleteError(xhr) {
    const message = xhr.responseJSON?.message || 'حدث خطأ أثناء الحذف النهائي الجماعي';
    showError(message);
}

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

window.confirmRestore = confirmRestore;
window.confirmForceDelete = confirmForceDelete;
window.handleMassRestore = handleMassRestore;
window.handleMassForceDelete = handleMassForceDelete;