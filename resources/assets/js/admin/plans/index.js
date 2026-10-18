'use strict';

import Sortable from 'sortablejs';
import { initializeTableStatusToggles } from '../../shared/table-status-toggle';

$(document).ready(function () {
    initializeTableStatusToggles();
    $('#plans-table').on('init.dt', function () {
        bindTableEvents();
        initSortable();
    });

    // إعادة تفعيل السحب بعد كل رسم للجدول
    $('#plans-table').on('draw.dt', function () {
        initSortable();
    });
});

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

function initSortable() {
    const tbody = document.querySelector('#plans-table tbody');

    if (!tbody) {
        console.warn('Table body not found');
        return;
    }

    // تدمير السابق إن وجد
    if (tbody.sortableInstance) {
        tbody.sortableInstance.destroy();
    }

    // إنشاء Sortable جديد
    tbody.sortableInstance = Sortable.create(tbody, {
        handle: '.cursor-move',
        animation: 200,
        ghostClass: 'dragging',
        dragClass: 'sortable-drag',
        easing: 'cubic-bezier(1, 0, 0, 1)',
        onStart: function (evt) {
            $(evt.item).addClass('is-dragging');
        },
        onEnd: function (evt) {
            $(evt.item).removeClass('is-dragging');

            // تحديث الترتيب في السيرفر
            if (evt.oldIndex !== evt.newIndex) {
                updateSortOrder();
            }
        }
    });
}

function updateSortOrder() {
    const tbody = $('#plans-table tbody');
    const order = [];

    tbody.find('tr').each(function (index) {
        const id = $(this).find('.row-checkbox').val();
        if (id) {
            order.push({
                id: id,
                sort_order: index + 1
            });
        }
    });

    if (order.length === 0) {
        console.warn('No items to sort');
        return;
    }

    // عرض مؤشر التحميل
    toastr.info('جاري تحديث الترتيب...', '', { timeOut: 1000 });

    // إرسال الترتيب الجديد للسيرفر
    $.ajax({
        url: window.routes.updateSortOrder,
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            order: order
        },
        success: function (response) {
            if (response.success) {
                toastr.success(response.message || 'تم تحديث الترتيب بنجاح');
                // تحديث أرقام الترتيب في الجدول
                updateTableDisplay();
            } else {
                toastr.error(response.message || 'فشل تحديث الترتيب');
                reloadTable();
            }
        },
        error: function (xhr) {
            console.error('Sort order update failed:', xhr);
            toastr.error('حدث خطأ أثناء تحديث الترتيب');
            reloadTable();
        }
    });
}

function updateTableDisplay() {
    const tbody = $('#plans-table tbody');
    tbody.find('tr').each(function (index) {
        const sortOrderCell = $(this).find('.plan-sort-order');
        sortOrderCell.find('.badge').text(index + 1);
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
window.initSortable = initSortable;
window.updateSortOrder = updateSortOrder;
