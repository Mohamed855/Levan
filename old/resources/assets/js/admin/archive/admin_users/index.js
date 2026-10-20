'use strict';

$(function () {
    initializeComponents();
    initializeDataTableEvents();
});

function initializeComponents() {
    $('.select2').select2({
        allowClear: true,
        placeholder: 'الكل',
        dir: 'rtl',
        width: '100%',
        language: 'ar'
    });
}

function initializeDataTableEvents() {
    $('#admin-user-archive-table').on('init.dt', function () {
        const table = $(this).DataTable();
        const tableWrapper = $('#admin-user-archive-table_wrapper');

        bindSelectionEvents(table, tableWrapper);
        bindTableEvents(table);
    });
}

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

function bindTableEvents(table) {
    table.on('draw', function () {
        $('#select-all').prop('checked', false);
    });
}
document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-restore-btn');
         if (!btn) return;
         const id = btn.dataset.id;
        if (!id) return;
        console.log(id)
        confirmRestore(id);
    });

  document.addEventListener('click', function (e) {
        const btn = e.target.closest('.js-force-btn');
        console.log(btn);
        if (!btn) return;
         const id = btn.dataset.id;
        if (!id) return;

        confirmForceDelete(id);
    });
    
function confirmRestore(id) {
    if (!id || !validateCompanyId(id)) {
        showError('معرف المستخدم غير صحيح');
        return;
    }

    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'سيتم استرجاع هذا المستخدم من الأرشيف',
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
            executeRestore(id);
        }
    });
}

function executeRestore(id) {
    const form = createFormForAction('PATCH', window.routes.restore.replace(':id', id));
    document.body.appendChild(form);
    form.submit();
}

function confirmForceDelete(userId) {
    if (!userId || !validateCompanyId(userId)) {
        showError('معرف المستخدم غير صحيح');
        return;
    }

    Swal.fire({
        title: 'تحذير!',
        text: 'سيتم حذف هذا المستخدم نهائياً ولا يمكن استرجاعها',
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
            executeForceDelete(userId);
        }
    });
}

function executeForceDelete(userId) {
    const form = createFormForAction('DELETE', window.routes.forceDelete.replace(':id', userId));
    document.body.appendChild(form);
    form.submit();
}

function handleMassRestore() {
    const selectedIds = getSelectedCompanyIds();

    if (!validateSelectedIds(selectedIds)) {
        return;
    }

    Swal.fire({
        title: 'استعادة جماعية',
        text: `سيتم استرجاع ${selectedIds.length} شركة من الأرشيف`,
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

function handleMassForceDelete() {
    const selectedIds = getSelectedCompanyIds();

    if (!validateSelectedIds(selectedIds)) {
        return;
    }

    Swal.fire({
        title: 'تحذير خطير!',
        text: `سيتم حذف ${selectedIds.length} شركة نهائياً ولا يمكن استرجاعها`,
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

function getSelectedCompanyIds() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });
    return selectedIds;
}

function validateSelectedIds(selectedIds) {
    if (selectedIds.length < 1) {
        showWarning('يرجى تحديد شركة واحدة على الأقل');
        return false;
    }
    return true;
}

function validateCompanyId(companyId) {
    return companyId && !isNaN(companyId) && parseInt(companyId) > 0;
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
    if ($.fn.DataTable.isDataTable('#admin-user-archive-table')) {
        $('#admin-user-archive-table').DataTable().draw(false);
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
            title: 'custom-title',
            text: 'custom-text'
        },
        willOpen: () => {
            Swal.showLoading();
        }
    });
}

function hideLoadingSpinner() {
    Swal.close();
}



document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-toggle-btn');
    if (!btn) return;

    const userId = btn.dataset.id;
    const url = btn.dataset.url;

    if (!userId || !url) {
        toastr.error('بيانات المستخدم غير مكتملة');
        return;
    }

    toggleStatus(btn, userId, url);
});

function toggleStatus(button, userId, url) {

    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.content
        || window.csrfToken;

    if (!csrfToken) {
        toastr.error('رمز الأمان مفقود');
        return;
    }

    const originalHtml = button.innerHTML;
    button.classList.add('disabled');
    button.innerHTML = '<i class="ti ti-loader ti-spin"></i>';

    console.log('Toggling status for user ID:', userId);

    $.ajax({
        url: url,
        type: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        success: function (response) {
            if (response.success) {
                $('#admin-user-archive-table')
                    .DataTable()
                    .ajax.reload(null, false);

                toastr.success(response.message || 'تم تغيير الحالة بنجاح');
            } else {
                toastr.error(response.message || 'حدث خطأ ما');
            }
        },
        error: function (xhr) {
            let errorMessage = 'حدث خطأ أثناء الاتصال بالخادم';

            if (xhr.status === 403) {
                errorMessage = 'ليس لديك صلاحية لتنفيذ هذا الإجراء';
            } else if (xhr.status === 404) {
                errorMessage = 'المستخدم غير موجود';
            } else if (xhr.status === 419) {
                errorMessage = 'انتهت صلاحية الجلسة، يرجى تحديث الصفحة';
            } else if (xhr.status === 422) {
                errorMessage = 'بيانات غير صالحة';
            } else if (xhr.responseJSON?.message) {
                errorMessage = xhr.responseJSON.message;
            }

            toastr.error(errorMessage);
        },
        complete: function () {
            button.classList.remove('disabled');
            button.innerHTML = originalHtml;
        }
    });
}


// تصدير الدوال للاستخدام العام
window.toggleStatus = toggleStatus;
window.confirmRestore = confirmRestore;
window.confirmForceDelete = confirmForceDelete;
window.handleMassRestore = handleMassRestore;
window.handleMassForceDelete = handleMassForceDelete;