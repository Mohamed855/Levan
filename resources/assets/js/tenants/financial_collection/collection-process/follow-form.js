'use strict';

class FollowFormManager {
    constructor() {
        this.currentCustomer = window.currentCustomer;
        this.formData = window.formData;
        this.customerRemainingDebt = 0;
        this.isSubmitting = false;

        this.init();
    }

    /*
    |--------------------------------------------------------------------------
    | تهيئة النموذج
    |--------------------------------------------------------------------------
    */
    init() {
        $(document).ready(() => {
            this.waitForSelect2().then(() => {
                this.initializeSelect2();
                this.bindEventHandlers();
                this.loadCustomerDebt();
            });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | انتظار تحميل Select2
    |--------------------------------------------------------------------------
    */
    waitForSelect2() {
        return new Promise((resolve) => {
            if (typeof $.fn.select2 !== 'undefined') {
                resolve();
            } else {
                const checkSelect2 = setInterval(() => {
                    if (typeof $.fn.select2 !== 'undefined') {
                        clearInterval(checkSelect2);
                        resolve();
                    }
                }, 100);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | تهيئة Select2
    |--------------------------------------------------------------------------
    */
    initializeSelect2() {
        try {
            $('.select2').each(function () {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        placeholder: 'اختر...',
                        allowClear: true,
                        width: '100%',
                        dir: 'rtl',
                        language: 'ar'
                    });
                }
            });
        } catch (error) {
            console.error('Select2 initialization error:', error);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تهيئة Select2 للحقول الديناميكية
    |--------------------------------------------------------------------------
    */
    initializeDynamicSelect2(container) {
        try {
            container.find('select').each(function () {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        placeholder: 'اختر...',
                        allowClear: true,
                        width: '100%',
                        dir: 'rtl',
                        language: 'ar'
                    });
                }
            });
        } catch (error) {
            console.error('Dynamic Select2 initialization error:', error);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ربط معالجات الأحداث
    |--------------------------------------------------------------------------
    */
    bindEventHandlers() {
        $(document).on('change', '#contact_result_id', (e) => {
            this.handleContactResultChange($(e.target).val());
        });

        $(document).on('change', '#contacted_person', (e) => {
            this.handleContactedPersonChange($(e.target).val());
        });

        $(document).on('click', '#save-follow', () => {
            this.submitForm();
        });

        $(document).on('input blur', '#followForm input, #followForm textarea', (e) => {
            this.clearFieldValidation($(e.target));
        });

        $(document).on('select2:select select2:clear change', '#followForm select', (e) => {
            this.clearFieldValidation($(e.target));
        });
    }

    /*
    |--------------------------------------------------------------------------
    | معالجة تغيير نتيجة الاتصال
    |--------------------------------------------------------------------------
    */
    handleContactResultChange(contactResultId) {
        const fieldsContainer = $('#contact_result_fields');
        const dynamicContainer = $('#dynamic_fields_container');

        if (!contactResultId) {
            fieldsContainer.hide();
            dynamicContainer.empty();
            return;
        }

        fieldsContainer.show();
        this.loadContactResultFields(contactResultId);
    }

    /*
    |--------------------------------------------------------------------------
    | معالجة تغيير الشخص المتصل به
    |--------------------------------------------------------------------------
    */
    handleContactedPersonChange(value) {
        const nameField = $('#contacted_person_name_field');
        const nameInput = $('#contacted_person_name');

        if (value === 'relative') {
            nameField.show();
            nameInput.attr('required', true);
        } else {
            nameField.hide();
            nameInput.removeAttr('required').val('');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تحميل حقول نتيجة الاتصال
    |--------------------------------------------------------------------------
    */
    loadContactResultFields(contactResultId) {
        const container = $('#dynamic_fields_container');

        container.html(`
            <div class="text-center py-3">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <p class="text-muted mb-0 mt-2">جاري التحميل...</p>
            </div>
        `);

        const url = window.routes.contactResultFields.replace('CUSTOMER_ID', this.currentCustomer.id);

        $.ajax({
            url: url,
            method: 'GET',
            data: { contact_result_id: contactResultId },
            headers: { 'X-CSRF-TOKEN': window.csrfToken },
            success: (response) => {
                if (response.success && response.data && response.data.length > 0) {
                    this.renderDynamicFields(response.data, container);
                } else {
                    container.html('<p class="text-muted mb-0">لا توجد حقول إضافية</p>');
                }
            },
            error: (xhr) => {
                console.error('Error loading fields:', xhr);
                container.html('<p class="text-danger mb-0">حدث خطأ في التحميل</p>');
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | عرض الحقول الديناميكية
    |--------------------------------------------------------------------------
    */
    renderDynamicFields(fields, container) {
        if (!fields || fields.length === 0) {
            container.html('<p class="text-muted mb-0">لا توجد حقول إضافية</p>');
            return;
        }

        let html = '<div class="row g-4">';

        fields.forEach(field => {
            const fieldId = field.name;
            const colSize = (field.type === 'textarea' || field.type === 'file') ? 'col-12' : 'col-md-6';

            if (field.type === 'hidden') {
                html += `<input type="hidden" name="${field.name}" id="${fieldId}" value="${field.default_value || ''}">`;
                return;
            }

            html += `<div class="${colSize}" data-field-name="${field.name}" ${field.show_if ? 'style="display: none;"' : ''}>`;

            html += `<label class="form-label">${field.label}`;
            if (field.required) html += ' <span class="text-danger">*</span>';
            html += '</label>';

            switch (field.type) {
                case 'number':
                    html += `<input type="number" name="${field.name}"   ${field.readonly ? 'readonly' : ''}  id="${fieldId}" class="form-control"
                     placeholder="${field.placeholder || ''}"
                     ${field.required ? 'required' : ''}
                     ${field.auto_fill_debt ? 'data-auto-fill="debt"' : ''}
                     step="0.01" min="0">`;
                    break;

                case 'date':
                    html += `<input type="date" name="${field.name}" id="${fieldId}" class="form-control"
                     ${field.required ? 'required' : ''}>`;
                    break;

                case 'datetime-local':
                    html += `<input type="datetime-local" name="${field.name}" id="${fieldId}" class="form-control"
                     ${field.required ? 'required' : ''}>`;
                    break;

                case 'select':
                    const placeholderText = `اختر ${field.label}`;
                    html += `<select name="${field.name}" id="${fieldId}"
                     class="form-select dynamic-select2"
                     ${field.required ? 'required' : ''}
                     data-placeholder="${placeholderText}">`;
                    html += `<option value="">${placeholderText}</option>`;
                    if (field.options) {
                        Object.entries(field.options).forEach(([key, value]) => {
                            html += `<option value="${key}">${value}</option>`;
                        });
                    }
                    html += '</select>';
                    break;

                case 'file':
                    html += `<input type="file" name="${field.name}" id="${fieldId}" class="form-control"
                     accept=".pdf,.jpg,.jpeg,.png" ${field.required ? 'required' : ''}>`;
                    html += '<small class="text-muted d-block mt-1">PDF, JPG, PNG - حتى 10MB</small>';
                    break;

                case 'textarea':
                    html += `<textarea name="${field.name}" id="${fieldId}" class="form-control" rows="3"
                     placeholder="${field.placeholder || ''}"
                     ${field.required ? 'required' : ''}></textarea>`;
                    break;

                default:
                    html += `<input type="text" name="${field.name}" id="${fieldId}" class="form-control"
                     placeholder="${field.placeholder || ''}"
                     ${field.required ? 'required' : ''}
                     maxlength="255">`;
            }

            html += '<div class="invalid-feedback"></div>';
            html += '</div>';
        });

        html += '</div>';
        container.html(html);

        this.initializeDynamicSelect2(container);
        this.autoFillDebtAmount();

        fields.forEach(field => {
            if (field.show_if) {
                this.setupConditionalField(field);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ملء المديونية تلقائياً
    |--------------------------------------------------------------------------
    */
    autoFillDebtAmount() {
        const debtFields = $('input[data-auto-fill="debt"]');
        if (debtFields.length && this.customerRemainingDebt > 0) {
            debtFields.val(this.customerRemainingDebt.toFixed(2));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | إعداد الحقول الشرطية
    |--------------------------------------------------------------------------
    */
    setupConditionalField(field) {
        const triggerFieldId = field.show_if.field;
        const triggerValue = field.show_if.value;
        const targetField = $(`[data-field-name="${field.name}"]`);

        $(document).on('change select2:select', `#${triggerFieldId}`, function () {
            if ($(this).val() === triggerValue) {
                targetField.show();
                targetField.find('input, select, textarea').attr('required', field.required);
            } else {
                targetField.hide();
                targetField.find('input, select, textarea').removeAttr('required').val('');
                if (targetField.find('select').hasClass('select2-hidden-accessible')) {
                    targetField.find('select').val(null).trigger('change');
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | تحميل مديونية العميل
    |--------------------------------------------------------------------------
    */
    async loadCustomerDebt() {
        if (!this.currentCustomer?.id) return;

        try {
            const url = window.routes.getCustomerDebt.replace('CUSTOMER_ID', this.currentCustomer.id);
            const response = await $.ajax({
                url: url,
                method: 'GET',
                headers: { 'X-CSRF-TOKEN': window.csrfToken }
            });

            if (response.success) {
                this.customerRemainingDebt = response.remaining_debt || 0;
            }
        } catch (error) {
            console.error('Error loading customer debt:', error);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | إرسال النموذج
    |--------------------------------------------------------------------------
    */
    submitForm() {
        if (this.isSubmitting) return;

        const form = $('#followForm');
        if (!this.validateForm(form)) {
            return;
        }

        const contactResultId = $('#contact_result_id').val();
        const paymentReceiptElement = $('#payment_receipt');
        const partialPaymentReceiptElement = $('#partial_payment_receipt');

        const hasPaymentReceipt = paymentReceiptElement.length > 0 && paymentReceiptElement[0].files.length > 0;
        const hasPartialPaymentReceipt = partialPaymentReceiptElement.length > 0 && partialPaymentReceiptElement[0].files.length > 0;

        if ((contactResultId == '1' && !hasPaymentReceipt) ||
            (contactResultId == '2' && !hasPartialPaymentReceipt)) {

            Swal.fire({
                title: '<span style="font-size:1.1rem;">هل تريد حفظ المتابعة دون إيصال؟</span>',
                text: "لم يتم رفع إيصال السداد",
                icon: 'warning',
                showCancelButton: true,
                buttonsStyling: false,
                customClass: {
                    popup: 'custom-popup',
                    title: 'custom-title',
                    text: 'custom-text',
                    confirmButton: 'btn btn-success custom-confirm me-2',
                    cancelButton: 'btn btn-danger custom-cancel'
                },
                confirmButtonText: 'تأكيد الحفظ',
                cancelButtonText: 'إلغاء',
                reverseButtons: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    this.checkForRelatedAccounts(contactResultId, form);
                }
            });
        } else {
            this.checkForRelatedAccounts(contactResultId, form);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | التحقق من الحسابات المرتبطة
    |--------------------------------------------------------------------------
    */
    async checkForRelatedAccounts(contactResultId, form) {
        const applicableResults = [3, 4, 5, 6, 7, 8];

        if (!applicableResults.includes(parseInt(contactResultId))) {
            this.proceedWithSave([this.currentCustomer.id]);
            return;
        }

        if (window.FollowAccountsModalManager) {
            const formData = new FormData(form[0]);

            window.FollowAccountsModalManager.show(
                this.currentCustomer.id,
                formData,
                (selectedAccountIds) => {
                    this.proceedWithSave(selectedAccountIds);
                }
            );
        } else {
            console.error('FollowAccountsModalManager not initialized');
            this.proceedWithSave([this.currentCustomer.id]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تنفيذ عملية الحفظ
    |--------------------------------------------------------------------------
    */
    proceedWithSave(selectedAccountIds = null) {
        if (this.isSubmitting) {
            return;
        }

        this.isSubmitting = true;
        const form = $('#followForm');
        const saveButton = $('#save-follow');
        const spinner = saveButton.find('.spinner-border');

        saveButton.prop('disabled', true);
        spinner.show();

        const formData = new FormData(form[0]);

        // ✅ إضافة معرفات الحسابات المحددة
        if (selectedAccountIds && selectedAccountIds.length > 0) {
            formData.append('selected_account_ids', JSON.stringify(selectedAccountIds));
        }

        const contactedPerson = formData.get('contacted_person');
        if (contactedPerson !== 'relative') {
            formData.delete('contacted_person_name');
        }

        const url = window.routes.storeFollow.replace('CUSTOMER_ID', this.currentCustomer.id);

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                if (response.success) {
                    if (typeof toastr !== 'undefined') {
                        const accountsCount = selectedAccountIds ? selectedAccountIds.length : 1;
                        toastr.success(`تم حفظ المتابعة بنجاح على ${accountsCount} حساب`);
                    }

                    setTimeout(() => {
                        if (response.redirect_url) {
                            window.location.href = response.redirect_url;
                        } else {
                            window.location.reload();
                        }
                    }, 1500);
                }
            },
            error: (xhr) => {
                this.isSubmitting = false;
                saveButton.prop('disabled', false);
                spinner.hide();

                // ✅ معالجة خاصة لخطأ السند المعلق
                if (xhr.status === 400 && xhr.responseJSON?.message) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(xhr.responseJSON.message);
                    }
                    return;
                }

                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    this.displayValidationErrors(form, xhr.responseJSON.errors);
                    if (typeof toastr !== 'undefined') {
                        toastr.error('يرجى تصحيح الأخطاء');
                    }
                } else {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('حدث خطأ في الحفظ');
                    }
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | التحقق من صحة النموذج
    |--------------------------------------------------------------------------
    */
    validateForm(form) {
        this.clearFormValidation(form);
        let isValid = true;

        const requiredFields = ['contact_method_id', 'contact_result_id'];

        requiredFields.forEach(fieldName => {
            const field = form.find(`[name="${fieldName}"]`);
            if (field.length && (!field.val() || field.val().trim() === '')) {
                this.showFieldError(field, 'هذا الحقل مطلوب');
                isValid = false;
            }
        });

        const contactedPerson = form.find('[name="contacted_person"]').val();
        const contactedPersonName = form.find('[name="contacted_person_name"]');

        if (contactedPerson === 'relative') {
            if (!contactedPersonName.val() || contactedPersonName.val().trim() === '') {
                this.showFieldError(contactedPersonName, 'اسم الشخص مطلوب');
                isValid = false;
            }
        }

        form.find('[data-field-name]:visible').each((index, element) => {
            const fieldInput = $(element).find('input[required], select[required], textarea[required]');
            if (fieldInput.length && (!fieldInput.val() || fieldInput.val().trim() === '')) {
                this.showFieldError(fieldInput, 'هذا الحقل مطلوب');
                isValid = false;
            }
        });

        return isValid;
    }

    /*
    |--------------------------------------------------------------------------
    | عرض أخطاء التحقق
    |--------------------------------------------------------------------------
    */
    displayValidationErrors(form, errors) {
        Object.entries(errors).forEach(([field, messages]) => {
            const fieldElement = form.find(`[name="${field}"]`);
            if (fieldElement.length && messages.length > 0) {
                this.showFieldError(fieldElement, messages[0]);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | عرض خطأ في الحقل
    |--------------------------------------------------------------------------
    */
    showFieldError(field, message) {
        field.addClass('is-invalid');
        field.siblings('.invalid-feedback').text(message).show();
    }

    /*
    |--------------------------------------------------------------------------
    | مسح خطأ الحقل
    |--------------------------------------------------------------------------
    */
    clearFieldValidation(field) {
        field.removeClass('is-invalid');
        field.siblings('.invalid-feedback').hide();
    }

    /*
    |--------------------------------------------------------------------------
    | مسح أخطاء النموذج
    |--------------------------------------------------------------------------
    */
    clearFormValidation(form) {
        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').hide();
    }

    /*
    |--------------------------------------------------------------------------
    | مسح النموذج بالكامل
    |--------------------------------------------------------------------------
    */
    clearForm() {
        const form = $('#followForm');
        form[0].reset();
        this.clearFormValidation(form);

        try {
            form.find('select.select2-hidden-accessible').val(null).trigger('change');
        } catch (error) {
            console.error('Error clearing Select2:', error);
        }

        $('#contacted_person_name_field').hide();
        $('#contact_result_fields').hide();
        $('#dynamic_fields_container').empty();

        if (this.currentCustomer?.customer_status_id) {
            $('#customer_status_id').val(this.currentCustomer.customer_status_id).trigger('change');
        }
    }
}

// Initialize
$(document).ready(function () {
    if (window.currentCustomer && typeof $ !== 'undefined') {
        window.FollowFormManager = new FollowFormManager();
    }
});
