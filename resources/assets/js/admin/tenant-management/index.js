
'use strict';

$(document).ready(function () {
    initializeComponents();

    // انتظر تحميل DataTable بشكل كامل
    $('#tenant-table').on('init.dt', function () {
        bindTableEvents();
    });
});

function initializeComponents() {
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
}

function bindTableEvents() {
    const table = $('#tenant-table').DataTable();

    // الفلاتر
    $('#filter-status, #filter-role, #filter-branch').on('change', function () {
        table.draw();
    });

    const tableWrapper = $('#tenant-table_wrapper');

    // تحديد الكل
    tableWrapper.on('click', '#select-all', function () {
        $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
    });

    tableWrapper.on('change', '.row-checkbox', function () {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        }
    });
}

function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد مستخدم واحد على الأقل');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Tenant');
}

// function toggleStatus(userId) {
//     // التحقق من وجود userId
//     if (!userId) {
//         toastr.error('معرف المستخدم غير صحيح');
//         return;
//     }

//     // التحقق من وجود CSRF token
//     const csrfToken = $('meta[name="csrf-token"]').attr('content') || window.csrfToken;

//     if (!csrfToken) {
//         toastr.error('رمز الأمان مفقود');
//         return;
//     }

//     // إظهار مؤشر التحميل (اختياري)
//     const $button = $(`[onclick="toggleStatus(${userId})"]`);
//     const originalHtml = $button.html();
//     $button.prop('disabled', true).html('<i class="ti ti-loader ti-spin"></i>');

//     $.ajax({
//         url: window.routes.toggleStatus.replace(':id', userId),
//         type: 'PATCH',
//         headers: {
//             'X-CSRF-TOKEN': csrfToken
//         },
//         data: {
//             _token: csrfToken
//         },
//         success: function (response) {
//             if (response.success) {
//                 // إعادة تحميل الجدول مع الحفاظ على الصفحة الحالية
//                 $('#tenant-table').DataTable().ajax.reload(null, false);
//                 toastr.success(response.message || 'تم تغيير الحالة بنجاح');
//             } else {
//                 toastr.error(response.message || 'حدث خطأ ما');
//             }
//         },
//         error: function (xhr, status, error) {
//             console.error('Toggle Status Error:', {
//                 status: xhr.status,
//                 statusText: xhr.statusText,
//                 response: xhr.responseText,
//                 error: error
//             });

//             let errorMessage = 'حدث خطأ أثناء الاتصال بالخادم';

//             // معالجة الأخطاء المختلفة
//             if (xhr.status === 403) {
//                 errorMessage = 'ليس لديك صلاحية لتنفيذ هذا الإجراء';
//             } else if (xhr.status === 404) {
//                 errorMessage = 'المستخدم غير موجود';
//             } else if (xhr.status === 419) {
//                 errorMessage = 'انتهت صلاحية الجلسة، يرجى تحديث الصفحة';
//             } else if (xhr.status === 422) {
//                 errorMessage = 'بيانات غير صالحة';
//             } else if (xhr.responseJSON && xhr.responseJSON.message) {
//                 errorMessage = xhr.responseJSON.message;
//             }

//             toastr.error(errorMessage);
//         },
//         complete: function () {
//             // إعادة الزر لحالته الأصلية
//             if ($button.length) {
//                 $button.prop('disabled', false).html(originalHtml);
//             }
//         }
//     });
// }

// تصدير الدوال للاستخدام العام
// window.toggleStatus = toggleStatus;
window.handleMassDelete = handleMassDelete;
