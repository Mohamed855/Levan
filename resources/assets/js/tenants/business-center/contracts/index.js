/**
 * إدارة العقود - الصفحة الرئيسية
 * ========================
 * يحتوي هذا الملف على جميع الوظائف المطلوبة لإدارة صفحة العقود الرئيسية
 * بما في ذلك الفلترة والبحث والحذف الجماعي وتبديل الحالات
 */

'use strict';

import { initializeTableStatusToggles } from '../../../shared/table-status-toggle';

/*
|--------------------------------------------------------------------------
| Document Ready Handler
|--------------------------------------------------------------------------
| يتم تنفيذ هذا الكود عند اكتمال تحميل محتوى الصفحة
*/
$(function () {
    initializeTableStatusToggles();
    initializeComponents();
    setupDataTableEvents();
});

/*
|--------------------------------------------------------------------------
| Components Initialization
|--------------------------------------------------------------------------
| تهيئة المكونات الأساسية للصفحة مثل Select2
*/
function initializeComponents() {
    // تهيئة مكتبة Select2 للفلاتر مع دعم اللغة العربية
    $('.select2').select2({
        allowClear: true,
        placeholder: function () {
            return $(this).data('placeholder') || 'الكل';
        },
        dir: 'rtl', // دعم اللغة العربية
        language: {
            noResults: function () {
                return "لا توجد نتائج";
            },
            searching: function () {
                return "جاري البحث...";
            }
        }
    });
}

/*
|--------------------------------------------------------------------------
| DataTable Events Setup
|--------------------------------------------------------------------------
| إعداد الأحداث المتعلقة بجدول البيانات
*/
function setupDataTableEvents() {
    $('#contracts-table').on('init.dt', function () {
        const table = $(this).DataTable();

        setupSelectAllCheckbox(table);
        setupTableDrawEvents(table);
    });
}

/*
|--------------------------------------------------------------------------
| Select All Checkbox Setup
|--------------------------------------------------------------------------
| إعداد وظيفة تحديد الكل للجدول
*/
function setupSelectAllCheckbox(table) {
    const tableWrapper = $('#contracts-table_wrapper');

    // عند النقر على مربع "تحديد الكل"
    tableWrapper.on('click', '#select-all', function () {
        const isChecked = this.checked;
        const rows = table.rows({ 'search': 'applied' }).nodes();
        $('input.row-checkbox', rows).prop('checked', isChecked);

        // تحديث عداد المحدد
        updateSelectedCount();
    });

    // عند تغيير حالة أي مربع تحديد فردي
    tableWrapper.on('change', '.row-checkbox', function () {
        const totalCheckboxes = $('.row-checkbox').length;
        const checkedCheckboxes = $('.row-checkbox:checked').length;

        // إذا لم تكن جميع المربعات محددة، ألغِ تحديد "تحديد الكل"
        $('#select-all').prop('checked', totalCheckboxes === checkedCheckboxes);

        // تحديث عداد المحدد
        updateSelectedCount();
    });
}

/*
|--------------------------------------------------------------------------
| Table Draw Events
|--------------------------------------------------------------------------
| أحداث إعادة رسم الجدول
*/
function setupTableDrawEvents(table) {
    // بعد كل عملية إعادة رسم للجدول، ألغِ تحديد "تحديد الكل"
    table.on('draw', function () {
        $('#select-all').prop('checked', false);
        updateSelectedCount();
    });
}



/*
|--------------------------------------------------------------------------
| Mass Delete Handler
|--------------------------------------------------------------------------
| التعامل مع عملية الحذف الجماعي
*/
function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length < 1) {
        toastr.warning('يرجى تحديد عنصر واحد على الأقل.');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Contract');
}

/*
|--------------------------------------------------------------------------
| Get Selected IDs
|--------------------------------------------------------------------------
| الحصول على معرفات العناصر المحددة
*/
function getSelectedIds() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        const id = $(this).val();
        if (id && !isNaN(id)) {
            selectedIds.push(parseInt(id));
        }
    });
    return selectedIds;
}


/*
|--------------------------------------------------------------------------
| Perform Mass Delete
|--------------------------------------------------------------------------
| تنفيذ عملية الحذف الجماعي
*/
function performMassDelete(selectedIds) {
    showLoadingIndicator();

    $.ajax({
        url: window.routes.massDelete,
        type: 'POST',
        data: {
            _token: window.csrfToken,
            ids: selectedIds,
            model: 'Contract'
        },
        success: function (response) {
            hideLoadingIndicator();

            if (response.success) {
                // إعادة تحميل الجدول
                $('#contracts-table').DataTable().ajax.reload();

                // إعادة تعيين مربعات التحديد
                $('#select-all').prop('checked', false);
                updateSelectedCount();

                showSuccessMessage(response.message || 'تم الحذف بنجاح');
            } else {
                showErrorMessage(response.message || 'فشل في عملية الحذف');
            }
        },
        error: function (xhr, status, error) {
            hideLoadingIndicator();
            handleAjaxError(xhr, 'حدث خطأ أثناء عملية الحذف');
        }
    });
}

/*
|--------------------------------------------------------------------------
| Update Selected Count
|--------------------------------------------------------------------------
| تحديث عداد العناصر المحددة
*/
function updateSelectedCount() {
    const count = $('.row-checkbox:checked').length;
    const countElement = $('#selected-count');

    if (countElement.length) {
        countElement.text(count);
        countElement.closest('.selected-info')[count > 0 ? 'show' : 'hide']();
    }
}

/*
|--------------------------------------------------------------------------
| Utility Functions
|--------------------------------------------------------------------------
| وظائف مساعدة للرسائل والتحميل
*/

function showSuccessMessage(message) {
    if (typeof toastr !== 'undefined') {
        toastr.success(message);
    } else {
        alert(message);
    }
}

function showErrorMessage(message) {
    if (typeof toastr !== 'undefined') {
        toastr.error(message);
    } else {
        alert(message);
    }
}

function showWarningMessage(message) {
    if (typeof toastr !== 'undefined') {
        toastr.warning(message);
    } else {
        alert(message);
    }
}

function showLoadingIndicator() {
    // يمكنك إضافة مؤشر تحميل هنا
    if (typeof NProgress !== 'undefined') {
        NProgress.start();
    }
}

function hideLoadingIndicator() {
    // إخفاء مؤشر التحميل
    if (typeof NProgress !== 'undefined') {
        NProgress.done();
    }
}

function handleAjaxError(xhr, defaultMessage) {
    let message = defaultMessage;

    if (xhr.responseJSON && xhr.responseJSON.message) {
        message = xhr.responseJSON.message;
    } else if (xhr.status === 404) {
        message = 'الصفحة المطلوبة غير موجودة';
    } else if (xhr.status === 403) {
        message = 'ليس لديك صلاحية لتنفيذ هذا الإجراء';
    } else if (xhr.status === 500) {
        message = 'حدث خطأ في الخادم';
    }

    showErrorMessage(message);
}

/*
|--------------------------------------------------------------------------
| Global Function Exports
|--------------------------------------------------------------------------
| جعل الدوال متاحة بشكل عام للاستدعاء من Blade
*/
window.handleMassDelete = handleMassDelete;
