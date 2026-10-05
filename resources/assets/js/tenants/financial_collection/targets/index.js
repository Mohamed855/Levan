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
| Export Functions
|--------------------------------------------------------------------------
*/
window.handleMassDelete = handleMassDelete;
