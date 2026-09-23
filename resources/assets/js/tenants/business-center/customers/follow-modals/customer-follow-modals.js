'use strict';
let currentCustomerId;
let customerRemainingDebt = 0;

$(document).ready(function () {
    currentCustomerId = window.customerId || $('[data-customer-id]').first().data('customer-id') || $('#follows-table').data('customer-id');

    if (!currentCustomerId) {
        // console.error('Customer ID not found');
        toastr.error('لم يتم العثور على معرف العميل');
        return;
    }

    setupContactResultEvents();

    $(document).on('click', '.js-create-follow-btn', function () {
        showCreateFollowModal();
    });

    $(document).on('click', '.js-edit-follow-btn', function () {
        editCustomerFollow($(this).data('id'));
    });

    $(document).on('click', '.js-delete-follow-btn', function () {
        deleteCustomerFollow($(this).data('id'));
    });
});

function setupContactResultEvents() {
    $(document).on('change', '#contact_result_id', function () {
        const contactResultId = $(this).val();
        const section = $('#contact_result_section');

        if (contactResultId) {
            section.show();
            loadContactResultFields(contactResultId, '#contact_result_fields_container', false);
        } else {
            section.hide();
            $('#contact_result_fields_container').empty();
        }
    });

    $(document).on('change', '#edit_contact_result_id', function () {
        const contactResultId = $(this).val();
        const section = $('#edit_contact_result_section');

        if (contactResultId) {
            section.show();
            loadContactResultFields(contactResultId, '#edit_contact_result_fields_container', true);
        } else {
            section.hide();
            $('#edit_contact_result_fields_container').empty();
        }
    });

    $(document).on('change', '#contacted_person, #edit_contacted_person', function () {
        const value = $(this).val();
        const isEditMode = $(this).attr('id').startsWith('edit_');
        const fieldContainer = isEditMode ?
            $('#contacted_person_name_field') :
            $(this).closest('.row').find('#contacted_person_name_field');
        const nameInput = isEditMode ?
            $('#edit_contacted_person_name') :
            $('#contacted_person_name');

        if (value === 'relative') {
            fieldContainer.show();
            nameInput.attr('required', true);
            nameInput.attr('name', 'contacted_person_name');
        } else {
            fieldContainer.hide();
            nameInput.attr('required', false);
            nameInput.val('');
        }
    });
}


// function formatDateForInput(dateString) {
//     if (!dateString) return '';
//     const date = new Date(dateString);
//     return date.toISOString().split('T')[0];
// }

// function formatDateTimeForInput(dateTimeString) {
//     if (!dateTimeString) return '';
//     const date = new Date(dateTimeString);
//     return date.toISOString().slice(0, 16);
// }

function formatDateForInput(dateString) {
    if (!dateString) return '';
    return dateString.split('T')[0].split(' ')[0];
}

function formatDateTimeForInput(dateTimeString) {
    if (!dateTimeString) return '';
    return dateTimeString.replace(/\s/, 'T').split('.')[0].slice(0, 16);
}


