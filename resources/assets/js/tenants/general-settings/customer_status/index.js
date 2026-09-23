/*
|--------------------------------------------------------------------------
| Customer Status Index: DataTable with Checkboxes and Mass Actions
|--------------------------------------------------------------------------
| Handles DataTable initialization, checkboxes, mass delete, and status toggle
*/

'use strict';

let customerStatusDataTable = null;

$(function () {
    $('#customer-status-table').on('init.dt', function () {
        customerStatusDataTable = $(this).DataTable();
        initializeCheckboxHandlers();
    });

    /**
     * Initializes checkbox handlers for select all and individual row checkboxes.
     */
    function initializeCheckboxHandlers() {
        if (!customerStatusDataTable) return;

        $(document).on('change', '#select-all', function () {
            const isChecked = this.checked;
            const visibleRows = customerStatusDataTable.rows({ page: 'current' }).nodes();
            $('input.row-checkbox', visibleRows).prop('checked', isChecked);
        });

        $(document).on('change', '.row-checkbox', function () {
            const totalCheckboxes = $('input.row-checkbox:visible').length;
            const checkedCheckboxes = $('input.row-checkbox:visible:checked').length;

            const selectAllCheckbox = $('#select-all');
            selectAllCheckbox.prop('checked', totalCheckboxes === checkedCheckboxes);
            selectAllCheckbox.prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        });

        customerStatusDataTable.on('draw', function () {
            $('#select-all').prop('checked', false).prop('indeterminate', false);
        });
    }

    /**
     * Handles opening the add new customer status modal.
     */
    $(document).on('click', '.btn-add', function () {
        $('#addModal').modal('show');
        resetForm('#addForm');
    });

    /**
     * Handles opening the edit customer status modal.
     */
    $(document).on('click', '.btn-edit', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const status = $(this).data('status');

        $('#edit_id').val(id);
        $('#edit_name').val(name);
        $('#edit_status').val(status);

        $('#editModal').modal('show');
    });

    /**
     * Submits the add new customer status form via AJAX.
     */
    $('#addForm').on('submit', function (e) {
        e.preventDefault();

        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.text();

        submitBtn.prop('disabled', true).text('جاري الإضافة...');

        $.ajax({
            url: window.routes.store,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': window.csrfToken
            },
            success: function (response) {
                $('#addModal').modal('hide');
                if (customerStatusDataTable) {
                    customerStatusDataTable.ajax.reload(null, false);
                }
                if (typeof toastr !== 'undefined') toastr.success('تم إضافة الحالة بنجاح');
                resetForm('#addForm');
            },
            error: function (xhr) {
                handleAjaxError(xhr);
            },
            complete: function () {
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    /**
     * Submits the edit customer status form via AJAX.
     */
    $('#editForm').on('submit', function (e) {
        e.preventDefault();

        if (!this.checkValidity()) {
            e.stopPropagation();
            $(this).addClass('was-validated');
            return;
        }

        const id = $('#edit_id').val();
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.text();

        submitBtn.prop('disabled', true).text('جاري التحديث...');

        $.ajax({
            url: window.routes.update.replace(':id', id),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': window.csrfToken
            },
            success: function (response) {
                $('#editModal').modal('hide');
                if (customerStatusDataTable) {
                    customerStatusDataTable.ajax.reload(null, false);
                }
                if (typeof toastr !== 'undefined') toastr.success('تم تحديث الحالة بنجاح');
                resetForm('#editForm');
            },
            error: function (xhr) {
                handleAjaxError(xhr);
            },
            complete: function () {
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    /**
     * Resets form validation classes when a modal is hidden.
     */
    $('.modal').on('hidden.bs.modal', function () {
        $(this).find('form').removeClass('was-validated');
        $(this).find('.is-invalid').removeClass('is-invalid');
        $(this).find('.invalid-feedback').hide();
    });

    /**
     * Activates Bootstrap form validation on submit.
     */
    $('.needs-validation').on('submit', function () {
        $(this).addClass('was-validated');
    });
});

/**
 * Toggles the status of a customer status item.
 * @param {string} statusId - The ID of the customer status to toggle.
 */
function toggleStatus(statusId) {
    const url = window.routes.toggleStatus.replace(':id', statusId);
    const statusBadge = $(`.badge[onclick="toggleStatus(${statusId})"]`);

    $.ajax({
        url: url,
        type: 'PATCH',
        data: { _token: window.csrfToken },
        success: function (response) {
            if (response.success) {
                if (statusBadge.length) {
                    if (response.status === 'active') {
                        statusBadge.removeClass('bg-label-danger').addClass('bg-label-success').text('نشط');
                    } else {
                        statusBadge.removeClass('bg-label-success').addClass('bg-label-danger').text('غير نشط');
                    }
                }
                if (typeof toastr !== 'undefined') toastr.success('تم تغيير الحالة بنجاح');
            } else {
                if (typeof toastr !== 'undefined') toastr.error(response.message || 'فشل في تغيير الحالة');
            }
        },
        error: function () {
            if (typeof toastr !== 'undefined') toastr.error('حدث خطأ أثناء تغيير الحالة');
        }
    });
}

/**
 * Handles mass deletion of selected customer status items.
 */
function handleMassDelete() {
    const selectedIds = [];
    $('.row-checkbox:checked').each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length < 1) {
        if (typeof toastr !== 'undefined') toastr.warning('يرجى تحديد عنصر واحد على الأقل.');
        return;
    }

    confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'SettingsCustomerStatus', function() {
        if (customerStatusDataTable) {
            customerStatusDataTable.ajax.reload(null, false);
        }
    });
}

/**
 * Resets a form to its initial state and removes validation feedback.
 * @param {string} formSelector - The CSS selector for the form to reset.
 */
function resetForm(formSelector) {
    const form = $(formSelector)[0];
    if (form) {
        form.reset();
        $(form).removeClass('was-validated');
        $(form).find('.is-invalid').removeClass('is-invalid');
        $(form).find('.invalid-feedback').hide();
    }
}

/**
 * Handles AJAX error responses, displaying appropriate messages.
 * @param {object} xhr - The XMLHttpRequest object.
 */
function handleAjaxError(xhr) {
    if (xhr.status === 422) {
        const errors = xhr.responseJSON?.errors;
        if (errors) {
            Object.keys(errors).forEach(field => {
                const input = $(`[name="${field}"]`);
                input.addClass('is-invalid');
                const feedback = input.siblings('.invalid-feedback');
                if (feedback.length) {
                    feedback.text(errors[field][0]).show();
                }
            });
            if (typeof toastr !== 'undefined') toastr.error('يرجى تصحيح الأخطاء في النموذج');
        }
    } else if (xhr.status === 500) {
        if (typeof toastr !== 'undefined') toastr.error('حدث خطأ في الخادم. يرجى المحاولة مرة أخرى');
    } else if (xhr.status === 403) {
        if (typeof toastr !== 'undefined') toastr.error('ليس لديك صلاحية للقيام بهذا الإجراء');
    } else {
        const message = xhr.responseJSON?.message || 'حدث خطأ غير متوقع';
        if (typeof toastr !== 'undefined') toastr.error(message);
    }
    console.error('Ajax Error:', xhr);
}

// Export functions to global scope
window.toggleStatus = toggleStatus;
window.handleMassDelete = handleMassDelete;
window.customerStatusDataTable = customerStatusDataTable; // تصدير كائن DataTable نفسه