'use strict';

let adminCollectionsTable;
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
    $('.select2').select2({
        placeholder: function () {
            return $(this).data('placeholder');
        },
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl'
    });

    // Select2 مع AJAX للعملاء
    $('.select2-ajax-filter').select2({
        placeholder: function () {
            return $(this).data('placeholder');
        },
        allowClear: true,
        width: '100%',
        language: {
            noResults: function () {
                return "لا توجد نتائج";
            },
            searching: function () {
                return "جاري البحث...";
            },
            inputTooShort: function () {
                return "ابحث برقم الحساب أو اسم العميل";
            }
        },
        dir: 'rtl',
        minimumInputLength: 0,
        ajax: {
            url: window.routes.searchCustomers,
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    q: params.term || '',
                    page: params.page || 1,
                    initial: params.page === undefined ? 1 : 0
                };
            },
            processResults: function (data, params) {
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

    $('.flatpickr-date').flatpickr({
        dateFormat: 'Y-m-d',
        locale: 'ar',
        allowInput: true,
        maxDate: 'today',
        allowClear: true
    });
    initializeFormValidation();
}

/*
|--------------------------------------------------------------------------
| Bind Events
|--------------------------------------------------------------------------
| Bind all event handlers for the page.
*/
function bindEvents() {
    $('#admin-collections-table').on('init.dt', function () {
        adminCollectionsTable = $(this).DataTable();
        $(this).find('th.custom-checkbox').removeAttr('title');

        AdminCollectionsSelection.initialize();
        bindTableEvents();
    });

    bindFilterEvents();
    bindModalEvents();
}
/*
|--------------------------------------------------------------------------
| Bind Table Events
|--------------------------------------------------------------------------
| Bind events specific to the DataTable.
*/
function bindTableEvents() {
    adminCollectionsTable.on('draw', function () {
        AdminCollectionsSelection.updateAfterDraw();
    });

    adminCollectionsTable.on('page', function () {
        AdminCollectionsSelection.updateAfterDraw();
    });

    adminCollectionsTable.on('search', function () {
        AdminCollectionsSelection.clearAll();
    });
}


/*
|--------------------------------------------------------------------------
| Bind Filter Events
|--------------------------------------------------------------------------
| Bind events for filters to trigger table refresh.
*/
function bindFilterEvents() {
    const filterSelectors = [
        '#filter-status',
        '#filter-added-by',
        '#filter-customer',
        '#filter-payment-method',
        '#filter-collection-type',
        '#filter-payment-type',
        '#filter-date-from',
        '#filter-date-to'
    ];

    $(filterSelectors.join(', ')).on('change', function () {
        if (adminCollectionsTable) {
            const baseUrl = window.location.pathname;
            const params = new URLSearchParams();

            filterSelectors.forEach(selector => {
                const value = $(selector).val();
                if (value) {
                    const key = selector.replace('#filter-', '').replace('-', '_');
                    params.append(key, value);
                }
            });

            const query = params.toString();
            const newUrl = query ? baseUrl + '?' + query : baseUrl;

            adminCollectionsTable.ajax.url(newUrl).load();
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
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            executeApproval(collectionId);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Execute Approval
|--------------------------------------------------------------------------
| Send AJAX request to approve collection.
*/
function executeApproval(collectionId) {
    $.ajax({
        url: window.routes.adminCollectionsApprove.replace(':id', collectionId),
        type: 'PATCH',
        data: { _token: window.csrfToken },
        beforeSend: showLoadingOverlay,
        success: function (response) {
            hideLoadingOverlay();

            if (response.success) {
                adminCollectionsTable.draw(false);
                toastr.success(response.message);
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
        url: window.routes.adminCollectionsReject.replace(':id', collectionId),
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
                adminCollectionsTable.draw(false);
                toastr.success(response.message);
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
| Handle Mass Approve
|--------------------------------------------------------------------------
| Handle approval of multiple selected collections.
*/
function handleMassApprove() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد تحصيل واحد على الأقل');
        return;
    }
    confirmMassApprove(selectedIds);
}

/*
|--------------------------------------------------------------------------
| Handle Mass Reject
|--------------------------------------------------------------------------
| Handle rejection of multiple selected collections.
*/
function handleMassReject() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        toastr.warning('يرجى تحديد تحصيل واحد على الأقل');
        return;
    }
    confirmMassReject(selectedIds);
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
| Confirm Mass Approve
|--------------------------------------------------------------------------
| Show confirmation dialog for mass approval.
*/
function confirmMassApprove(selectedIds) {
    Swal.fire({
        title: 'هل أنت متأكد من اعتماد التحصيلات المحددة؟',
        text: `سيتم اعتماد ${selectedIds.length} تحصيل نهائياً!`,
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
            cancelButton: 'btn btn-danger custom-cancel'
        },
        confirmButtonText: 'تأكيد',
        cancelButtonText: 'إلغاء',
        reverseButtons: false,
    }).then((result) => {
        if (result.isConfirmed) {
            executeMassApprove(selectedIds);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Confirm Mass Reject
|--------------------------------------------------------------------------
| Show confirmation dialog for mass rejection.
*/
function confirmMassReject(selectedIds) {
    Swal.fire({
        title: 'هل أنت متأكد من رفض التحصيلات المحددة؟',
        html: `
            <p style="font-size: 0.9rem;">سيتم رفض ${selectedIds.length} تحصيل نهائياً!</p>
            <div class="mt-3">
                <label for="swal-rejection-reason" class="form-label" style="font-size: 0.9rem;">سبب الرفض <span class="text-danger">*</span></label>
                <textarea id="swal-rejection-reason" class="form-control" rows="3" placeholder="اكتب سبب رفض التحصيلات..." maxlength="500" style="font-size: 0.85rem;"></textarea>
            </div>
        `,
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
        didOpen: () => {
            document.getElementById('swal-rejection-reason').focus();
        },
        preConfirm: () => {
            const reason = document.getElementById('swal-rejection-reason').value.trim();
            if (!reason) {
                Swal.showValidationMessage('يرجى إدخال سبب الرفض');
                return false;
            }
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            executeMassReject(selectedIds, result.value);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Execute Mass Approve
|--------------------------------------------------------------------------
| Send AJAX request for mass approval.
*/
/*
|--------------------------------------------------------------------------
| Execute Mass Approve - Final & Definitive Solution
|--------------------------------------------------------------------------
| إرسال طلب الاعتماد الجماعي بالطريقة الصحيحة التي يتوقعها Laravel
*/
/*
|--------------------------------------------------------------------------
| Execute Mass Approve - Final Corrected Solution
|--------------------------------------------------------------------------
| إرسال طلب الاعتماد الجماعي بالطريقة الصحيحة التي يتوقعها Laravel
*/
function executeMassApprove(selectedIds) {
    // التأكد من أن selectedIds هي مصفوفة
    const idsToSend = Array.isArray(selectedIds) ? selectedIds : [];

    // **الحل النهائي هنا: استخدام JSON.stringify و processData: false**
    // هذا يتجاوز مشاكل ترميز jQuery بشكل كامل
    $.ajax({
        url: window.routes.adminCollectionsApproveSelected,
        type: 'POST',
        // تعيين نوع المحتوى كـ JSON
        contentType: 'application/json',
        // منع jQuery من معالجة البيانات (نحن نرسلها جاهزة)
        processData: false,
        // تحويل كائن البيانات إلى نص JSON
        data: JSON.stringify({
            _token: window.csrfToken,
            _method: 'PATCH',
            ids: idsToSend
        }),
        beforeSend: function (jqXHR, settings) {
            // console.log('Final JSON data being sent:', settings.data);
            showLoadingOverlay();
        },
        success: function (response) {
            hideLoadingOverlay();
            handleMassApprovalResponse(response);
        },
        // في index.js - داخل دالة executeMassApprove
        error: function (xhr) {
            hideLoadingOverlay();
            let errorMessage = 'حدث خطأ غير متوقع أثناء معالجة الطلب.';

            // **معالجة أخطاء 422 بشكل آمن وقوي**
            if (xhr.status === 422) {
                // التحقق من وجود استجابة JSON وأنها تحتوي على أخطاء
                if (xhr.responseJSON) {
                    const response = xhr.responseJSON;

                    // الحالة الأولى: رسالة خطأ عامة (الاستثناءات من الخادم)
                    if (response.errors && response.errors.general) {
                        errorMessage = response.errors.general.join('<br>');
                    }
                    // الحالة الثانية: أخطاء تحقق من صحة الحقول (مثل ids.*)
                    else if (response.errors) {
                        // تحويل كائن الأخطاء إلى مصفوفة مسطحة من الرسائل
                        const errorMessages = Object.values(response.errors).flat();
                        errorMessage = errorMessages.join('<br>');
                    }
                    // الحالة الثالثة: رسالة مباشرة بدون مفتاح errors
                    else if (response.message) {
                        errorMessage = response.message;
                    }
                } else {
                    // إذا لم تكن هناك استجابة JSON على الإطلاق
                    errorMessage = 'الخادم أرجع خطأ ولكن بدون تفاصيل.';
                }

                toastr.error(errorMessage, { timeOut: 10000 });
            }
            // **معالجة الأخطاء الأخرى (مثل 500, 404, etc.)**
            else {
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                // console.error('Generic Server Error:', xhr);
                toastr.error(errorMessage);
            }
        }
    });
}
/*
|--------------------------------------------------------------------------
| Execute Mass Reject
|--------------------------------------------------------------------------
| Send AJAX request for mass rejection.
*/
function executeMassReject(selectedIds, reason) {
    $.ajax({
        url: window.routes.adminCollectionsRejectSelected,
        type: 'PATCH',
        data: {
            _token: window.csrfToken,
            ids: selectedIds,
            rejection_reason: reason
        },
        beforeSend: showLoadingOverlay,
        success: function (response) {
            hideLoadingOverlay();

            if (response.success) {
                refreshTableAndClearSelection();
                toastr.success(response.message);
                updateStatistics();
            } else {
                toastr.error(response.message);
            }
        },
        error: function (xhr) {
            hideLoadingOverlay();
            const errorMessage = xhr.responseJSON?.message || 'حدث خطأ أثناء رفض التحصيلات';
            toastr.error(errorMessage);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Handle Mass Approval Response
|--------------------------------------------------------------------------
| Handle different response scenarios for mass approval.
*/
function handleMassApprovalResponse(response) {
    refreshTableAndClearSelection();
    updateStatistics();

    if (response.status === 'full') {
        toastr.success(response.message);
    } else if (response.status === 'partial') {
        if (response.details.approved_count > 0) {
            toastr.success(`تم اعتماد ${response.details.approved_count} تحصيل بنجاح.`);
        }
        if (response.details.failed_count > 0) {
            toastr.warning(`فشل اعتماد ${response.details.failed_count} تحصيل بسبب تجاوز المديونية.`, '', {
                timeOut: 12000
            });
        }
    } else if (response.success) {
        toastr.info(response.message);
    } else {
        toastr.error(response.message || 'فشلت العملية لسبب غير معروف.');
    }
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
    return AdminCollectionsSelection.getSelectedIds();
}

/*
|--------------------------------------------------------------------------
| Refresh Table And Clear Selection
|--------------------------------------------------------------------------
| Refresh DataTable and reset all selections.
*/
function refreshTableAndClearSelection() {
    AdminCollectionsSelection.clearAll();
    if (adminCollectionsTable) {
        adminCollectionsTable.draw();
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
    $.ajax({
        url: window.routes.adminCollectionsQuickStats,
        type: 'GET',
        success: function (response) {
            if (response.success) {
                // console.log('تم تحديث الإحصائيات', response.data);
            }
        },
        error: function () {
            console.log('خطأ في تحديث الإحصائيات');
        }
    });
}



$(window).on('beforeunload', function () {
    if (typeof AdminCollectionsSelection !== 'undefined') {
        AdminCollectionsSelection.cleanup();
    }
});

window.showRejectModal = showRejectModal;
window.confirmApproveCollection = confirmApproveCollection;
window.handleMassApprove = handleMassApprove;
window.handleMassReject = handleMassReject;
window.handleMassDelete = handleMassDelete;