function renderContactResultFields(fields, containerSelector, isEditMode = false) {
    const container = $(containerSelector);
    container.empty();

    if (!fields || fields.length === 0) {
        return;
    }

    const prefix = isEditMode ? 'edit_' : '';
    const fieldsContainer = $('<div class="row g-3"></div>');
    container.append(fieldsContainer);

    fields.forEach((field, index) => {
        const fieldId = prefix + field.name;
        const fieldName = field.name;
        let fieldSize = (field.type === 'textarea' || field.type === 'file') ? 'col-12' : 'col-md-6';

        let fieldHtml = '';

        switch (field.type) {
            case 'hidden':
                fieldHtml = `<input type="hidden" name="${fieldName}" id="${fieldId}" value="${field.default_value || ''}" />`;
                break;
            case 'text':
            case 'number':
                fieldHtml = `
         <div class="${fieldSize}" data-field-name="${field.name}" ${field.show_if ? 'style="display: none;"' : ''}>
             <label class="form-label fw-semibold" for="${fieldId}">${field.label} ${field.required ? '<span class="text-danger">*</span>' : ''}</label>
             <input type="${field.type}" name="${fieldName}" id="${fieldId}" class="form-control" 
                 ${field.required ? 'required' : ''} 
                 ${field.min !== undefined ? `min="${field.min}"` : ''} 
                 placeholder="${field.placeholder || ''}" 
                 ${field.auto_fill_debt ? 'data-auto-fill-debt="true"' : ''} />
             <div class="invalid-feedback"></div>
         </div>
     `;
                break;
            case 'date':
                fieldHtml = `
           <div class="${fieldSize}" data-field-name="${field.name}" ${field.show_if ? 'style="display: none;"' : ''}>
               <label class="form-label fw-semibold" for="${fieldId}">${field.label} ${field.required ? '<span class="text-danger">*</span>' : ''}</label>
               <input type="${field.type}" name="${fieldName}" id="${fieldId}" class="form-control" 
                   ${field.required ? 'required' : ''} 
                   ${field.min !== undefined ? `min="${field.min}"` : ''} 
                   placeholder="${field.placeholder || ''}" />
               <div class="invalid-feedback"></div>
           </div>
       `;
                break;

            case 'datetime-local':
                fieldHtml = `
           <div class="${fieldSize}" data-field-name="${field.name}" ${field.show_if ? 'style="display: none;"' : ''}>
               <label class="form-label fw-semibold" for="${fieldId}">${field.label} ${field.required ? '<span class="text-danger">*</span>' : ''}</label>
               <input type="${field.type}" name="${fieldName}" id="${fieldId}" class="form-control" 
                   ${field.required ? 'required' : ''} 
                   placeholder="${field.placeholder || ''}" />
               <div class="invalid-feedback"></div>
           </div>
       `;
                break;

            case 'textarea':
                fieldHtml = `
           <div class="${fieldSize}" data-field-name="${field.name}" ${field.show_if ? 'style="display: none;"' : ''}>
               <label class="form-label fw-semibold" for="${fieldId}">${field.label} ${field.required ? '<span class="text-danger">*</span>' : ''}</label>
               <textarea name="${fieldName}" id="${fieldId}" class="form-control" rows="3" 
                   ${field.required ? 'required' : ''} 
                   ${field.maxlength ? `maxlength="${field.maxlength}"` : ''} 
                   placeholder="${field.placeholder || ''}"></textarea>
               <div class="invalid-feedback"></div>
           </div>
       `;
                break;

            case 'select':
                let options = '<option value="">اختر...</option>';
                if (field.options) {
                    for (const [value, label] of Object.entries(field.options)) {
                        options += `<option value="${value}">${label}</option>`;
                    }
                }

                fieldHtml = `
           <div class="${fieldSize}" data-field-name="${field.name}" ${field.show_if ? 'style="display: none;"' : ''}>
               <label class="form-label fw-semibold" for="${fieldId}">${field.label} ${field.required ? '<span class="text-danger">*</span>' : ''}</label>
               <select name="${fieldName}" id="${fieldId}" class="form-control select2-modal" 
                   data-placeholder="اختر ${field.label}" ${field.required ? 'required' : ''}>
                   ${options}
               </select>
               <div class="invalid-feedback"></div>
           </div>
       `;
                break;

            case 'file':
                fieldHtml = `
           <div class="${fieldSize}" data-field-name="${field.name}" ${field.show_if ? 'style="display: none;"' : ''}>
               <label class="form-label fw-semibold" for="${fieldId}">${field.label} ${field.required ? '<span class="text-danger">*</span>' : ''}</label>
               <input type="file" name="${fieldName}" id="${fieldId}" class="form-control" 
                   ${field.required ? 'required' : ''} 
                   accept="${field.accept || '*'}" />
               <div class="invalid-feedback"></div>
               <small class="text-muted">الحد الأقصى: 10 ميجابايت</small>
           </div>
       `;
                break;
        }
        fieldsContainer.append(fieldHtml);

        if (field.auto_fill_debt && customerRemainingDebt >= 0) {
            setTimeout(() => {
                $(`#${fieldId}`).val(customerRemainingDebt);
            }, 100);
        }

        if (field.type === 'select') {
            setTimeout(() => {
                $(`#${fieldId}`).select2({
                    placeholder: $(`#${fieldId}`).data('placeholder'),
                    allowClear: true,
                    width: '100%',
                    dropdownParent: container.closest('.modal')
                });
            }, 100);
        }

        if (field.show_if) {
            const triggerField = isEditMode ? `edit_${field.show_if.field}` : field.show_if.field;
            $(document).on('change', `#${triggerField}`, function () {
                if ($(this).val() === field.show_if.value) {
                    $(`[data-field-name="${field.name}"]`).show();
                    $(`#${fieldId}`).attr('required', field.required);
                } else {
                    $(`[data-field-name="${field.name}"]`).hide();
                    $(`#${fieldId}`).attr('required', false);
                    $(`#${fieldId}`).val(''); // إزالة القيمة عند الإخفاء
                    $(`#${fieldId}`).removeClass('is-invalid'); // إزالة حالة الخطأ
                }
            });
        }
    });
}

