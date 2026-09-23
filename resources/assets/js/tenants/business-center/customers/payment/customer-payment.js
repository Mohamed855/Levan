'use strict';

let currentCustomerId;

$(document).ready(function () {
    currentCustomerId = window.customerId || $('[data-customer-id]').first().data('customer-id') || $('#payments-table').data('customer-id');
    
    // تشخيص للتأكد من وجود CSRF Token
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    // console.log('CSRF Token:', csrfToken);
    // console.log('Current Customer ID:', currentCustomerId);
    
    if (!csrfToken) {
        // console.error('CSRF Token not found! Make sure <meta name="csrf-token"> exists in the head.');
    }

    $(document).on('click', '.js-create-payment-btn', function () {
        showCreatePaymentModal();
    });

    $(document).on('click', '.js-edit-payment-btn', function () {
        editCustomerPayment($(this).data('id'));
    });

    $(document).on('click', '.js-delete-payment-btn', function () {
        deleteCustomerPayment($(this).data('id'));
    });
});

// فتح مودال الإضافة
async function showCreatePaymentModal() {
    if (!currentCustomerId) {
        toastr.error('لم يتم العثور على معرف العميل');
        return;
    }

    try {
        const existingModal = document.getElementById('createPaymentModal');
        if (existingModal) {
            const modalInstance = bootstrap.Modal.getInstance(existingModal);
            if (modalInstance) modalInstance.dispose();
            existingModal.remove();
        }

        showLoadingModal('createPaymentModal', 'إضافة تحصيل جديد');

        const response = await $.ajax({
            url: `/customers/${currentCustomerId}/payments/create`,
            type: 'GET'
        });

        if (response.success && response.html) {
            $('#createPaymentModal .modal-content').html(response.html);
            initializeModalComponents('#createPaymentModal');
            bindCreateModalEvents();
            setupLiveValidation('#createPaymentModal');
        } else {
            toastr.error('حدث خطأ في تحميل النموذج');
            hideModal('createPaymentModal');
        }
    } catch (error) {
        // console.error('خطأ في تحميل المودال:', error);
        toastr.error('حدث خطأ في تحميل النموذج');
        hideModal('createPaymentModal');
    }
}

// فتح مودال التعديل
async function editCustomerPayment(paymentId) {
    if (!paymentId) {
        toastr.error('معرف التحصيل غير صحيح');
        return;
    }

    try {
        const existingModal = document.getElementById('editPaymentModal');
        if (existingModal) {
            const modalInstance = bootstrap.Modal.getInstance(existingModal);
            if (modalInstance) modalInstance.dispose();
            existingModal.remove();
        }

        showLoadingModal('editPaymentModal', 'تعديل التحصيل');

        const response = await $.ajax({
            url: `/customers/${currentCustomerId}/payments/${paymentId}/edit`,
            type: 'GET'
        });

        if (response.success && response.html) {
            $('#editPaymentModal .modal-content').html(response.html);
            initializeModalComponents('#editPaymentModal');
            bindEditModalEvents();
            setupLiveValidation('#editPaymentModal');
        } else {
            toastr.error('حدث خطأ في تحميل البيانات');
            hideModal('editPaymentModal');
        }
    } catch (error) {
        // console.error('خطأ في تحميل بيانات التعديل:', error);
        toastr.error('حدث خطأ في تحميل البيانات');
        hideModal('editPaymentModal');
    }
}

// حذف تحصيل
function deleteCustomerPayment(paymentId) {
    if (!paymentId) {
        toastr.error('معرف التحصيل غير صحيح');
        return;
    }
    
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
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/customers/${currentCustomerId}/payments/${paymentId}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message || 'تم حذف التحصيل بنجاح');
                        refreshPaymentsTable();
                    } else {
                        toastr.error(response.message || 'حدث خطأ في الحذف');
                    }
                },
                error: function (xhr) {
                    // console.error('خطأ في الحذف:', xhr);
                    let errorMessage = 'حدث خطأ أثناء حذف التحصيل';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                }
            });
        }
    });
}

// مودال التحميل
function showLoadingModal(modalId, title) {
    const loadingHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <div class="mt-3">جاري التحميل...</div>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#customer-payment-modals-container').html(loadingHtml);
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
}

function hideModal(modalId) {
    const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
    if (modal) modal.hide();
}

// تهيئة مكونات المودال
async function initializeModalComponents(modalSelector) {
    if (!$(modalSelector).length) {
        // console.error('Modal selector not found:', modalSelector);
        return;
    }

    // Select2
    $(modalSelector + ' .select2-modal').each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) {
            $(this).select2('destroy');
        }

        $(this).select2({
            placeholder: $(this).data('placeholder') || 'اختر...',
            allowClear: true,
            width: '100%',
            dropdownParent: $(modalSelector)
        });
    });

    setupLiveValidation(modalSelector);
    // console.log('تم تهيئة مكونات المودال:', modalSelector);
}

// إعداد التحقق اللحظي
function setupLiveValidation(modalSelector) {
    $(modalSelector + ' input, ' + modalSelector + ' textarea, ' + modalSelector + ' select').on('input blur change', function () {
        if ($(this).hasClass('is-invalid')) {
            clearFieldValidation($(this));
        }
    });

    $(modalSelector + ' .select2-modal').on('select2:select select2:unselect', function () {
        if ($(this).hasClass('is-invalid')) {
            clearFieldValidation($(this));
        }
    });
}

