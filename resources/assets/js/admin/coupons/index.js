
'use strict';

import { initializeTableStatusToggles } from '../../shared/table-status-toggle';

$(document).ready(function () {
    initializeTableStatusToggles();
    initializeComponents();

    // انتظر تحميل DataTable بشكل كامل
    $('#coupon-table').on('init.dt', function () {
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
    const table = $('#coupon-table').DataTable();

    // الفلاتر
    $('#filter-status, #filter-role, #filter-branch').on('change', function () {
        table.draw();
    });

    const tableWrapper = $('#coupon-table_wrapper');

    // تحديد الكل
    tableWrapper.on('click', '#select-all', function () {
        $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
    });

    tableWrapper.on('change', '.row-checkbox', function () {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        }
    });

    // نسخ الكود
    $(document).on('click', '.copyable-code-wrapper, .copyable-code, .copy-icon', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const $wrapper = $(this).closest('.copyable-code-wrapper');
        const code = ($wrapper.length ? $wrapper.data('code') : null) || $(this).data('code') || $(this).text().trim();
        const $icon = $wrapper.length ? $wrapper.find('.copy-icon, .ti-copy') : $(this).parent().find('.ti-copy, .copy-icon');

        if (!code) return;

        const copyText = function (text, cb) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(cb).catch(function () {
                    fallback(text, cb);
                });
            } else {
                fallback(text, cb);
            }
        };

        const fallback = function (text, cb) {
            try {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.top = '-9999px';
                ta.style.left = '-9999px';
                ta.setAttribute('readonly', '');
                document.body.appendChild(ta);
                ta.select();
                ta.setSelectionRange(0, ta.value.length);
                document.execCommand('copy');
                document.body.removeChild(ta);
                if (typeof cb === 'function') cb();
            } catch (err) {}
        };

        copyText(code, function () {
            const originalClass = $icon.attr('class') || 'ti ti-copy text-muted copy-icon';
            $icon.removeClass('ti-copy text-muted').addClass('ti ti-check text-success');

            if (typeof toastr !== 'undefined') {
                toastr.success('تم نسخ الكود: ' + code);
            }

            setTimeout(function () {
                $icon.attr('class', originalClass);
            }, 2000);
        });
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

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Coupon');
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
//                 $('#coupon-table').DataTable().ajax.reload(null, false);
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