function getRouteUrl(routeName, params = {}) {
    if (typeof window.routes === 'undefined' || !window.routes[routeName]) {
        // console.error('Route not found:', routeName);
        return '#';
    }

    let url = window.routes[routeName];
    if (params.customerFollow) {
        url = url.replace(/FOLLOW_ID/g, params.customerFollow);
    }

    if (params.customer) {
        url = url.replace(/CUSTOMER_ID/g, params.customer);
    }
    if (!url.startsWith('http') && !url.startsWith('/')) {
        url = '/' + url;
    }

    return url;
}


async function loadCustomerDebt() {
    try {
        // console.log('جاري جلب المديونية للعميل:', currentCustomerId);

        const url = getRouteUrl('customers.follows.get-customer-debt', { customer: currentCustomerId });
        // console.log('URL للمديونية:', url);

        const response = await $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken
            }
        });

        if (response.success) {
            customerRemainingDebt = response.remaining_debt;
            // console.log('المديونية المتبقية:', customerRemainingDebt);
        }
    } catch (error) {
        // console.error('خطأ في جلب المديونية:', error);
    }
}

async function showCreateFollowModal() {
    if (!currentCustomerId) {
        toastr.error('لم يتم العثور على معرف العميل');
        return;
    }

    try {
        const existingModal = document.getElementById('createFollowModal');
        if (existingModal) {
            const modalInstance = bootstrap.Modal.getInstance(existingModal);
            if (modalInstance) modalInstance.dispose();
            existingModal.remove();
        }

        showLoadingModal('createFollowModal', 'إضافة متابعة جديدة');

        await loadCustomerDebt();

        const url = getRouteUrl('customers.follows.create', { customer: currentCustomerId });

        const response = await $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken
            }
        });

        if (response.success && response.html) {
            $('#createFollowModal .modal-content').html(response.html);

            // تطبيق الخصائص لمنع الإغلاق
            $('#createFollowModal').attr('data-bs-backdrop', 'static');
            $('#createFollowModal').attr('data-bs-keyboard', 'false');

            initializeModalComponents('#createFollowModal');
            bindCreateModalEvents();
            setupLiveValidation('#createFollowModal');
        } else {
            toastr.error(response.message || 'حدث خطأ في تحميل النموذج');
            hideModal('createFollowModal');
        }
    } catch (error) {
        let errorMessage = 'حدث خطأ في تحميل النموذج';
        
        if (error.responseJSON && error.responseJSON.message) {
            errorMessage = error.responseJSON.message;
        } else if (error.statusText) {
            errorMessage = error.statusText; 
        }

        toastr.error(errorMessage);
        hideModal('createFollowModal');
    }
}


async function editCustomerFollow(followId) {
    if (!followId) {
        toastr.error('معرف المتابعة غير صحيح');
        return;
    }

    try {
        const existingModal = document.getElementById('editFollowModal');
        if (existingModal) {
            const modalInstance = bootstrap.Modal.getInstance(existingModal);
            if (modalInstance) modalInstance.dispose();
            existingModal.remove();
        }

        showLoadingModal('editFollowModal', 'تعديل المتابعة');

        await loadCustomerDebt();

        const url = getRouteUrl('customers.follows.edit', {
            customer: currentCustomerId,
            customerFollow: followId
        });

        const response = await $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken
            }
        });

        if (response.success && response.html) {
            $('#editFollowModal .modal-content').html(response.html);

            // تطبيق الخصائص لمنع الإغلاق
            $('#editFollowModal').attr('data-bs-backdrop', 'static');
            $('#editFollowModal').attr('data-bs-keyboard', 'false');

            initializeModalComponents('#editFollowModal');
            bindEditModalEvents();
            setupLiveValidation('#editFollowModal');

            const contactResultId = $('#edit_contact_result_id').val();
            if (contactResultId) {
                $('#edit_contact_result_section').show();
                await loadContactResultFields(contactResultId, '#edit_contact_result_fields_container', true);

                setTimeout(() => {
                    populateEditFields();
                }, 800);
            }

            const contactedPerson = $('#edit_contacted_person').val();
            if (contactedPerson === 'relative') {
                $('#contacted_person_name_field').show();
                $('#edit_contacted_person_name').attr('required', true);
            } else {
                $('#contacted_person_name_field').hide();
                $('#edit_contacted_person_name').attr('required', false);
            }
        } else {
            toastr.error('حدث خطأ في تحميل البيانات');
            hideModal('editFollowModal');
        }
    } catch (error) {
        toastr.error('حدث خطأ في تحميل البيانات');
        hideModal('editFollowModal');
    }
}

