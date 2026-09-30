/**
 * Teams Index - Interactive Functions
 */
'use strict';

import { initializeTableStatusToggles } from '../../../shared/table-status-toggle';

$(document).ready(function () {
    initializeTableStatusToggles();
    initializeComponents();
    
    $('#teams-table').on('preInit.dt', function () {
        setupFilters();
    });
    
    $('#teams-table').on('init.dt', function () {
        bindTableEvents();
    });
});

/*
|--------------------------------------------------------------------------
| Initialize Components
|--------------------------------------------------------------------------
*/
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

/*
|--------------------------------------------------------------------------
| Setup Filters
|--------------------------------------------------------------------------
*/
function setupFilters() {
    if ($.fn.DataTable.isDataTable('#teams-table')) {
        const table = $('#teams-table').DataTable();
        
        table.on('preXhr.dt', function (e, settings, data) {
            data.status = $('#filter-status').val();
            data.branch_id = $('#filter-branch').val();
        });
    }
}

/*
|--------------------------------------------------------------------------
| Bind Table Events
|--------------------------------------------------------------------------
*/
function bindTableEvents() {
    const table = $('#teams-table').DataTable();
    
    $('#filter-status, #filter-branch').on('change', function () {
        table.ajax.reload(null, false);
    });

    const tableWrapper = $('#teams-table_wrapper');
    
    tableWrapper.on('click', '#select-all', function () {
        $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
    });

    tableWrapper.on('change', '.row-checkbox', function () {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Handle Mass Delete
|--------------------------------------------------------------------------
*/
function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد مجموعة واحدة على الأقل');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Team');
}

window.handleMassDelete = handleMassDelete;
