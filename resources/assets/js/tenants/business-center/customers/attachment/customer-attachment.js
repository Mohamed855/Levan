'use strict';

let attachmentIndex = 0;
let currentCustomerId;

// تصدير الدوال للنافذة العامة
window.addAttachmentField = addAttachmentField;
window.removeAttachmentField = removeAttachmentField;
window.saveAttachments = saveAttachments;
window.deleteAttachment = deleteAttachment;

$(document).ready(function () {
    currentCustomerId = window.customerId;

    // إضافة حقل افتراضي عند تحميل الصفحة
    if (document.getElementById('attachments-container')) {
        addAttachmentField();
    }

    $(document).on('click', '.js-add-attachment-field-btn', function () {
        addAttachmentField();
    });

    $(document).on('click', '.js-save-attachments-btn', function () {
        saveAttachments();
    });

    $(document).on('click', '.js-remove-attachment-field-btn', function () {
        removeAttachmentField($(this).data('index'));
    });

    $(document).on('click', '.js-delete-attachment-btn', function () {
        deleteAttachment($(this).data('id'));
    });
});

// إضافة حقل مرفق جديد
function addAttachmentField() {
    attachmentIndex++;

    const fieldHtml = `
        <div class="attachment-field border rounded p-3 mb-3" data-index="${attachmentIndex}">
            <div class="row g-4 align-items-center">
                <div class="col-md-12">
                    <label class="form-label">المرفق <span class="text-danger">*</span></label>
                    <input type="file"
                           name="attachments[${attachmentIndex}][file]"
                           class="form-control attachment-file"
                           required
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip,.rar,.txt">
                    <div class="form-text text-danger mt-1">PDF, DOC, XLS, صور, ZIP, TXT (أقصى حجم: 10MB)</div>
                </div>
                <div class="col-md-12 mt-2">
                    <label class="form-label">وصف المرفق</label>
                    <textarea name="attachments[${attachmentIndex}][description]"
                              class="form-control"
                              rows="2"
                              placeholder="وصف اختياري للمرفق..."></textarea>
                </div>
                <div class="col-md-12 d-flex justify-content-end mt-3">
                    <button type="button"
                            class="btn btn-outline-danger btn-sm js-remove-attachment-field-btn"
                            data-index="${attachmentIndex}">
                        <i class="ti ti-trash me-1"></i>
                    </button>
                </div>
            </div>
        </div>
    `;

    const container = document.getElementById('attachments-container');
    if (container) {
        container.insertAdjacentHTML('beforeend', fieldHtml);
        updateSaveButtonVisibility();
    }
}

function getRouteUrl(routeName, params = {}) {
    if (!window.routes || !window.routes[routeName]) {
        console.error('Route not found:', routeName);
        return '#';
    }
    let url = window.routes[routeName];

    if (params.customer) url = url.replace(/CUSTOMER_ID/g, params.customer);
    if (params.customerAttachment) url = url.replace(/ATTACHMENT_ID/g, params.customerAttachment);

    if (!url.startsWith('http') && !url.startsWith('/')) url = '/' + url;
    return url;
}


// حذف حقل مرفق
function removeAttachmentField(index) {
    const field = document.querySelector(`.attachment-field[data-index="${index}"]`);
    if (field) {
        field.remove();
        updateSaveButtonVisibility();

        // إضافة حقل جديد إذا لم يتبق أي حقل
        const remainingFields = document.querySelectorAll('.attachment-field');
        if (remainingFields.length === 0) {
            addAttachmentField();
        }
    }
}

// تحديث ظهور زر الحفظ
function updateSaveButtonVisibility() {
    const hasFields = document.querySelectorAll('.attachment-field').length > 0;
    const saveSection = document.getElementById('save-section');
    if (saveSection) {
        saveSection.style.display = hasFields ? 'block' : 'none';
    }
}