function populateEditFields() {
    if (!window.currentFollowDetails) return;
    const details = window.currentFollowDetails;

    switch (details.contact_result_id) {
        case 1: // تم السداد بالكامل
            if (details.fullPaymentDetails) {
                // تعبئة جميع الحقول
                $('#edit_payment_amount').val(details.fullPaymentDetails.payment_amount);
                $('#edit_payment_date').val(formatDateForInput(details.fullPaymentDetails.payment_date));
                $('#edit_collection_type').val(details.fullPaymentDetails.collection_type || 'cash');
                $('#edit_payment_type').val(details.fullPaymentDetails.payment_type);

                // تأخير select2 للتأكد من التحميل
                setTimeout(() => {
                    if ($('#edit_payment_type').hasClass('select2-hidden-accessible')) {
                        $('#edit_payment_type').val(details.fullPaymentDetails.payment_type).trigger('change');
                    }
                    $('#edit_payment_method_id').val(details.fullPaymentDetails.payment_method_id).trigger('change');
                }, 500);

                // معالجة المرفق
                if (details.fullPaymentDetails.payment_receipt_path) {
                    $('#edit_payment_receipt').parent().find('.file-preview').remove();
                    $('#edit_payment_receipt').after(`
        <div class="file-preview mt-2">
            <a href="${window.tenantStorageUrl}${details.fullPaymentDetails.payment_receipt_path}"
               target="_blank"
               class="btn btn-sm btn-outline-primary">
                <i class="ti ti-file me-1"></i> عرض الإيصال الحالي
            </a>
            <small class="text-muted d-block mt-1">
                يمكنك رفع ملف جديد لاستبدال الإيصال الحالي
            </small>
        </div>
    `);
                    $('#edit_payment_receipt').removeAttr('required');
                }

            }
            break;

        case 2: // سداد جزئي
            if (details.partialPaymentDetails) {

                $('#edit_partial_payment_amount').val(details.partialPaymentDetails.partial_payment_amount);
                $('#edit_partial_payment_date').val(formatDateForInput(details.partialPaymentDetails.partial_payment_date));
                $('#edit_collection_type').val(details.partialPaymentDetails.collection_type || 'scheduled');
                $('#edit_partial_payment_type').val(details.partialPaymentDetails.partial_payment_type);

                if (details.partialPaymentDetails.next_partial_payment_date) {
                    $('#edit_next_partial_payment_date')
                        .val(formatDateForInput(details.partialPaymentDetails.next_partial_payment_date));
                } else {
                    $('#edit_next_partial_payment_date').val('');
                }

                setTimeout(() => {
                    if ($('#edit_partial_payment_type').hasClass('select2-hidden-accessible')) {
                        $('#edit_partial_payment_type')
                            .val(details.partialPaymentDetails.partial_payment_type)
                            .trigger('change');
                    }

                    $('#edit_payment_method_id')
                        .val(details.partialPaymentDetails.payment_method_id)
                        .trigger('change');
                }, 500);

                if (details.partialPaymentDetails.partial_payment_receipt_path) {
                    $('#edit_partial_payment_receipt').parent().find('.file-preview').remove();
                    $('#edit_partial_payment_receipt').after(`
                <div class="file-preview mt-2">
                    <a href="${window.tenantStorageUrl}${details.partialPaymentDetails.partial_payment_receipt_path}"
                       target="_blank"
                       class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-file me-1"></i> عرض الإيصال الحالي
                    </a>
                    <small class="text-muted d-block mt-1">
                        يمكنك رفع ملف جديد لاستبدال الإيصال الحالي
                    </small>
                </div>
            `);
                    $('#edit_partial_payment_receipt').removeAttr('required');
                }
            }
            break;

        case 3: // وعد بالسداد
            if (details.promisePaymentDetails) {
                $('#edit_promise_amount').val(details.promisePaymentDetails.promise_amount);
                $('#edit_promise_date').val(formatDateForInput(details.promisePaymentDetails.promise_date));
            }
            break;
        case 4: // معاودة الاتصال
            if (details.callbackDetails) {
                $('#edit_callback_date').val(formatDateTimeForInput(details.callbackDetails.callback_date));
                $('#edit_callback_reason').val(details.callbackDetails.callback_reason);
                setTimeout(() => {
                    $('#edit_callback_reason').trigger('change');
                }, 300);

                if (details.callbackDetails.callback_reason === 'other' && details.callbackDetails.callback_reason_other) {
                    setTimeout(() => {
                        $('#edit_callback_reason_other').val(details.callbackDetails.callback_reason_other);
                    }, 600);
                }
            }
            break;
        case 9: // طلب مستندات
            if (details.documentRequestDetails) {
                $('#edit_documents_date').val(formatDateForInput(details.documentRequestDetails.documents_date));
                if (details.documentRequestDetails.documents_reason_id) {
                    setTimeout(() => {
                        $('#edit_documents_reason').val(details.documentRequestDetails.documents_reason_id).trigger('change');
                    }, 300);
                }
            }
            break;
        case 10: // معترض
            if (details.objectionDetails) {
                $('#edit_objection_reason').val(details.objectionDetails.objection_reason);
            }
            break;
        case 11: // حذف مسترد
            if (details.recoveryDetails) {
                $('#edit_recovery_reason').val(details.recoveryDetails.recovery_reason);
                setTimeout(() => {
                    $('#edit_recovery_reason').trigger('change');
                }, 300);

                if (details.recoveryDetails.recovery_reason === 'other' && details.recoveryDetails.recovery_reason_other) {
                    setTimeout(() => {
                        $('#edit_recovery_reason_other').val(details.recoveryDetails.recovery_reason_other);
                    }, 600);
                }

                if (details.recoveryDetails.recovery_document_path) {
                    $('#edit_recovery_document').parent().find('.file-preview').remove();
                    $('#edit_recovery_document').after(`
               <div class="file-preview mt-2">
                   <a href="/storage/${details.recoveryDetails.recovery_document_path}" target="_blank" class="btn btn-sm btn-outline-primary">
                       <i class="ti ti-file me-1"></i> عرض الإيصال الحالي
                   </a>
                   <small class="text-muted d-block mt-1">يمكنك رفع ملف جديد لاستبدال الإيصال الحالي</small>
               </div>
           `);
                    $('#edit_recovery_document').removeAttr('required');
                }
            }
            break;

        case 12: // عدم التواصل مؤقتاً
            if (details.temporaryContactLossDetails) {
                $('#edit_temporary_contact_reason').val(details.temporaryContactLossDetails.temporary_contact_reason);
                setTimeout(() => {
                    $('#edit_temporary_contact_reason').trigger('change');
                }, 300);

                if (details.temporaryContactLossDetails.temporary_contact_reason === 'other' && details.temporaryContactLossDetails.temporary_contact_reason_other) {
                    setTimeout(() => {
                        $('#edit_temporary_contact_reason_other').val(details.temporaryContactLossDetails.temporary_contact_reason_other);
                    }, 600);
                }
            }
            break;

    }
}


