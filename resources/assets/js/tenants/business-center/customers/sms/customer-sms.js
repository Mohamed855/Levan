'use strict';

let currentCustomerId;

// تصدير الدوال للنافذة العامة
window.showCreateSmsModal = showCreateSmsModal;
window.deleteSms = deleteSms;
window.toggleFullMessage = toggleFullMessage;

$(document).ready(function () {
    currentCustomerId = window.customerId;

    $(document).on('click', '.js-create-sms-btn', function () {
        showCreateSmsModal();
    });

    $(document).on('click', '.js-toggle-sms-btn', function () {
        toggleFullMessage($(this).data('id'));
    });

    $(document).on('click', '.js-delete-sms-btn', function () {
        deleteSms($(this).data('id'));
    });
});

async function showCreateSmsModal() {
    if (!currentCustomerId) {
        toastr.error('لم يتم العثور على معرف العميل');
        return;
    }

    try {
        const existingModal = document.getElementById('createSmsModal');
        if (existingModal) {
            const modalInstance = bootstrap.Modal.getInstance(existingModal);
            if (modalInstance) modalInstance.dispose();
            existingModal.remove();
        }

        showLoadingModal('createSmsModal', 'إرسال رسالة نصية');

        const response = await $.ajax({
            url: window.routes.customerSmsCreate.replace(':customerId', currentCustomerId),
            type: 'GET'
        });

        if (response.success && response.html) {
            $('#createSmsModal .modal-content').html(response.html);
            bindCreateModalEvents();
        } else {
            toastr.error(response.message || 'حدث خطأ في تحميل النموذج');
            hideModal('createSmsModal');
        }
    } catch (error) {
        console.error('خطأ في تحميل المودال:', error);
        if (error.responseJSON && error.responseJSON.message) {
            toastr.error(error.responseJSON.message);
        } else {
            toastr.error('حدث خطأ في تحميل النموذج');
        }
        hideModal('createSmsModal');
    }
}

