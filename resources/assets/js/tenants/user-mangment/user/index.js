/**
 * Users Index - Interactive Functions
 */
'use strict';

$(document).ready(function () {
    initializeComponents();
    
    $('#users-table').on('preInit.dt', function () {
        setupFilters();
    });
    
    $('#users-table').on('init.dt', function () {
        bindTableEvents();
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

function setupFilters() {
    $('#users-table').on('preXhr.dt', function (e, settings, data) {
        data.status = $('#filter-status').val();
        data.role = $('#filter-role').val();
        data.branch_ids = $('#filter-branch').val();
    });
}

function bindTableEvents() {
    const table = $('#users-table').DataTable();
    
    $('#filter-status, #filter-role, #filter-branch').on('change', function () {
        table.ajax.reload(null, false); 
    });

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

function toggleStatus(userId) {
    $.ajax({
        url: window.routes.toggleStatus.replace('___ID___', userId),
        type: 'PATCH',
        data: { _token: window.csrfToken },
        success: function (response) {
            if (response.success) {
                $('#users-table').DataTable().ajax.reload(null, false);
                toastr.success(response.message);
            } else {
                toastr.error(response.message || 'حدث خطأ ما');
            }
        },
        error: function () {
            toastr.error('حدث خطأ أثناء الاتصال بالخادم');
        }
    });
}

window.toggleStatus = toggleStatus;
window.handleMassDelete = handleMassDelete;