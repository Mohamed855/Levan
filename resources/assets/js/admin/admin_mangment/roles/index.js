'use strict';

import { initializeTableStatusToggles } from '../../../shared/table-status-toggle';

$(function () {
    initializeTableStatusToggles();
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
            dropdownParent: $(this).closest('.card-body')
        });
    });

    // ==============================
    // 🔹 عند تهيئة الجدول DataTable
    // ==============================
    $('#users-table').on('init.dt', function () {
        const usersTable = $(this).DataTable(); // ✅ الجدول نفسه من Yajra

        // ==============================

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

    statusSelect.empty().append('<option value=""></option>');
    $.each(data.status_options, function (value, label) {
        const option = new Option(label, value, false, data.assigned_status === value);
        statusSelect.append(option);
    });

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

window.confirmDelete = confirmDelete;
