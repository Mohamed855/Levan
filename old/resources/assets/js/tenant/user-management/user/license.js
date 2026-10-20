// ✅ CSRF عبر الـ header فقط — لا حاجة لإرساله في data أيضاً
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});


/*
|============================================================================
|  Entry Point
|============================================================================
*/

// ✅ async/await بدلاً من .then() المتداخل
window.toggleLicense = async function (element, userId) {
    const isChecked = element.checked;
    const originalState = !isChecked;

    element.disabled = true;

    const confirmed = await showConfirmationDialog(isChecked);

    if (!confirmed) {
        revertSwitch(element, originalState);
        return;
    }

    await performLicenseAction(element, userId, isChecked, originalState);
};


/*
|============================================================================
|  Dialogs
|============================================================================
*/

async function showConfirmationDialog(isChecked) {
    const actionText = isChecked ? 'تفعيل' : 'إلغاء';

    const result = await Swal.fire({
        title: `تأكيد ${actionText} الترخيص`,
        text: `هل أنت متأكد من ${actionText} الترخيص لهذا المستخدم؟`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'تأكيد',
        cancelButtonText: 'إلغاء',
        reverseButtons: false,
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn btn-success me-2',
            cancelButton: 'btn btn-danger',
            popup: 'custom-popup',
            title: 'custom-title',
            text: 'custom-text'
        }
    });

    return result.isConfirmed;
}


function showLoadingDialog(isChecked) {
    Swal.fire({
        title: isChecked ? 'جاري تفعيل الترخيص...' : 'جاري إلغاء الترخيص...',
        text: 'الرجاء الانتظار...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading(),
        customClass: {
            popup: 'custom-popup',
            title: 'custom-title',
            text: 'custom-text'
        }
    });
}


function showSuccessMessage(isChecked, message) {
    Swal.fire({
        icon: 'success',
        title: isChecked ? 'تم التفعيل بنجاح!' : 'تم الإلغاء بنجاح!',
        text: message || '',
        timer: 2500,
        showConfirmButton: false,
        customClass: {
            popup: 'custom-popup',
            title: 'custom-title',
            text: 'custom-text'
        }
    });
}


function showErrorDialog(message, icon, title, showUpgradeButton) {
    Swal.fire({
        icon,
        title,
        text: message,
        showCancelButton: showUpgradeButton,
        showConfirmButton: true,
        confirmButtonText: showUpgradeButton ? 'شراء تراخيص جديدة' : 'حسناً',
        cancelButtonText: 'إلغاء',
        buttonsStyling: false,
        customClass: {
            confirmButton: showUpgradeButton ? 'btn btn-primary' : 'btn btn-danger',
            cancelButton: 'btn btn-secondary ms-2',
            popup: 'custom-popup',
            title: 'custom-title',
            text: 'custom-text'
        }
    }).then((result) => {
        if (result.isConfirmed && showUpgradeButton) {
            redirectToSubscriptions();
        }
    });
}


/*
|============================================================================
|  AJAX
|============================================================================
*/

function performLicenseAction(element, userId, isChecked, originalState) {
    const url = isChecked ? window.licenseRoutes.assign : window.licenseRoutes.release;

    showLoadingDialog(isChecked);

    return $.ajax({
        url,
        type: 'POST',
        dataType: 'json',
        // ✅ لا _token هنا — $.ajaxSetup يرسله في الـ header تلقائياً
        data: { user_id: userId },
        timeout: 30000,

        success(response) {
            handleSuccess(response, isChecked, element, originalState);
        },

        // ✅ إزالة actionText — لم يكن يُستخدم داخل handleError أبداً
        error(xhr, status) {
            handleError(xhr, status, element, originalState);
        },

        complete() {
            element.disabled = false;
        }
    });
}


/*
|============================================================================
|  Response Handlers
|============================================================================
*/

function handleSuccess(response, isChecked, element, originalState) {
    Swal.close();

    if (response.success) {
        showSuccessMessage(isChecked, response.message);
        reloadUsersTable();
        return;
    }

    // success: false من الخادم — نُعيد الـ switch لحالته الأصلية
    element.checked = originalState;
    showErrorDialog(response.message || 'حدث خطأ غير متوقع', 'warning', 'فشلت العملية', false);
}


