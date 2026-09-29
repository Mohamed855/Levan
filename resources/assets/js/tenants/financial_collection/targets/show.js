'use strict';
let userTargetsTable;

/*
|--------------------------------------------------------------------------
| Document Ready
|--------------------------------------------------------------------------
*/
$(document).ready(function () {
    initializeComponents();
    bindEvents();
});

/*
|--------------------------------------------------------------------------
| Initialize Components
|--------------------------------------------------------------------------
*/
function initializeComponents() {
    // لا حاجة لـ Select2 في صفحة التفاصيل
}

/*
|--------------------------------------------------------------------------
| Bind Events
|--------------------------------------------------------------------------
*/
function bindEvents() {
    $('#user-targets-table').on('init.dt', function () {
        userTargetsTable = $(this).DataTable();
        bindTableEvents();
    });
}

/*
|--------------------------------------------------------------------------
| Bind Table Events
|--------------------------------------------------------------------------
*/
function bindTableEvents() {
    // لا حاجة لـ select-all في صفحة التفاصيل
}

/*
|--------------------------------------------------------------------------
| Delete Single Target (تم تصديرها للاستخدام العام)
|--------------------------------------------------------------------------
*/
function deleteSingleTarget(targetId, deleteRoute) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: 'سيتم حذف هذا الهدف نهائياً',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، احذف',
        cancelButtonText: 'إلغاء',
        customClass: {
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: deleteRoute,
                type: 'DELETE',
                data: { _token: window.csrfToken },
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم الحذف!',
                        text: 'تم حذف الهدف بنجاح',
                        customClass: {
                            confirmButton: 'btn btn-success'
                        }
                    }).then(() => {
                        if (userTargetsTable) {
                            userTargetsTable.ajax.reload(null, false);
                        } else {
                            window.location.reload();
                        }
                    });
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ!',
                        text: 'حدث خطأ أثناء حذف الهدف',
                        customClass: {
                            confirmButton: 'btn btn-danger'
                        }
                    });
                }
            });
        }
    });
}

/*
|--------------------------------------------------------------------------
| Export Functions
|--------------------------------------------------------------------------
*/
window.deleteSingleTarget = deleteSingleTarget;