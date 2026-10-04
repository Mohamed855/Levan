'use strict';

let collectionsTable;
let currentCollectionId = null;

/*
|--------------------------------------------------------------------------
| Document Ready
|--------------------------------------------------------------------------
| Initialize all components when document is ready.
*/
$(document).ready(function () {
    initializeComponents();
    bindEvents();
});

/*
|--------------------------------------------------------------------------
| Initialize Components
|--------------------------------------------------------------------------
| Initialize Select2, Flatpickr and form validation.
*/
function initializeComponents() {
    initializeSelect2Components();
    initializeFlatpickr();
    initializeFormValidation();
}

function initializeSelect2Components() {
    // Select2 العادي
    $('.select2').select2({
        placeholder: function() {
            return $(this).data('placeholder');
        },
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "لا توجد نتائج";
            },
            searching: function() {
                return "جاري البحث...";
            }
        },
        dir: 'rtl'
    });
    
    // Select2 مع AJAX للعملاء في الفلاتر (الشاشة الرئيسية)
    $('.select2-ajax-filter').select2({
        placeholder: function() {
            return $(this).data('placeholder');
        },
        allowClear: true,
        width: '100%',
        language: {
            noResults: function() {
                return "لا توجد نتائج";
            },
            searching: function() {
                return "جاري البحث...";
            },
            inputTooShort: function() {
                return "ابحث برقم الحساب أو اسم العميل";
            }
        },
        dir: 'rtl',
        minimumInputLength: 0, // تغيير من 1 إلى 0 للسماح بالتحميل الفوري
        ajax: {
            url: window.routes.searchCustomers,
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return {
                    q: params.term || '',
                    page: params.page || 1,
                    initial: params.page === undefined ? 1 : 0 // إضافة معرف للتحميل الأولي
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                
                return {
                    results: data,
                    pagination: {
                        more: data.length >= 20
                    }
                };
            },
            cache: true
        }
    });
}


function initializeFlatpickr() {
    $('.flatpickr-date').flatpickr({
        dateFormat: 'Y-m-d',
        locale: 'ar',
        allowInput: true,
        maxDate: 'today',
        allowClear: true
    });
}
/*
|--------------------------------------------------------------------------
| Bind Events
|--------------------------------------------------------------------------
| Bind all event handlers for the page.
*/
function bindEvents() {
    $('#collections-table').on('init.dt', function () {
        collectionsTable = $(this).DataTable();
        bindTableEvents();
    });

    bindModalEvents();
}

