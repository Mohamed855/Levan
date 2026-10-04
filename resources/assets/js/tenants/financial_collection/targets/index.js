'use strict';

import { initializeTableStatusToggles } from '../../../shared/table-status-toggle';
let collectionTargetsTable;

/*
|--------------------------------------------------------------------------
| Document Ready
|--------------------------------------------------------------------------
| Initialize all components when document is ready.
*/
$(document).ready(function () {
    initializeTableStatusToggles();
    initializeComponents();
    bindEvents();
});

/*
|--------------------------------------------------------------------------
| Initialize Components
|--------------------------------------------------------------------------
| Initialize Select2 and form validation.
*/
function initializeComponents() {
    $('.select2').select2({
        placeholder: function () {
            return $(this).data('placeholder');
        },
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl'
    });
}

/*
|--------------------------------------------------------------------------
| Bind Events
|--------------------------------------------------------------------------
| Bind all event handlers for the page.
*/
function bindEvents() {
    $('#collection-targets-table').on('init.dt', function () {
        $(this).find('th.custom-checkbox').removeAttr('title');

        collectionTargetsTable = $(this).DataTable();
        bindTableEvents();
    });

    bindFilterEvents();
}

/*
|--------------------------------------------------------------------------
| Bind Table Events
|--------------------------------------------------------------------------
| Bind events specific to the DataTable.
*/
function bindTableEvents() {
    const tableWrapper = $('#collection-targets-table_wrapper');

    tableWrapper.on('click', '#select-all', function () {
        const rows = collectionTargetsTable.rows({ 'search': 'applied' }).nodes();
        $('input.row-checkbox', rows).prop('checked', this.checked);
    });

    tableWrapper.on('change', '.row-checkbox', function () {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        }
    });

    collectionTargetsTable.on('draw', function () {
        $('#select-all').prop('checked', false);
    });
}

/*
|--------------------------------------------------------------------------
| Bind Filter Events
|--------------------------------------------------------------------------
| Bind events for filters to trigger table refresh.
*/
function bindFilterEvents() {
    // معالجة تغيير الفرع في الفلاتر لتحديث خيارات المستخدمين
    $('#filter-branch').on('change', function () {
        const branchId = $(this).val();
        const userSelect = $('#filter-user');

        userSelect.empty().append('<option value="">اختر المستخدم</option>');

        if (branchId) {
            userSelect.prop('disabled', false);
            userSelect.next('small').text('جاري التحميل...');

            loadUsersByBranch(branchId, userSelect, function () {
                userSelect.next('small').text('');
            });
        } else {
            userSelect.prop('disabled', true);
            userSelect.next('small').text('اختر الفرع أولاً');
        }
    });

    $(document).on('datatable-filter:reset', function (e, data) {
        if (data && data.tableSelector === '#collection-targets-table') {
            const userSelect = $('#filter-user');
            userSelect.empty().append('<option value="">اختر المستخدم</option>').prop('disabled', true);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Handle Mass Delete
|--------------------------------------------------------------------------
| Handle deletion of multiple selected collection targets.
*/
function handleMassDelete() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد هدف واحد على الأقل');
        return;
    }
    confirmDeleteSelected(selectedIds);
}

/*
|--------------------------------------------------------------------------
| Get Selected IDs
|--------------------------------------------------------------------------
| Retrieve array of selected checkbox values.
*/
function getSelectedIds() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });
    return selectedIds;
}

/*
|--------------------------------------------------------------------------
| Load Users By Branch
|--------------------------------------------------------------------------
| Load users based on selected branch for filtering.
*/
function loadUsersByBranch(branchId, targetSelect, callback) {
    if (!branchId) {
        targetSelect.empty().append('<option value="">اختر المستخدم</option>');
        if (callback) callback();
        return;
    }

    $.ajax({
        url: window.routes.getUsersByBranch,
        type: 'GET',
        data: { branch_id: branchId },
        success: function (response) {
            targetSelect.empty().append('<option value="">اختر المستخدم</option>');

            if (response.success && response.users && response.users.length > 0) {
                $.each(response.users, function (index, user) {
                    targetSelect.append(
                        $('<option></option>')
                            .attr('value', user.id)
                            .text(user.name)
                    );
                });
            } else {
                targetSelect.next('small').text('لا يوجد مستخدمين في هذا الفرع');
            }

            if (callback) callback();
        },
        error: function () {
            toastr.error('حدث خطأ أثناء جلب المستخدمين');
            targetSelect.prop('disabled', true);
            targetSelect.next('small').text('حدث خطأ في التحميل');
            if (callback) callback();
        }
    });
}


/*
|--------------------------------------------------------------------------
| Export Functions
|--------------------------------------------------------------------------
*/
window.handleMassDelete = handleMassDelete;
