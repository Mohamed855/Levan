/**
 * Users Index - Interactive Functions
 */
'use strict';

import { initializeTableStatusToggles } from '../../../shared/table-status-toggle';

$(document).ready(function () {
    initializeTableStatusToggles();
    initializeComponents();
    
    $('#users-table').on('init.dt', function () {
        bindTableEvents();
    });
});

function initializeComponents() {
    $('.select2').each(function () {
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

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'User');
}

window.handleMassDelete = handleMassDelete;
