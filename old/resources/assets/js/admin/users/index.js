
'use strict';

import { initializeTableStatusToggles } from '../../shared/table-status-toggle';

$(document).ready(function () {
    initializeTableStatusToggles();
    initializeComponents();

    // انتظر تحميل DataTable بشكل كامل
    $('#users-table').on('init.dt', function () {
        bindTableEvents();
    });

    $('#users-table').on('click', '.edit-role-link', function () {
        const userId = this.dataset.userId;
        const modalElement = document.getElementById('editUserRoleModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

        $.get(window.routes.getUserRoles.replace(':id', userId))
            .done((data) => {
                const roleSelect = $('#role-assignment-role');
                roleSelect.val(data.assigned_role || '').trigger('change');
                $('#role-assignment-user-id').val(userId);
                modal.show();
            })
            .fail(() => toastr.error('تعذر تحميل بيانات الدور.'));
    });

    $('#updateUserRoleForm').on('submit', function (event) {
        event.preventDefault();

        const userId = $('#role-assignment-user-id').val();
        const role = $('#role-assignment-role').val();
        if (!role) {
            toastr.warning('يرجى اختيار الدور.');
            return;
        }

        $.post(window.routes.updateUserRole.replace(':id', userId), {
            role,
            _token: window.csrfToken
        }).done((response) => {
            toastr.success(response.message);
            bootstrap.Modal.getInstance(document.getElementById('editUserRoleModal'))?.hide();
            $('#users-table').DataTable().ajax.reload(null, false);
        }).fail((xhr) => {
            toastr.error(xhr.responseJSON?.message || 'تعذر تحديث الدور.');
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            confirmDelete(id);
        });
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
    const table = $('#users-table').DataTable();
    const tableWrapper = $('#users-table_wrapper');

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

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'AdminUser');
}

window.handleMassDelete = handleMassDelete;