function handleError(xhr, status, element, originalState) {
    Swal.close();
    element.checked = originalState;

    if (status === 'timeout') {
        showErrorDialog('انتهت مهلة الاتصال. الرجاء المحاولة مرة أخرى.', 'warning', 'انتهت المهلة', false);
        return;
    }

    const errorMsg = extractErrorMessage(xhr);
    const errorConfig = getErrorConfiguration(xhr.status, errorMsg);

    showErrorDialog(errorMsg, errorConfig.icon, errorConfig.title, errorConfig.showUpgradeButton);
}


/*
|============================================================================
|  Error Helpers
|============================================================================
*/

function extractErrorMessage(xhr) {
    if (xhr.responseJSON) {
        return xhr.responseJSON.message || xhr.responseJSON.error || 'حدث خطأ غير متوقع';
    }

    if (xhr.responseText) {
        try {
            return JSON.parse(xhr.responseText).message || 'حدث خطأ غير متوقع';
        } catch {
            return xhr.responseText.substring(0, 200);
        }
    }

    return 'فشل الاتصال بالخادم';
}


function getErrorConfiguration(statusCode, errorMsg) {
    const config = {
        icon: 'error',
        title: 'فشلت العملية',
        showUpgradeButton: false
    };

    switch (statusCode) {
        case 409:
            return handle409Error(errorMsg, config);

        case 400:
        case 422:
            config.icon = 'warning';
            config.title = 'بيانات غير صحيحة';
            break;

        case 401:
        case 403:
            config.icon = 'warning';
            config.title = 'غير مصرح';
            break;

        // ✅ إضافة 404 — المورد غير موجود
        case 404:
            config.icon = 'warning';
            config.title = 'المستخدم غير موجود';
            break;

        case 408:
            config.icon = 'warning';
            config.title = 'انتهت المهلة';
            break;

        // ✅ إضافة 429 — تجاوز حد الطلبات
        case 429:
            config.icon = 'warning';
            config.title = 'طلبات كثيرة جداً';
            break;

        case 500:
        case 502:
        case 503:
        case 504:
            config.icon = 'error';
            config.title = 'خطأ في الخادم';
            break;
    }

    return config;
}


function handle409Error(errorMsg, config) {
    // ترخيص أساسي
    if (/الترخيص الأساسي|المستخدم الإداري/i.test(errorMsg)) {
        config.icon = 'info';
        config.title = 'غير مسموح بالتعديل';
    }

    // نفاذ التراخيص — ✅ regex محدد لا يطابق أي كلمة "متاحة" عشوائية
    else if (/لا توجد تراخيص متاحة|no.*seats.*available|quota.*exceeded/i.test(errorMsg)) {
        config.icon = 'warning';
        config.title = 'لا توجد تراخيص متاحة';
        config.showUpgradeButton = true;
    }

    // يملك ترخيص بالفعل
    else if (/يملك ترخيص|already.*license/i.test(errorMsg)) {
        config.icon = 'info';
        config.title = 'تعارض في الحالة';
    }

    // قاعدة الـ 30 يوم
    else if (/30 يومًا|30.*يوم/i.test(errorMsg)) {
        config.icon = 'warning';
        config.title = 'لا يمكن التحرير حالياً';
    }

    // حالات 409 أخرى
    else {
        config.icon = 'warning';
        config.title = 'تعارض في الحالة';
    }

    return config;
}


/*
|============================================================================
|  Utilities
|============================================================================
*/

function revertSwitch(element, originalState) {
    element.checked = originalState;
    element.disabled = false;
}

function reloadUsersTable() {
    if ($.fn.DataTable?.isDataTable('#users-table')) {
        $('#users-table').DataTable().ajax.reload(null, false);
    }
}

function redirectToSubscriptions() {
    if (window.licenseRoutes?.subscriptions) {
        window.location.href = window.licenseRoutes.subscriptions;
    }
}