function loadContactResultFields(contactResultId, containerSelector, isEditMode = false) {
    return new Promise((resolve, reject) => {
        const container = $(containerSelector);
        container.html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary mb-2"></div>
                <p class="text-muted mb-0">جاري تحميل الحقول...</p>
            </div>
        `);

        const url = getRouteUrl('customers.follows.contact-result-fields', { customer: currentCustomerId });

        $.ajax({
            url: url,
            type: 'GET',
            data: { contact_result_id: contactResultId },
            success: function (response) {
                if (response.success && response.data) {
                    renderContactResultFields(response.data, containerSelector, isEditMode);

                    setTimeout(() => {
                        container.find('.select2-modal').each(function () {
                            if ($(this).hasClass('select2-hidden-accessible')) {
                                $(this).select2('destroy');
                            }
                            $(this).select2({
                                placeholder: $(this).data('placeholder') || 'اختر...',
                                allowClear: true,
                                width: '100%',
                                dropdownParent: container.closest('.modal')
                            });
                        });

                        container.find('[data-show-if]').each(function () {
                            const fieldData = $(this).data('show-if');
                            const triggerField = isEditMode ? `edit_${fieldData.field}` : fieldData.field;
                            $(`#${triggerField}`).trigger('change');
                        });

                        resolve();
                    }, 100);
                } else {
                    container.html('<div class="alert alert-warning mb-0">لا توجد حقول متاحة لهذه النتيجة</div>');
                    resolve();
                }
            },
            error: function (xhr) {
                // console.error('Error loading contact result fields:', xhr);
                container.html('<div class="alert alert-danger mb-0">حدث خطأ أثناء تحميل الحقول</div>');
                reject();
            }
        });
    });
}

