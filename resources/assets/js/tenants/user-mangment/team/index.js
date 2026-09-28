/**
 * Teams Index - Interactive Functions
 */
'use strict';

$(document).ready(function () {
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

/*
|--------------------------------------------------------------------------
| Toggle Status
|--------------------------------------------------------------------------
*/
function toggleStatus(element, teamId) {
    let el = null;
    let id = teamId;
    if (typeof element === 'number' || typeof element === 'string') {
        id = element;
        el = document.getElementById('status_' + id);
    } else {
        el = element;
    }

    if (!id) {
        toastr.error('معرف الفريق غير صحيح');
        return;
    }

    if (el) {
        $(el).prop('disabled', true);
    }

    $.ajax({
        url: window.routes.toggleStatus.replace('___ID___', id),
        type: 'PATCH',
        data: { _token: window.csrfToken },
        success: function (response) {
            if (response.success) {
                toastr.success(response.message);
                if (el) {
                    $(el).prop('disabled', false);
                } else {
                    $('#teams-table').DataTable().ajax.reload(null, false);
                }
            } else {
                toastr.error(response.message || 'حدث خطأ ما');
                if (el) {
                    $(el).prop('checked', !el.checked).prop('disabled', false);
                }
            }
        },
        error: function (xhr) {
            let errorMsg = 'حدث خطأ أثناء الاتصال بالخادم';
            if (xhr.responseJSON?.message) {
                errorMsg = xhr.responseJSON.message;
            }
            toastr.error(errorMsg);
            if (el) {
                $(el).prop('checked', !el.checked).prop('disabled', false);
            }
        }
    });
}

// Global functions
window.toggleStatus = toggleStatus;
window.handleMassDelete = handleMassDelete;