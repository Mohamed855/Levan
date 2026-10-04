'use strict';

$(function () {
    // Initialize Select2
    $('.select2').select2({
        allowClear: true,
        placeholder: 'الكل',
        dir: 'rtl'
    });

    // Initialize DataTable
    $('#tasks-table').on('init.dt', function () {
        const table = $(this).DataTable();

        // Custom Date Range Toggle
        $('#filter-overdue-days').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#custom-date-range').removeClass('d-none');
            } else {
                $('#custom-date-range').addClass('d-none');
                $('#filter-date-from, #filter-date-to').val('');
            }
        });

        $(document).on('datatable-filter:reset', function (e, data) {
            if (data && data.targetTable === '#tasks-table') {
                $('#custom-date-range').addClass('d-none');
            }
        });

        // Select All Checkbox
        const tableWrapper = $('#tasks-table_wrapper');

        tableWrapper.on('click', '#select-all', function () {
            const rows = table.rows({ 'search': 'applied' }).nodes();
            $('input.row-checkbox', rows).prop('checked', this.checked);
        });

        tableWrapper.on('change', '.row-checkbox', function () {
            if (!this.checked) {
                $('#select-all').prop('checked', false);
            }
        });

        table.on('draw', function () {
            $('#select-all').prop('checked', false);
        });
    });

    // Initialize Tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});

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

    if (selectedIds.length < 1) {
        toastr.warning('يرجى تحديد مهمة واحدة على الأقل.');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Task');
}

/*
|--------------------------------------------------------------------------
| Confirm Delete Selected (Mass Delete)
|--------------------------------------------------------------------------
*/
function confirmDeleteSelectedmss(ids, url, modelName) {
    Swal.fire({
        title: 'هل أنت متأكد؟',
        text: `سيتم حذف ${ids.length} مهمة. لا يمكن التراجع عن هذا الإجراء!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذف!',
        cancelButtonText: 'إلغاء',
        customClass: {
            confirmButton: 'btn btn-danger me-3',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    ids: ids,
                    model: modelName,
                    _token: window.csrfToken
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message || 'تم الحذف بنجاح');
                        $('#tasks-table').DataTable().draw(false);
                    } else {
                        toastr.error(response.message || 'حدث خطأ أثناء الحذف');
                    }
                },
                error: function (xhr) {
                    let errorMessage = 'حدث خطأ أثناء الحذف';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                }
            });
        }
    });
}

// Export functions to global scope
window.handleMassDelete = handleMassDelete;