function deleteCustomerFollow(followId) {
    if (!followId) {
        toastr.error('معرف المتابعة غير صحيح');
        return;
    }

    Swal.fire({
        title: 'هل أنت متأكد من عملية الحذف؟',
        text: " لا يمكن التراجع عن هذا الإجراء!",
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
            const url = getRouteUrl('customers.follows.destroy', {
                customer: currentCustomerId,
                customerFollow: followId
            });

            $.ajax({
                url: url,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message || 'تم حذف المتابعة بنجاح');
                        refreshFollowsTable();
                    } else {
                        toastr.error(response.message || 'حدث خطأ في الحذف');
                    }
                },
                error: function (xhr) {
                    let errorMessage = 'حدث خطأ أثناء حذف المتابعة';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                }
            });
        }
    });
}

function showLoadingModal(modalId, title) {
    const loadingHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
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

    $('#customer-follow-modals-container').html(loadingHtml);
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();
}


function hideModal(modalId) {
    const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
    if (modal) modal.hide();
}

function initializeModalComponents(modalSelector) {
    if (!$(modalSelector).length) {
        return;
    }

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
}

function setupLiveValidation(modalSelector) {
    $(modalSelector + ' input, ' + modalSelector + ' textarea').on('input blur', function () {
        if ($(this).hasClass('is-invalid')) {
            clearFieldValidation($(this));
        }
    });

    $(modalSelector + ' select').on('change', function () {
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

function clearFieldValidation(field) {
    field.removeClass('is-invalid');
    field.siblings('.invalid-feedback').remove();
}

function validateForm(form) {
    let isValid = true;
    clearFormValidation(form);

    const requiredFields = [
        'contact_method_id',
        'contact_result_id'
    ];

    requiredFields.forEach(fieldName => {
        const field = form.find(`[name="${fieldName}"]`);
        if (field.length && (!field.val() || field.val().trim() === '')) {
            field.addClass('is-invalid');
            isValid = false;
        }
    });

    const contactedPerson = form.find('[name="contacted_person"]').val();
    const contactedPersonName = form.find('[name="contacted_person_name"]');

    if (contactedPerson === 'relative') {
        if (!contactedPersonName.val() || contactedPersonName.val().trim() === '') {
            contactedPersonName.addClass('is-invalid');
            showFieldError(contactedPersonName, 'اسم الشخص المتصل به مطلوب عند اختيار "أحد أقارب العميل"');
            isValid = false;
        }
    }

    // التحقق من الحقول الديناميكية المرئية فقط
    form.find('[data-field-name]:visible').each(function () {
        const fieldInput = $(this).find('input[required], select[required], textarea[required]');
        if (fieldInput.length && (!fieldInput.val() || fieldInput.val().trim() === '')) {
            fieldInput.addClass('is-invalid');
            isValid = false;
        }
    });

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
        const fieldElement = form.find(`[name="${field}"]`);
        if (fieldElement.length && messages.length > 0) {
            showFieldError(fieldElement, messages[0]);
        }
    });
}

function bindCreateModalEvents() {
    // ✅ استخدام الدالة الجديدة من الملف المنفصل
    if (typeof window.bindCreateModalEventsWithAccounts === 'function') {
        console.log('[FollowModals] Using bindCreateModalEventsWithAccounts from external file');
        window.bindCreateModalEventsWithAccounts();
    } else {
        console.warn('[FollowModals] bindCreateModalEventsWithAccounts not found! Using fallback');
        bindCreateModalEventsOld();
    }
}

/**
 * ✅ نسخة احتياطية من الكود القديم (Fallback)
 * تستخدم فقط إذا لم يتم تحميل الملف الجديد
 */
function bindCreateModalEventsOld() {
    $('#saveCustomerFollow').off('click.followModal');

    $('#saveCustomerFollow').on('click.followModal', function () {
        const form = $('#createCustomerFollowForm');
        const button = $(this);
        const spinner = button.find('.spinner-border');

        const contactedPerson = form.find('[name="contacted_person"]').val();
        const contactedPersonNameField = form.find('[name="contacted_person_name"]');

        if (contactedPerson === 'customer') {
            contactedPersonNameField.removeAttr('name');
        } else {
            contactedPersonNameField.attr('name', 'contacted_person_name');
        }

        if (!validateForm(form)) {
            return;
        }

        const contactResultId = $('#contact_result_id').val();
        const paymentReceiptElement = $('#payment_receipt');
        const partialPaymentReceiptElement = $('#partial_payment_receipt');

        const hasPaymentReceipt = paymentReceiptElement.length > 0 && paymentReceiptElement[0].files.length > 0;
        const hasPartialPaymentReceipt = partialPaymentReceiptElement.length > 0 && partialPaymentReceiptElement[0].files.length > 0;

        // التحقق إذا كانت نتيجة الاتصال تتطلب عرض المودال
        if (shouldShowAccountsModal(contactResultId)) {
            showAccountsModalAndSave(form, button, spinner);
        } else if ((contactResultId == '1' && !hasPaymentReceipt) ||
            (contactResultId == '2' && !hasPartialPaymentReceipt)) {

            Swal.fire({
                title: '<span style="font-size:1.1rem;">هل تريد حفظ المتابعة دون إيصال ؟</span>',
                text: "لم يتم رفع إيصال السداد",
                icon: 'warning',
                showCancelButton: true,
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-success custom-confirm',
                    cancelButton: 'btn btn-danger custom-cancel'
                },
                confirmButtonText: 'تأكيد',
                cancelButtonText: 'إلغاء',
            }).then((result) => {
                if (result.isConfirmed) {
                    proceedWithSaveOld(form, button, spinner);
                }
            });
        } else {
            proceedWithSaveOld(form, button, spinner);
        }
    });
}

