'use strict';

$(function () {
    // ==============================
    // 🔹 تهيئة Select2
    // ==============================
    $('.select2').each(function () {
        $(this).select2({
            placeholder: function () {
                return $(this).data('placeholder');
            },
            allowClear: true,
            width: '100%',
            language: 'ar',
            dir: 'rtl',
            dropdownParent: $(this).closest('#card-users')
        });
    });

    // ==============================
    // 🔹 عند تهيئة الجدول DataTable
    // ==============================
    $('#users-table').on('init.dt', function () {
        const usersTable = $(this).DataTable(); // ✅ الجدول نفسه من Yajra

        // ==============================
        // 🔹 تطبيق الفلاتر (status, type, )
        // ==============================
        function applyFilters() {
            const filters = {
                status: $('#filter-status').val() || '',
                role: $('#filter-role').val() || '',
            };

            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            if (filters.status) params.append('status', filters.status);
            if (filters.role) params.append('role', filters.role);

            const newUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');
            usersTable.ajax.url(newUrl).load();
        }

        $('#filter-status, #filter-role').on('change', applyFilters);

        // ==============================
        // 🔹 تحديد الكل
        // ==============================
        $('#select-all').on('click', function () {
            const rows = usersTable.rows({ 'search': 'applied' }).nodes();
            $('input[type="checkbox"]', rows).prop('checked', this.checked);
        });

        // ==============================
        // 🔹 ربط أحداث المودالات
        // ==============================
        bindModalEvents(usersTable);
    });
});

/**
 * ==============================
 * 🔹 ربط أحداث المودالات
 * ==============================
 */
function bindModalEvents(usersTable) {
    // فتح مودال تعديل الدور
    $('#users-table').on('click', '.edit-role-link', function () {
        const userId = $(this).data('user-id');
        if (!userId) {
            toastr.error('معرف المستخدم غير موجود.');
            return;
        }

        openEditRoleModal(userId, usersTable);
    });

    // حفظ تعديل الدور
    $('#updateRoleForm').on('submit', function (e) {
        e.preventDefault();
        updateUserRole(usersTable);
    });

    // تغيير حالة المستخدم من القائمة المنسدلة
    $('#users-table').on('change', '.status-dropdown', function () {
        const userId = $(this).data('user-id');
        const newStatus = $(this).val();
        updateUserStatus(userId, newStatus);
    });
}

/**
 * ==============================
 * 🔹 فتح مودال تعديل الدور
 * ==============================
 */
function openEditRoleModal(userId, usersTable) {
    $('#editRoleModal').modal('show');
    $('#editRoleModal #user-id').val(userId);

    $.ajax({
        url: window.routes.getUserRoles.replace(':id', userId),
        method: 'GET',
        success: function (data) {
            if (data.error) {
                toastr.error(data.error);
                return;
            }

            populateRoleModal(data);
        },
        error: function (xhr) {
            console.error('AJAX Error:', xhr.responseText);
            toastr.error('فشل في جلب الأدوار والحالة.');
        }
    });
}

/**
 * ==============================
 * 🔹 تعبئة مودال تعديل الدور
 * ==============================
 */
function populateRoleModal(data) {
    const modal = $('#editRoleModal');
    const roleSelect = modal.find('#user-role');
    const statusSelect = modal.find('#user-status');

    roleSelect.empty().append('<option value=""></option>');
    $.each(data.roles, function (_, role) {
        roleSelect.append(
            `<option value="${role.id}" ${data.assigned_role === role.id ? 'selected' : ''}>${role.name}</option>`
        );
    });

    statusSelect.empty()
        .append('<option value=""></option>')
        .append(`<option value="active" ${data.assigned_status === 'active' ? 'selected' : ''}>نشط</option>`)
        .append(`<option value="inactive" ${data.assigned_status === 'inactive' ? 'selected' : ''}>غير نشط</option>`);

    // تهيئة Select2 داخل المودال
    roleSelect.select2({
        dropdownParent: modal,
        placeholder: 'اختر الدور',
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl'
    });

    statusSelect.select2({
        dropdownParent: modal,
        placeholder: 'اختر الحالة',
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl'
    });
}

