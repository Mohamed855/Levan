
'use strict';

import { initializeTableStatusToggles } from '../../../shared/table-status-toggle';

$(document).ready(function () {
    initializeTableStatusToggles();
    initializeComponents();

    // انتظر تحميل DataTable بشكل كامل
    $('#users-table').on('init.dt', function () {
        bindTableEvents();
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

    // الفلاتر
    $('#filter-status, #filter-role, #filter-branch').on('change', function () {
        table.draw();
    });

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