function proceedWithSaveOld(form, button, spinner) {
    button.prop('disabled', true);
    spinner.show();

    const url = getRouteUrl('customers.follows.store', { customer: currentCustomerId });
    const formData = new FormData(form[0]);

    // إضافة الحساب الحالي فقط
    formData.append('selected_account_ids', JSON.stringify([currentCustomerId]));

    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.success) {
                toastr.success('تم حفظ المتابعة بنجاح');
                hideModal('createFollowModal');
                refreshFollowsTable();
            } else {
                toastr.error('حدث خطأ في الحفظ');
            }
        },
        error: function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                displayValidationErrors(form, xhr.responseJSON.errors);
            } else {
                toastr.error('حدث خطأ أثناء حفظ البيانات');
            }
        },
        complete: function () {
            button.prop('disabled', false);
            spinner.hide();
        }
    });
}

function bindEditModalEvents() {
    $('#updateCustomerFollow').off('click.followModal');
    $('#updateCustomerFollow').on('click.followModal', function () {
        const form = $('#editCustomerFollowForm');
        const button = $(this);
        const spinner = button.find('.spinner-border');
        const followId = form.data('follow-id');

        const contactedPerson = form.find('[name="contacted_person"]').val();
        const contactedPersonNameField = form.find('[name="contacted_person_name"]');

        if (contactedPerson === 'customer') {
            contactedPersonNameField.removeAttr('name');
        } else {
            contactedPersonNameField.attr('name', 'contacted_person_name');
        }

        const contactResultId = $('#edit_contact_result_id').val();

        if (contactResultId == 1 && $('.file-preview').length > 0) {
            $('#edit_payment_receipt').removeAttr('required');
        }
        if (contactResultId == 2 && $('.file-preview').length > 0) {
            $('#edit_partial_payment_receipt').removeAttr('required');
        }
        if (contactResultId == 11 && $('.file-preview').length > 0) {
            $('#edit_recovery_document').removeAttr('required');
        }

        if (!validateForm(form)) {
            return;
        }

        button.prop('disabled', true);
        spinner.show();

        const url = getRouteUrl('customers.follows.update', {
            customer: currentCustomerId,
            customerFollow: followId
        });

        $.ajax({
            url: url,
            type: 'POST',
            data: new FormData(form[0]),
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    toastr.success('تم تحديث المتابعة بنجاح');
                    hideModal('editFollowModal');
                    refreshFollowsTable();
                } else {
                    toastr.error('حدث خطأ في التحديث');
                }
            },
            error: function (xhr) {
                // console.log('Validation errors:', xhr.responseJSON);
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

function handleAjaxError(xhr, defaultMessage = 'حدث خطأ في العملية') {
    // console.error('Ajax Error:', xhr);

    let errorMessage = defaultMessage;

    if (xhr.status === 401) {
        errorMessage = 'انتهت جلستك، يرجى تسجيل الدخول مرة أخرى';
        setTimeout(() => {
            window.location.href = '/login';
        }, 2000);
    } else if (xhr.status === 403) {
        errorMessage = 'ليس لديك صلاحية لتنفيذ هذا الإجراء';
    } else if (xhr.status === 404) {
        errorMessage = 'الصفحة المطلوبة غير موجودة';
    } else if (xhr.status === 422) {
        if (xhr.responseJSON && xhr.responseJSON.errors) {
            const firstError = Object.values(xhr.responseJSON.errors)[0];
            errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
        }
    } else if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMessage = xhr.responseJSON.message;
    }

    toastr.error(errorMessage);
    return errorMessage;
}

function refreshFollowsTable() {
    const url = getRouteUrl('customers.follows.list', { customer: currentCustomerId });
    $.ajax({
        url: url,
        type: 'GET',
        headers: {
            'X-CSRF-TOKEN': window.csrfToken
        },
        success: function (response) {
            if (response.success) {
                $('#saved-follows').html(response.html);

                $('[data-bs-toggle="tooltip"]').tooltip();

                // if (response.count !== undefined) {
                //     console.log('Total follows:', response.count);
                // }
            } else {
                toastr.error(response.message || 'حدث خطأ في تحديث القائمة');
            }
        },
        error: function (xhr) {
            handleAjaxError(xhr, 'حدث خطأ في تحديث قائمة المتابعات');
        }
    });
}

/**
 * التحقق من نتيجة الاتصال المحددة وعرض المودال عند الحاجة
 */
function checkContactResultAndShowModal() {
    const contactResultId = $('#contact_result_id').val();

    // إذا كانت نتيجة الاتصال تتطلب عرض المودال
    // if (shouldShowAccountsModal(contactResultId)) {
    //     console.log('Contact result requires accounts modal:', contactResultId);
    // }
}


/// التعامل مع عرض الملاحظات ونسخها
$(document).on('click', '.follow-note-trigger', function () {
    const followId = $(this).data('follow-id');
    const noteText = $(this).data('note');

    const row = $('#note-row-' + followId);
    const content = $('#note-content-' + followId);

    if (row.hasClass('d-none')) {
        $('.follow-note-row').addClass('d-none'); // إغلاق أي صف مفتوح
        content.text(noteText);
        row.removeClass('d-none');
    } else {
        row.addClass('d-none');
    }
});

$(document).on('click', '.copy-note-btn', function () {
    const followId = $(this).data('follow-id');
    const text = $('#note-content-' + followId).text();

    navigator.clipboard.writeText(text).then(() => {
        toastr.success('تم نسخ الملاحظة');
    });
});
/// التعامل مع عرض الملاحظات ونسخها

window.showCreateFollowModal = showCreateFollowModal;
window.editCustomerFollow = editCustomerFollow;
window.deleteCustomerFollow = deleteCustomerFollow;
window.validateForm = validateForm;
window.displayValidationErrors = displayValidationErrors;
window.hideModal = hideModal;
window.refreshFollowsTable = refreshFollowsTable;
window.getRouteUrl = getRouteUrl;
window.currentCustomerId = currentCustomerId;