/*
|--------------------------------------------------------------------------
| Bind Table Events
|--------------------------------------------------------------------------
| Bind events specific to the DataTable.
*/
function bindTableEvents() {
    const tableWrapper = $('#collections-table_wrapper');

    tableWrapper.on('click', '#select-all', function () {
        const rows = collectionsTable.rows({ 'search': 'applied' }).nodes();
        $('input.row-checkbox', rows).prop('checked', this.checked);
    });

    tableWrapper.on('change', '.row-checkbox', function () {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        }
    });

    collectionsTable.on('draw', function () {
        $('#select-all').prop('checked', false);
    });

    $(document).on('datatable-filter:changed datatable-filter:reset', function (e, data) {
        if (data && data.tableSelector === '#collections-table') {
            $('#select-all').prop('checked', false);
            $('.row-checkbox').prop('checked', false);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Bind Modal Events
|--------------------------------------------------------------------------
| Bind events for modals and form interactions.
*/
function bindModalEvents() {
    $('#confirm-reject').on('click', function () {
        const reason = $('#rejection-reason').val().trim();
        if (!reason) {
            toastr.error('يرجى إدخال سبب الرفض');
            $('#rejection-reason').focus();
            return;
        }
        if (currentCollectionId) {
            executeRejection(currentCollectionId, reason);
        }
    });

    $('#rejection-reason').on('input', function () {
        const currentLength = $(this).val().length;
        const maxLength = 500;
        $('#char-count').text(currentLength + '/' + maxLength);

        if (currentLength > maxLength * 0.9) {
            $('#char-count').addClass('text-warning').removeClass('text-muted');
        } else {
            $('#char-count').addClass('text-muted').removeClass('text-warning');
        }
    });

    $('#statusModal').on('hidden.bs.modal', function () {
        resetModal();
    });
}

/*
|--------------------------------------------------------------------------
| Show Reject Modal
|--------------------------------------------------------------------------
| Display rejection modal with focus on reason textarea.
*/
function showRejectModal(collectionId) {
    currentCollectionId = collectionId;
    $('#statusModal').modal('show');

    setTimeout(() => {
        $('#rejection-reason').focus();
    }, 300);
}

/*
|--------------------------------------------------------------------------
| Confirm Approve Collection
|--------------------------------------------------------------------------
| Show confirmation dialog for collection approval.
*/
function confirmApproveCollection(collectionId) {
    Swal.fire({
        title: 'هل أنت متأكد من الاعتماد؟',
        text: "سيتم اعتماد التحصيل نهائياً!",
        icon: 'question',
        showCancelButton: true,
        showConfirmButton: true,
        showDenyButton: false,
        buttonsStyling: false,
        customClass: {
            popup: 'custom-popup',
            title: 'custom-title',
            text: 'custom-text',
            confirmButton: 'btn btn-success custom-confirm',
            cancelButton: 'btn btn-secondary custom-cancel'
        },
        confirmButtonText: 'اعتماد',
        cancelButtonText: 'إلغاء',
        reverseButtons: false,
        allowOutsideClick: false,
        allowEscapeKey: true
    }).then((result) => {
        if (result.isConfirmed) {
            executeApproval(collectionId);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Confirm Reject Collection
|--------------------------------------------------------------------------
| Show rejection modal instead of SweetAlert.
*/
function confirmRejectCollection(collectionId) {
    showRejectModal(collectionId);
}

/*
|--------------------------------------------------------------------------
| Execute Approval
|--------------------------------------------------------------------------
| Send AJAX request to approve collection.
*/
function executeApproval(collectionId) {
    $.ajax({
        url: window.routes.collectionsApprove.replace(':id', collectionId),
        type: 'PATCH',
        data: { _token: window.csrfToken },
        beforeSend: showLoadingOverlay,
        success: function (response) {
            hideLoadingOverlay();

            if (response.success) {
                toastr.success(response.message);
                collectionsTable.draw(false);
                updateStatistics();
            } else {
                toastr.error(response.message);
            }
        },
        error: function (xhr) {
            hideLoadingOverlay();
            const errorMessage = xhr.responseJSON?.message || 'حدث خطأ أثناء اعتماد التحصيل';
            toastr.error(errorMessage);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Execute Rejection
|--------------------------------------------------------------------------
| Send AJAX request to reject collection.
*/
function executeRejection(collectionId, reason) {
    $.ajax({
        url: window.routes.collectionsReject.replace(':id', collectionId),
        type: 'PATCH',
        data: {
            _token: window.csrfToken,
            rejection_reason: reason
        },
        beforeSend: showLoadingOverlay,
        success: function (response) {
            hideLoadingOverlay();

            if (response.success) {
                $('#statusModal').modal('hide');
                toastr.success(response.message);
                collectionsTable.draw(false);
                updateStatistics();
            } else {
                toastr.error(response.message);
            }
        },
        error: function (xhr) {
            hideLoadingOverlay();
            const errorMessage = xhr.responseJSON?.message || 'حدث خطأ أثناء رفض التحصيل';
            toastr.error(errorMessage);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Handle Mass Delete
|--------------------------------------------------------------------------
| Handle deletion of multiple selected collections.
*/
function handleMassDelete() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد تحصيل واحد على الأقل');
        return;
    }
    confirmDeleteSelected(selectedIds, window.routes.collectionsDeleteSelected);
}

/*
|--------------------------------------------------------------------------
| Confirm Delete Selected
|--------------------------------------------------------------------------
| Show confirmation dialog for deletion.
*/
function confirmDeleteSelected(selectedIds, deleteUrl) {
    Swal.fire({
        title: 'هل أنت متأكد من عملية الحذف؟',
        text: "لا يمكن التراجع عن هذا الإجراء!",
        icon: 'warning',
        showCancelButton: true,
        showConfirmButton: true,
        showDenyButton: false,
        buttonsStyling: false,
        customClass: {
            popup: 'custom-popup',
            title: 'custom-title',
            text: 'custom-text',
            confirmButton: 'btn btn-success custom-confirm',
            cancelButton: 'btn btn-danger custom-cancel'
        },
        confirmButtonText: 'تأكيد',
        cancelButtonText: 'إلغاء',
        reverseButtons: false,
        allowOutsideClick: false,
        allowEscapeKey: true
    }).then((result) => {
        if (result.isConfirmed) {
            executeDeleteSelected(selectedIds, deleteUrl);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Execute Delete Selected
|--------------------------------------------------------------------------
| Send AJAX request for deletion.
*/
function executeDeleteSelected(selectedIds, deleteUrl) {
    $.ajax({
        url: deleteUrl,
        method: 'DELETE',
        data: {
            _token: window.csrfToken,
            ids: selectedIds,
        },
        beforeSend: showLoadingOverlay,
        success: function (response) {
            hideLoadingOverlay();

            if (response.success) {
                refreshTableAndClearSelection();
                toastr.success(response.message || 'تم حذف المحدد بنجاح.');
                updateStatistics();
            } else {
                toastr.error(response.message || 'فشل في حذف المحدد.');
            }
        },
        error: function (xhr) {
            hideLoadingOverlay();
            let errorMessage = 'حدث خطأ أثناء حذف التحصيلات';

            if (xhr.responseJSON?.message) {
                errorMessage = xhr.responseJSON.message;
            }

            toastr.error(errorMessage);
        }
    });
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
| Refresh Table And Clear Selection
|--------------------------------------------------------------------------
| Refresh DataTable and reset all selections.
*/
function refreshTableAndClearSelection() {
    $('#select-all').prop('checked', false);
    $('.row-checkbox').prop('checked', false);
    if (collectionsTable) {
        collectionsTable.draw();
    }
}

/*
|--------------------------------------------------------------------------
| Reset Modal
|--------------------------------------------------------------------------
| Reset modal form to default state.
*/
function resetModal() {
    currentCollectionId = null;
    $('#rejection-reason').val('');
    $('#char-count').text('0/500').removeClass('text-warning').addClass('text-muted');
}

/*
|--------------------------------------------------------------------------
| Initialize Form Validation
|--------------------------------------------------------------------------
| Set up form validation for any forms on the page.
*/
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

/*
|--------------------------------------------------------------------------
| Show Loading Overlay
|--------------------------------------------------------------------------
| Display loading spinner overlay.
*/
function showLoadingOverlay() {
    $('body').append('<div class="loading-overlay"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
}

/*
|--------------------------------------------------------------------------
| Hide Loading Overlay
|--------------------------------------------------------------------------
| Remove loading spinner overlay.
*/
function hideLoadingOverlay() {
    $('.loading-overlay').remove();
}

/*
|--------------------------------------------------------------------------
| Update Statistics
|--------------------------------------------------------------------------
| Fetch and update page statistics via AJAX.
*/
function updateStatistics() {
    console.log('تحديث الإحصائيات');
}

window.showRejectModal = showRejectModal;
window.confirmApproveCollection = confirmApproveCollection;
window.confirmRejectCollection = confirmRejectCollection;
window.handleMassDelete = handleMassDelete;