// مسح التحقق من حقل واحد
function clearFieldValidation(field) {
    field.removeClass('is-invalid');
    field.siblings('.invalid-feedback').remove();
}

// ربط أحداث مودال الإضافة
function bindCreateModalEvents() {
    $('#saveCustomerPayment').off('click.paymentModal');

    $('#saveCustomerPayment').on('click.paymentModal', function () {
        const form = $('#createCustomerPaymentForm');
        const button = $(this);
        const spinner = button.find('.spinner-border');

        // console.log('Form data before validation:', new FormData(form[0]));

        if (!validateForm(form)) {
            // console.log('Form validation failed');
            return;
        }

        button.prop('disabled', true);
        spinner.show();

        // استخدام FormData لدعم رفع الملفات
        const formData = new FormData(form[0]);
        
        // إضافة تشخيص للبيانات المرسلة
        // console.log('Sending data to:', `/customers/${currentCustomerId}/payments`);
        for (let pair of formData.entries()) {
            // console.log(pair[0] + ': ' + pair[1]);
        }

        $.ajax({
            url: `/customers/${currentCustomerId}/payments`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                // console.log('Success response:', response);
                if (response.success) {
                    toastr.success('تم حفظ التحصيل بنجاح');
                    hideModal('createPaymentModal');
                    refreshPaymentsTable();
                } else {
                    toastr.error('حدث خطأ في الحفظ');
                }
            },
            error: function (xhr) {
                // console.error('Error response:', xhr);
                // console.error('Response text:', xhr.responseText);
                
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    // console.log('Validation errors:', xhr.responseJSON.errors);
                    displayValidationErrors(form, xhr.responseJSON.errors);
                    toastr.error('يرجى تصحيح الأخطاء في النموذج');
                } else {
                    const errorMessage = xhr.responseJSON?.message || 'حدث خطأ أثناء حفظ البيانات';
                    toastr.error(errorMessage);
                }
            },
            complete: function () {
                button.prop('disabled', false);
                spinner.hide();
            }
        });
    });
}

// ربط أحداث مودال التعديل
function bindEditModalEvents() {
    $('#updateCustomerPayment').off('click.paymentModal');

    $('#updateCustomerPayment').on('click.paymentModal', function () {
        const form = $('#editCustomerPaymentForm');
        const button = $(this);
        const spinner = button.find('.spinner-border');
        const paymentId = form.data('payment-id');

        if (!validateForm(form)) {
            return;
        }

        button.prop('disabled', true);
        spinner.show();

        // استخدام FormData لدعم رفع الملفات
        const formData = new FormData(form[0]);

        $.ajax({
            url: `/customers/${currentCustomerId}/payments/${paymentId}`,
            type: 'POST', // سنستخدم POST مع _method=PUT
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    toastr.success('تم تحديث التحصيل بنجاح');
                    hideModal('editPaymentModal');
                    refreshPaymentsTable();
                } else {
                    toastr.error('حدث خطأ في التحديث');
                }
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    displayValidationErrors(form, xhr.responseJSON.errors);
                } else {
                    toastr.error('حدث خطأ أثناء تحديث البيانات');
                }
            },
            complete: function () {
                button.prop('disabled', false);
                spinner.hide();
            }
        });
    });
}

// التحقق من صحة النموذج
function validateForm(form) {
    let isValid = true;
    clearFormValidation(form);

    // console.log('Starting form validation...');

    form.find('[required]').each(function () {
        const field = $(this);
        const value = field.val();
        const fieldName = field.attr('name');
        
        // console.log(`Validating field: ${fieldName}, value: "${value}"`);
        
        if (!value || value.trim() === '') {
            // console.log(`Field ${fieldName} is empty`);
            field.addClass('is-invalid');
            showFieldError(field, 'هذا الحقل مطلوب');
            isValid = false;
        } else {
            // تحقق إضافي للمبلغ
            if (fieldName === 'collected_amount') {
                const amount = parseFloat(value);
                if (isNaN(amount) || amount <= 0) {
                    // console.log(`Invalid amount: ${value}`);
                    field.addClass('is-invalid');
                    showFieldError(field, 'يجب إدخال مبلغ صحيح أكبر من صفر');
                    isValid = false;
                }
            }
        }
    });

    // console.log('Form validation result:', isValid);
    return isValid;
}

function showFieldError(field, message) {
    field.addClass('is-invalid');
    field.siblings('.invalid-feedback').remove();
    const feedback = $('<div class="invalid-feedback"></div>');
    feedback.text(message);
    field.after(feedback);
}

function clearFormValidation(form) {
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').remove();
}

function displayValidationErrors(form, errors) {
    $.each(errors, function (field, messages) {
        const fieldElement = form.find('[name="' + field + '"]');
        if (fieldElement.length && messages.length > 0) {
            showFieldError(fieldElement, messages[0]);
        }
    });
}

function refreshPaymentsTable() {
    location.reload();
}

// تصدير الدوال
window.showCreatePaymentModal = showCreatePaymentModal;
window.editCustomerPayment = editCustomerPayment;
window.deleteCustomerPayment = deleteCustomerPayment;