/**
 * ==============================
 * 🔹 تحديث دور المستخدم
 * ==============================
 */
function updateUserRole(usersTable) {
    const userId = $('#user-id').val();
    const role = $('#user-role').val();
    const status = $('#user-status').val();

    $.ajax({
        url: window.routes.updateUserRoles.replace(':id', userId),
        method: 'POST',
        data: {
            role,
            status,
            _token: window.csrfToken
        },
        success: function (response) {
            if (response.success) {
                toastr.success(response.message);
                $('#editRoleModal').modal('hide');
                usersTable.ajax.reload(null, false);
            } else {
                toastr.error(response.message);
            }
        },
        error: function (xhr) {
            console.error('AJAX Error:', xhr.responseText);
            toastr.error('فشل في تحديث الدور.');
        }
    });
}

/**
 * ==============================
 * 🔹 تحديث حالة المستخدم
 * ==============================
 */
function updateUserStatus(userId, newStatus) {
    $.ajax({
        url: window.routes.updateStatus.replace(':id', userId),
        method: 'PATCH',
        data: {
            status: newStatus,
            _token: window.csrfToken
        },
        success: function (response) {
            if (response.success) {
                toastr.success(response.message);
            } else {
                toastr.error(response.message);
            }
        },
        error: function () {
            toastr.error('فشل في تحديث الحالة.');
        }
    });
}

/**
 * ==============================
 * 🔹 تأكيد الحذف
 * ==============================
 */
function confirmDelete(roleId) {
    Swal.fire({
        title: 'هل أنت متأكد من عملية الحذف؟',
        text: 'لا يمكن التراجع عن هذا الإجراء!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'تأكيد',
        cancelButtonText: 'إلغاء',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'btn btn-success me-2',
            cancelButton: 'btn btn-danger'
        }
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('delete-form-' + roleId).submit();
        }
    });
}

function toggleStatus(userId) {
    // التحقق من وجود userId
    if (!userId) {
        toastr.error('معرف المستخدم غير صحيح');
        return;
    }

    // التحقق من وجود CSRF token
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || window.csrfToken;

    if (!csrfToken) {
        toastr.error('رمز الأمان مفقود');
        return;
    }

    // إظهار مؤشر التحميل (اختياري)
    const $button = $(`[onclick="toggleStatus(${userId})"]`);
    const originalHtml = $button.html();
    $button.prop('disabled', true).html('<i class="ti ti-loader ti-spin"></i>');

    $.ajax({
        url: window.routes.toggleStatus.replace(':id', userId),
        type: 'PATCH',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        data: {
            _token: csrfToken
        },
        success: function (response) {
            if (response.success) {
                // إعادة تحميل الجدول مع الحفاظ على الصفحة الحالية
                $('#users-table').DataTable().ajax.reload(null, false);
                toastr.success(response.message || 'تم تغيير الحالة بنجاح');
            } else {
                toastr.error(response.message || 'حدث خطأ ما');
            }
        },
        error: function (xhr, status, error) {
            console.error('Toggle Status Error:', {
                status: xhr.status,
                statusText: xhr.statusText,
                response: xhr.responseText,
                error: error
            });

            let errorMessage = 'حدث خطأ أثناء الاتصال بالخادم';

            // معالجة الأخطاء المختلفة
            if (xhr.status === 403) {
                errorMessage = 'ليس لديك صلاحية لتنفيذ هذا الإجراء';
            } else if (xhr.status === 404) {
                errorMessage = 'المستخدم غير موجود';
            } else if (xhr.status === 419) {
                errorMessage = 'انتهت صلاحية الجلسة، يرجى تحديث الصفحة';
            } else if (xhr.status === 422) {
                errorMessage = 'بيانات غير صالحة';
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            toastr.error(errorMessage);
        },
        complete: function () {
            // إعادة الزر لحالته الأصلية
            if ($button.length) {
                $button.prop('disabled', false).html(originalHtml);
            }
        }
    });
}

window.toggleStatus = toggleStatus;
window.confirmDelete = confirmDelete;