// ربط أحداث مودال الإرسال
function bindCreateModalEvents() {
    // تفعيل select2
    $('.select2-phone').select2({
        placeholder: 'اختر رقم الهاتف',
        allowClear: true,
        dropdownParent: $('#createSmsModal')
    });

    $('.select2-template').select2({
        placeholder: 'اختر قالب الرسالة',
        allowClear: true,
        dropdownParent: $('#createSmsModal')
    });

    // عند تغيير رقم الهاتف
    $('#phone_number').on('change', function () {
        const selectedOption = $(this).find('option:selected');
        const phoneType = selectedOption.data('type');
        $('#phone_type').val(phoneType);

        checkFormCompletion();
    });

    // عند تغيير القالب
    $('#template_id').on('change', function () {
        const templateId = $(this).val();

        if (templateId) {
            loadTemplatePreview(templateId);
        } else {
            hideTemplatePreview();
        }

        checkFormCompletion();
    });

    // زر الإرسال
    $('#sendSmsButton').off('click.smsModal');
    $('#sendSmsButton').on('click.smsModal', function () {
        const form = $('#createSmsForm');
        const button = $(this);
        const spinner = button.find('.spinner-border');

        if (!validateForm(form)) {
            return;
        }

        button.prop('disabled', true);
        spinner.show();

        $.ajax({
            url: window.routes.customerSmsSend.replace(':customerId', currentCustomerId),
            type: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                    hideModal('createSmsModal');
                    location.reload();
                } else {
                    toastr.error(response.message || 'حدث خطأ في الإرسال');
                }
            },
            error: function (xhr) {
                console.error('خطأ في الإرسال:', xhr);
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    displayValidationErrors(form, xhr.responseJSON.errors);
                } else {
                    const errorMessage = xhr.responseJSON?.message || 'حدث خطأ أثناء إرسال الرسالة';
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

// تحميل معاينة القالب
async function loadTemplatePreview(templateId) {
    try {
        const response = await $.ajax({
            url: window.routes.customerSmsPreview.replace(':customerId', currentCustomerId),
            type: 'POST',
            data: {
                template_id: templateId,
                _token: $('meta[name="csrf-token"]').attr('content')
            }
        });

        if (response.success) {
            $('#template-preview').html(response.preview_content);
            $('#template-preview-container').show();
        } else {
            toastr.error(response.message || 'حدث خطأ في تحميل المعاينة');
            hideTemplatePreview();
        }
    } catch (error) {
        console.error('خطأ في تحميل المعاينة:', error);
        toastr.error('حدث خطأ في تحميل معاينة القالب');
        hideTemplatePreview();
    }
}

// إخفاء معاينة القالب
function hideTemplatePreview() {
    $('#template-preview-container').hide();
    $('#template-preview').html('');
}

// التحقق من اكتمال النموذج
function checkFormCompletion() {
    const phoneNumber = $('#phone_number').val();
    const templateId = $('#template_id').val();

    const isComplete = phoneNumber && templateId;
    $('#sendSmsButton').prop('disabled', !isComplete);
}

// عرض/إخفاء الرسالة كاملة
function toggleFullMessage(smsId) {
    const fullMessageDiv = document.getElementById(`full-message-${smsId}`);
    const button = event.target;

    if (fullMessageDiv.style.display === 'none') {
        fullMessageDiv.style.display = 'block';
        button.textContent = 'إخفاء';
    } else {
        fullMessageDiv.style.display = 'none';
        button.textContent = 'عرض كامل';
    }
}

// حذف رسالة
function deleteSms(smsId) {
    if (!smsId) {
        toastr.error('معرف الرسالة غير صحيح');
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
                url: `/customers/${currentCustomerId}/sms-messages/${smsId}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message || 'تم حذف الرسالة بنجاح');

                        // إزالة الرسالة من القائمة
                        const smsElement = document.getElementById(`sms-${smsId}`);
                        if (smsElement) {
                            // تأثير بصري للحذف
                            smsElement.style.transition = 'all 0.4s ease';
                            smsElement.style.transform = 'scale(0.95)';
                            smsElement.style.opacity = '0.5';

                            setTimeout(() => {
                                smsElement.style.transform = 'translateX(-100%)';
                                setTimeout(() => {
                                    smsElement.remove();
                                    checkAndShowNoSmsMessage();
                                }, 300);
                            }, 200);
                        }
                    } else {
                        toastr.error(response.message || 'حدث خطأ في الحذف');
                    }
                },
                error: function (xhr) {
                    console.error('خطأ في حذف الرسالة:', xhr);
                    let errorMessage = 'حدث خطأ أثناء حذف الرسالة';
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

    $('#customer-sms-modals-container').html(loadingHtml);
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
}

function hideModal(modalId) {
    // تنظيف select2 قبل إخفاء المودال
    const modal = document.getElementById(modalId);
    if (modal) {
        $(modal).find('.select2-phone, .select2-template').select2('destroy');
    }

    const modalInstance = bootstrap.Modal.getInstance(document.getElementById(modalId));
    if (modalInstance) modalInstance.hide();
}

// التحقق من صحة النموذج
function validateForm(form) {
    let isValid = true;
    clearFormValidation(form);

    form.find('[required]').each(function () {
        if (!$(this).val() || $(this).val().trim() === '') {
            $(this).addClass('is-invalid');
            isValid = false;
        }
    });

    return isValid;
}

function clearFormValidation(form) {
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').remove();
}

function displayValidationErrors(form, errors) {
    $.each(errors, function (field, messages) {
        const fieldElement = form.find('[name="' + field + '"]');

        if (fieldElement.length && messages.length > 0) {
            fieldElement.addClass('is-invalid');

            // إزالة رسالة خطأ سابقة
            fieldElement.siblings('.invalid-feedback').remove();

            // إضافة رسالة الخطأ
            fieldElement.after(`<div class="invalid-feedback">${messages[0]}</div>`);
        }
    });
}

// التحقق من وجود رسائل وإظهار رسالة مناسبة
function checkAndShowNoSmsMessage() {
    const savedSms = document.getElementById('saved-sms-messages');
    const smsElements = savedSms.querySelectorAll('.sms-card');

    if (smsElements.length === 0) {
        const noSmsHtml = `
            <div class="card border-0 bg-light" id="no-sms-message">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="ti ti-message-circle text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="text-muted mb-2">لا توجد رسائل نصية</h5>
                    <p class="text-muted mb-4">ابدأ بإرسال رسالة نصية لهذا العميل</p>
                    <button type="button"
                            class="btn btn-primary js-create-sms-btn">
                        <i class="ti ti-plus me-2"></i>إرسال رسالة جديدة
                    </button>
                </div>
            </div>
        `;
        savedSms.innerHTML = noSmsHtml;
    }
}
