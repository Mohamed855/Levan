/*
|--------------------------------------------------------------------------
| Notifications All: Single Source of Truth for DataTable Filters (type, status)
|--------------------------------------------------------------------------
| Initializes Select2, wires filters to DataTables AJAX source, and handles
| select-all / mass actions and notification management.
*/

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    // تهيئة Select2
    $('.select2').select2({
        allowClear: true,
        placeholder: 'الكل',
        dir: 'rtl'
    });

    // تهيئة DataTable
    $('#notifications-table').on('init.dt', function () {
        const table = $(this).DataTable();

        // تطبيق الفلاتر
        function applyFilters() {
            const type = $('#filter-type').val() || '';
            const status = $('#filter-status').val() || '';
            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (type) params.append('type', type);
            if (status) params.append('status', status);

            const query = params.toString();
            const newUrl = query ? baseUrl + '?' + query : baseUrl;

            table.ajax.url(newUrl).load();
        }

        // استماع لتغييرات الفلاتر
        $('#filter-type, #filter-status').on('change', applyFilters);

        const tableWrapper = $('#notifications-table_wrapper');

        // تحديد الكل/إلغاء تحديد الكل
        tableWrapper.on('click', '#select-all', function () {
            const rows = table.rows({ search: 'applied' }).nodes();
            $('input.row-checkbox', rows).prop('checked', this.checked);
        });

        tableWrapper.on('change', '.row-checkbox', function () {
            if (!this.checked) {
                $('#select-all').prop('checked', false);
            }
        });

        table.on('draw', function () {
            $('#select-all').prop('checked', false);
        });
    });
});

// تحديد إشعار كمقروء
function markAsRead(notificationId) {
    if (!notificationId) {
        console.error('معرف الإشعار مطلوب');
        return;
    }

    const url = window.routes.markAsRead.replace('{id}', notificationId);

    $.ajax({
        url: url,
        type: 'POST',
        data: {
            _token: window.csrfToken
        },
        success: function (response) {
            if (response.message) {
                $('#notifications-table').DataTable().draw(false);
                updateNotificationCount();
                if (typeof toastr !== 'undefined') {
                    toastr.success(response.message);
                }
            }
        },
        error: function (xhr) {
            let errorMessage = 'حدث خطأ أثناء تحديد الإشعار كمقروء';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            if (typeof toastr !== 'undefined') {
                toastr.error(errorMessage);
            }

            console.error('خطأ في تحديد الإشعار كمقروء:', xhr);
        }
    });
}

// تحديد جميع الإشعارات كمقروءة
function markAllAsRead() {
    $.ajax({
        url: window.routes.markAllAsRead,
        type: 'POST',
        data: {
            _token: window.csrfToken
        },
        success: function (response) {
            if (response.message) {
                $('#notifications-table').DataTable().draw(false);
                updateNotificationCount();
                if (typeof toastr !== 'undefined') {
                    toastr.success(response.message);
                }
            }
        },
        error: function (xhr) {
            let errorMessage = 'حدث خطأ أثناء تحديد جميع الإشعارات كمقروءة';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            if (typeof toastr !== 'undefined') {
                toastr.error(errorMessage);
            }

            console.error('خطأ في تحديد جميع الإشعارات كمقروءة:', xhr);
        }
    });
}

// حذف إشعار
function deleteNotification(notificationId) {
    if (!notificationId) {
        console.error('معرف الإشعار مطلوب');
        return;
    }

    Swal.fire({
        title: 'هل أنت متأكد من عملية الحذف؟',
        text: " لا يمكن التراجع عن هذا الإجراء!",
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
        confirmButtonText: 'تأكيد',
        cancelButtonText: 'إلغاء',
        reverseButtons: false,
    }).then((result) => {
        if (result.isConfirmed) {
            const url = window.routes.deleteNotification.replace('{id}', notificationId);

            $.ajax({
                url: url,
                type: 'DELETE',
                data: {
                    _token: window.csrfToken
                },
                success: function (response) {
                    if (response.message) {
                        $('#notifications-table').DataTable().draw(false);
                        updateNotificationCount();
                        if (typeof toastr !== 'undefined') {
                            toastr.success(response.message);
                        }
                    }
                },
                error: function (xhr) {
                    let errorMessage = 'حدث خطأ أثناء حذف الإشعار';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMessage);
                    }

                    console.error('خطأ في حذف الإشعار:', xhr);
                }
            });
        }
    });
}

// حذف متعدد للإشعارات
function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length < 1) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('يرجى تحديد عنصر واحد على الأقل.');
        }

        return;
    }

    if (!confirm('هل أنت متأكد من حذف الإشعارات المحددة؟')) {
        return;
    }

    $.ajax({
        url: window.routes.massDelete,
        type: 'POST',
        data: {
            _token: window.csrfToken,
            ids: selectedIds
        },
        success: function (response) {
            if (response.message) {
                $('#notifications-table').DataTable().draw(false);
                updateNotificationCount();
                if (typeof toastr !== 'undefined') {
                    toastr.success(response.message);
                }
            }
        },
        error: function (xhr) {
            let errorMessage = 'حدث خطأ أثناء حذف الإشعارات';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            if (typeof toastr !== 'undefined') {
                toastr.error(errorMessage);
            }

            console.error('خطأ في حذف الإشعارات:', xhr);
        }
    });
}

// تحديث عدد الإشعارات غير المقروءة
function updateNotificationCount() {
    fetch('/notifications/unread-count', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': window.csrfToken,
            'Accept': 'application/json'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('فشل في تحديث عدد الإشعارات');
            }

            return response.json();
        })
        .then(data => {
            const badge = document.querySelector('.badge-notifications');
            const countBadge = document.getElementById('unreadCountBadge');

            if (data.count > 0) {
                if (badge) badge.style.display = 'block';
                if (countBadge) countBadge.textContent = `${data.count} جديد`;
            } else {
                if (badge) badge.style.display = 'none';
                if (countBadge) countBadge.textContent = '0 جديد';
            }
        })
        .catch(error => {
            console.error('خطأ في تحديث عدد الإشعارات:', error);
        });
}

// تعريف الدوال في النافذة العامة لتكون متاحة من HTML
window.markAsRead = markAsRead;
window.markAllAsRead = markAllAsRead;
window.deleteNotification = deleteNotification;
window.handleMassDelete = handleMassDelete;
