'use strict';

import { initializeTableStatusToggles } from '../../shared/table-status-toggle';

$(document).ready(function () {
    initializeTableStatusToggles();
    $('#plans-table').on('init.dt', function () {
        bindTableEvents();
        initTooltips();
    });

    // إعادة تفعيل التلميحات بعد كل رسم للجدول
    $('#plans-table').on('draw.dt', function () {
        initTooltips();
    });
});

function initTooltips() {
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('#plans-table [data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return bootstrap.Tooltip.getOrCreateInstance(tooltipTriggerEl);
        });
    }
}

function bindTableEvents() {
    const table = $('#plans-table').DataTable();

    const tableWrapper = $('#plans-table_wrapper');

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

function reloadTable() {
    const table = $('#plans-table').DataTable();
    table.ajax.reload(null, false);
}

function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد باقة واحدة على الأقل');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Plan');
}

// تصدير الدوال للاستخدام العام
window.handleMassDelete = handleMassDelete;