// حفظ المرفقات
function saveAttachments() {
    const form = document.getElementById('customerAttachmentsForm');
    const formData = new FormData(form);
    const saveButton = document.querySelector('.js-save-attachments-btn');
    const spinner = document.getElementById('save-spinner');

    // التحقق من وجود ملفات
    const fileInputs = document.querySelectorAll('.attachment-file');

    let hasFiles = false;

    fileInputs.forEach(function (input) {
        if (input.files && input.files.length > 0) {
            hasFiles = true;
        }
    });

    if (!hasFiles) {
        toastr.error('يجب إضافة ملف واحد على الأقل');
        return;
    }

    // تعطيل الزر وإظهار اللودر
    if (saveButton) {
        saveButton.disabled = true;
    }
    if (spinner) {
        spinner.style.display = 'inline-block';
    }

    $.ajax({
        url: getRouteUrl('customers.attachments.store', { customer: currentCustomerId }),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.success) {
                toastr.success(response.message);

                // مسح النموذج
                clearForm();

                // تحديث قائمة المرفقات بدون إعادة تحميل الصفحة
                refreshAttachmentsList();
            } else {
                toastr.error(response.message || 'حدث خطأ في الحفظ');
            }
        },
        error: function (xhr) {
            console.error('خطأ في حفظ المرفقات:', xhr);
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                // عرض رسائل الخطأ
                let errorMessage = '';
                for (const [field, messages] of Object.entries(xhr.responseJSON.errors)) {
                    errorMessage += messages[0] + '\n';
                }
                toastr.error(errorMessage);
            } else {
                toastr.error('حدث خطأ أثناء حفظ المرفقات');
            }
        },
        complete: function () {
            // إعادة تفعيل الزر وإخفاء اللودر
            if (saveButton) {
                saveButton.disabled = false;
            }
            if (spinner) {
                spinner.style.display = 'none';
            }
        }
    });
}

// مسح النموذج
function clearForm() {
    const container = document.getElementById('attachments-container');
    if (container) {
        container.innerHTML = '';
        attachmentIndex = 0;
        addAttachmentField();
    }
}

// تحديث قائمة المرفقات بعد الإضافة
function refreshAttachmentsList() {
    $.ajax({
       url: getRouteUrl('customers.attachments.list', { customer: currentCustomerId }),
        type: 'GET',
        success: function (response) {
            if (response.success) {
                $('#saved-attachments').html(response.html);
            } else {
                toastr.error('حدث خطأ في تحديث القائمة');
                // في حالة الخطأ، يمكن إعادة تحميل الصفحة كحل احتياطي
                location.reload();
            }
        },
        error: function () {
            toastr.error('حدث خطأ في تحديث القائمة');
            // في حالة الخطأ، يمكن إعادة تحميل الصفحة كحل احتياطي
            location.reload();
        }
    });
}

// حذف مرفق
function deleteAttachment(attachmentId) {
    if (!attachmentId) {
        toastr.error('معرف المرفق غير صحيح');
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
            // تنفيذ الحذف عبر AJAX
            $.ajax({
                url: getRouteUrl('customers.attachments.destroy', { customer: currentCustomerId, customerAttachment: attachmentId }),
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        // إظهار رسالة النجاح
                        toastr.success(response.message || 'تم حذف المرفق بنجاح');

                        // إزالة المرفق من القائمة
                        const attachmentElement = document.getElementById(`attachment-${attachmentId}`);
                        if (attachmentElement) {
                            attachmentElement.style.transition = 'opacity 0.3s';
                            attachmentElement.style.opacity = '0';
                            setTimeout(() => {
                                attachmentElement.remove();
                                checkAndShowNoAttachmentsMessage();
                            }, 300);
                        }
                    } else {
                        toastr.error(response.message || 'حدث خطأ في الحذف');
                    }
                },
                error: function (xhr) {
                    console.error('خطأ في حذف المرفق:', xhr);
                    let errorMessage = 'حدث خطأ أثناء حذف المرفق';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                }
            });
        }
    });
}

// التحقق من وجود مرفقات وإظهار رسالة مناسبة
function checkAndShowNoAttachmentsMessage() {
    const savedAttachments = document.getElementById('saved-attachments');
    const attachmentElements = savedAttachments.querySelectorAll('.border');

    if (attachmentElements.length === 0) {
        const noAttachmentsHtml = `
            <div class="text-center py-4 text-muted" id="no-attachments-message">
                <i class="ti ti-paperclip" style="font-size: 3rem; opacity: 0.3;"></i>
                <div class="mt-2">لا توجد مرفقات لهذا العميل</div>
            </div>
        `;
        savedAttachments.innerHTML = noAttachmentsHtml;
    }
}