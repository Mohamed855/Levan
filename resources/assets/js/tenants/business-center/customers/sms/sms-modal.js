/**
 * Customer SMS Modal Management
 * =============================
 * إدارة مودال الرسائل النصية للعملاء بطريقة محسنة
 */

'use strict';

/*
|--------------------------------------------------------------------------
| Customer SMS Module
|--------------------------------------------------------------------------
| وحدة إدارة الرسائل النصية للعملاء
*/
const CustomerSMS = {
    /*
    |--------------------------------------------------------------------------
    | Modal State Management
    |--------------------------------------------------------------------------
    | إدارة حالة المودال
    */
    state: {
        isLoading: false,
        currentCustomerIds: [],
        modalInstance: null
    },

    /*
    |--------------------------------------------------------------------------
    | Initialization
    |--------------------------------------------------------------------------
    | تهيئة الوحدة
    */
    initialize() {
        // يمكن إضافة تهيئة إضافية هنا إذا لزم الأمر
    },

    /*
    |--------------------------------------------------------------------------
    | Show SMS Modal
    |--------------------------------------------------------------------------
    | عرض مودال الرسائل النصية
    */
    show(customerIds) {
        if (!customerIds || customerIds.length === 0) {
            toastr.warning('يرجى تحديد عميل واحد على الأقل');
            return;
        }

        this.state.currentCustomerIds = customerIds;
        this.showLoadingModal(customerIds);
        this.loadModalData(customerIds);
    },

    /*
    |--------------------------------------------------------------------------
    | Show Loading Modal
    |--------------------------------------------------------------------------
    | عرض مودال التحميل
    */
    showLoadingModal(customerIds) {
        const loadingModalHtml = `
            <div class="modal fade" id="smsModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-white">
                            <h5 class="modal-title text-dark fw-bold mb-0">
                                إرسال رسالة نصية لـ ${customerIds.length} عميل
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4 bg-white text-center">
                            <div class="d-flex flex-column align-items-center" style="min-height: 200px; justify-content: center;">
                                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <h6 class="mb-2">جاري تحضير النموذج...</h6>
                                <p class="text-muted mb-0">يرجى الانتظار لحظات</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // إزالة أي مودال سابق وإضافة الجديد
        $('#smsModal').remove();
        $('body').append(loadingModalHtml);

        // عرض المودال
        this.state.modalInstance = new bootstrap.Modal(document.getElementById('smsModal'));
        this.state.modalInstance.show();
        this.state.isLoading = true;
    },

    /*
    |--------------------------------------------------------------------------
    | Load Modal Data
    |--------------------------------------------------------------------------
    | تحميل بيانات المودال
    */
    loadModalData(customerIds) {
        // تنظيف وتحويل الـ IDs
        const cleanedIds = customerIds.map(id => {
            const numId = parseInt(id);
            return isNaN(numId) ? null : numId.toString();
        }).filter(id => id !== null);

        // للأعداد الكبيرة، نأخذ عينة صغيرة فقط للحصول على القوالب
        const sampleIds = cleanedIds.length > 1000 ? cleanedIds.slice(0, 10) : cleanedIds;

        const formData = new FormData();
        formData.append('_token', window.csrfToken);

        // إرسال عينة صغيرة من الـ IDs
        sampleIds.forEach(id => {
            formData.append('customer_ids[]', id);
        });

        $.ajax({
            url: window.routes.customersSMSForm,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 15000,
            success: (response) => {
                if (response.success) {
                    // تمرير العدد الفعلي بدلاً من عينة البيانات
                    const modifiedData = {
                        ...response.data,
                        total_customers: cleanedIds.length,
                        total_phone_numbers: Math.round(cleanedIds.length * 1.2),
                        estimated_cost: response.data.estimated_cost ?
                            (response.data.estimated_cost / sampleIds.length) * cleanedIds.length : 0
                    };

                    this.replaceModalContent(modifiedData, cleanedIds);
                } else {
                    toastr.error(response.message);
                    this.hide();
                }
            },
            error: (xhr, status, error) => {
                let message = 'حدث خطأ أثناء جلب البيانات';

                if (status === 'timeout') {
                    message = 'انتهت مهلة الاتصال. يرجى المحاولة مرة أخرى.';
                } else if (xhr.status === 413 || xhr.status === 422) {
                    message = 'العدد المحدد كبير جداً. جاري المحاولة بطريقة مبسطة...';
                    this.loadModalDataFallback(cleanedIds);
                    return;
                }

                toastr.error(message);
                this.hide();
            }
        });
    },

    /*
    |--------------------------------------------------------------------------
    | Replace Modal Content
    |--------------------------------------------------------------------------
    | استبدال محتوى المودال
    */
    replaceModalContent(data, customerIds) {
        const templatesOptions = data.templates.map(template =>
            `<option value="${template.id}">${template.name}</option>`
        ).join('');

        // تنظيف الـ IDs مرة أخرى للتأكد
        const cleanedIds = customerIds.map(id => {
            const numId = parseInt(id);
            return isNaN(numId) ? null : numId.toString();
        }).filter(id => id !== null);

        // إنشاء inputs منفصلة لكل customer_id بصيغة مصفوفة
        const customerIdsInputs = cleanedIds.map(id =>
            `<input type="hidden" name="customer_ids[]" value="${id}">`
        ).join('');

        const newContent = `
            <div class="modal-header bg-white">
                <h5 class="modal-title text-dark fw-bold mb-0">
                    إرسال رسالة نصية لـ ${data.total_customers} عميل
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <form id="smsForm">
                    ${customerIdsInputs}
                    ${this.getSMSFormContent(templatesOptions, data)}
                </form>
            </div>
        `;

        $('#smsModal .modal-content').html(newContent);
        this.state.isLoading = false;

        // إعادة تهيئة الأحداث والمكونات
        this.initializeModalEvents();
        this.initializeSelect2InModal();
    },

    /*
    |--------------------------------------------------------------------------
    | Get SMS Form Content
    |--------------------------------------------------------------------------
    | الحصول على محتوى نموذج الرسائل
    */
    getSMSFormContent(templatesOptions, data) {
        return `
            <!-- نوع الرسالة -->
            <div class="mb-4">
                <label class="form-label fw-semibold text-dark mb-3">نوع الرسالة</label>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check message-type-option">
                            <input class="form-check-input" type="radio" name="message_type" id="template_type" value="template" checked>
                            <label class="form-check-label w-100 p-3 border rounded text-center cursor-pointer"
                                   for="template_type"
                                   style="transition: all 0.3s ease; border: 2px solid #e9ecef; background-color: #ffffff; min-height: 80px; display: flex; align-items: center; justify-content: center;">
                                <div>
                                    <div class="fw-semibold text-dark">استخدام قالب</div>
                                    <small class="text-muted">اختر من القوالب المحفوظة</small>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check message-type-option">
                            <input class="form-check-input" type="radio" name="message_type" id="custom_type" value="custom">
                            <label class="form-check-label w-100 p-3 border rounded text-center cursor-pointer"
                                   for="custom_type"
                                   style="transition: all 0.3s ease; border: 2px solid #e9ecef; background-color: #ffffff; min-height: 80px; display: flex; align-items: center; justify-content: center;">
                                <div>
                                    <div class="fw-semibold text-dark">رسالة مخصصة</div>
                                    <small class="text-muted">اكتب رسالة جديدة</small>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- قسم القالب -->
            <div id="template_section" class="mb-4">
                <label for="template_id" class="form-label fw-semibold text-dark">اختر القالب</label>
                <select id="template_id" name="template_id" class="form-select">
                    <option value="">-- اختر القالب --</option>
                    ${templatesOptions}
                </select>
            </div>

            <!-- قسم الرسالة المخصصة -->
            <div id="custom_section" class="mb-4" style="display: none;">
                <!-- المتغيرات المتاحة -->
                <div id="variables_section_custom" class="mb-3">
                    <label class="form-label fw-semibold text-dark mb-3">
                        المتغيرات المتاحة
                        <small class="text-muted">(اضغط على المتغير لإدراجه)</small>
                    </label>
                    <div class="variables-container" style="max-height: 250px; overflow-y: auto; background-color: #ffffff; border: 1px solid #e9ecef; border-radius: 6px; padding: 1rem;">
                        ${this.createVariablesHTML(data.variables)}
                    </div>
                </div>

                <label for="custom_message" class="form-label fw-semibold text-dark">نص الرسالة</label>
                <textarea id="custom_message" name="custom_message" class="form-control" rows="5"
                          placeholder="اكتب رسالتك هنا..." maxlength="1000"></textarea>
                <div class="form-text d-flex justify-content-between">
                    <span class="text-muted">الحد الأقصى 1000 حرف</span>
                    <span id="char_counter" class="text-muted">0 / 1000</span>
                </div>
            </div>

            <!-- معاينة الرسالة -->
            <div id="message_preview" class="mb-4" style="display: none;">
                <label class="form-label fw-semibold text-dark">معاينة الرسالة</label>
                <div class="alert alert-light border">
                    <div id="preview_content" class="mb-2 text-dark" style="white-space: pre-wrap;"></div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">عدد الأحرف: <span id="char_count">0</span></small>
                    </div>
                </div>
            </div>

            <!-- أزرار التحكم -->
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary">إرسال الرسائل</button>
            </div>

            ${this.getModalStyles()}
        `;
    },

    /*
    |--------------------------------------------------------------------------
    | Get Modal Styles
    |--------------------------------------------------------------------------
    | الحصول على أنماط المودال
    */
    getModalStyles() {
        return `
            <style>
            #smsModal .message-type-option .form-check-label:hover {
                border-color: var(--bs-primary) !important;
                background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
            }
            #smsModal .message-type-option .form-check-input:checked + .form-check-label {
                border-color: var(--bs-primary) !important;
                background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
            }
            #smsModal .variable-tag {
                transition: all 0.2s ease;
                font-size: 0.85rem;
                padding: 0.4rem 0.8rem;
                margin: 0.2rem;
                border: 1px solid #dee2e6;
                background-color: #ffffff;
                color: #495057;
                user-select: none;
            }
            #smsModal .variable-tag:hover {
                background-color: var(--bs-primary) !important;
                color: #ffffff !important;
                border-color: var(--bs-primary) !important;
                transform: translateY(-1px);
            }
            #smsModal .variable-category-header {
                padding: 0.75rem;
                background-color: #ffffff;
                border: 1px solid #e9ecef;
                border-radius: 6px;
                user-select: none;
                cursor: pointer;
            }
            #smsModal .variable-category-header:hover {
                background-color: rgba(var(--bs-primary-rgb), 0.05);
            }
            #smsModal .collapse-icon {
                font-size: 1.2rem;
                font-weight: bold;
                color: var(--bs-primary);
                transition: transform 0.2s ease;
            }
            #smsModal .variable-tags {
                padding: 0.75rem;
                background-color: #ffffff;
                border-radius: 4px;
                border: 1px solid #e9ecef;
                margin-top: 0.5rem;
            }
            </style>
        `;
    },

    /*
    |--------------------------------------------------------------------------
    | Create Variables HTML
    |--------------------------------------------------------------------------
    | إنشاء HTML للمتغيرات
    */
    createVariablesHTML(variables) {
        let html = '';

        // فلترة المتغيرات لإظهار العام والعملاء فقط
        const filteredVariables = {
            'عام': variables['عام'] || {},
            'العملاء': variables['العملاء'] || {}
        };

        Object.entries(filteredVariables).forEach(([category, categoryVariables]) => {
            if (Object.keys(categoryVariables).length === 0) return;

            html += `
                <div class="variable-category mb-3">
                    <div class="d-flex align-items-center justify-content-between variable-category-header"
                         data-bs-toggle="collapse" data-bs-target="#category-${category}" aria-expanded="false">
                        <h6 class="mb-0 fw-semibold text-dark">${category}</h6>
                        <span class="collapse-icon">+</span>
                    </div>
                    <div class="collapse" id="category-${category}">
                        <div class="variable-tags">
            `;

            Object.entries(categoryVariables).forEach(([variable, description]) => {
                html += `
                    <span class="badge bg-white border text-dark me-1 mb-1 variable-tag cursor-pointer"
                          data-variable="${variable}"
                          title="${description}">
                        ${description}
                    </span>
                `;
            });

            html += `
                        </div>
                    </div>
                </div>
            `;
        });

        return html;
    },

    /*
    |--------------------------------------------------------------------------
    | Initialize Select2 in Modal
    |--------------------------------------------------------------------------
    | تهيئة Select2 في المودال
    */
    initializeSelect2InModal() {
        $('#template_id').select2({
            placeholder: '-- اختر القالب --',
            allowClear: true,
            dropdownParent: $('#smsModal'),
            language: 'ar',
            dir: 'rtl',
            width: '100%'
        });
    },

    /*
    |--------------------------------------------------------------------------
    | Initialize Modal Events
    |--------------------------------------------------------------------------
    | تهيئة أحداث المودال
    */
    initializeModalEvents() {
        // تبديل نوع الرسالة مع إزالة رسائل الخطأ
        $('input[name="message_type"]').on('change', function () {
            const isTemplate = $(this).val() === 'template';

            // إزالة رسائل الخطأ السابقة
            $('.text-danger').remove();
            $('.is-invalid').removeClass('is-invalid');

            if (isTemplate) {
                $('#custom_section').slideUp(300);
                $('#template_section').slideDown(300);
                $('#custom_message').val('');
            } else {
                $('#template_section').slideUp(300);
                $('#custom_section').slideDown(300);
                $('#template_id').val('').trigger('change');
            }

            $('#message_preview').slideUp(300);

            // تحديث تصميم الاختيار
            $('.message-type-option .form-check-label').removeClass('border-primary bg-light');
            $(this).closest('.message-type-option').find('.form-check-label').addClass('border-primary bg-light');
        });

        // معاينة القالب
        $('#template_id').on('change', function () {
            $('.text-danger').remove();
            $('.is-invalid').removeClass('is-invalid');

            const templateId = $(this).val();
            if (templateId) {
                CustomerSMS.previewTemplateMessage(templateId);
            } else {
                $('#message_preview').slideUp(300);
            }
        });

        // معاينة الرسالة المخصصة مع عداد الأحرف
        $('#custom_message').on('input', function () {
            $(this).removeClass('is-invalid');
            $(this).siblings('.text-danger').remove();

            const message = $(this).val();
            const charCount = message.length;

            $('#char_counter').text(`${charCount} / 1000`);

            if (charCount > 900) {
                $('#char_counter').removeClass('text-muted text-warning').addClass('text-danger');
            } else if (charCount > 700) {
                $('#char_counter').removeClass('text-muted text-danger').addClass('text-warning');
            } else {
                $('#char_counter').removeClass('text-warning text-danger').addClass('text-muted');
            }

            if (message.trim()) {
                $('#preview_content').text(message);
                $('#char_count').text(charCount);
                $('#message_preview').slideDown(300);
            } else {
                $('#message_preview').slideUp(300);
            }
        });

        // إدراج المتغيرات
        $(document).on('click', '.variable-tag', function () {
            const variable = $(this).data('variable');
            const activeTextarea = $('#custom_message');

            if (activeTextarea.length && activeTextarea.is(':visible')) {
                activeTextarea.removeClass('is-invalid');
                activeTextarea.siblings('.text-danger').remove();

                $(this).addClass('bg-primary text-white').removeClass('bg-white text-dark');
                setTimeout(() => {
                    $(this).removeClass('bg-primary text-white').addClass('bg-white text-dark');
                }, 200);

                CustomerSMS.insertAtCursor(activeTextarea[0], variable);
                activeTextarea.trigger('input');
                activeTextarea.focus();
            }
        });

        // تبديل المتغيرات
        $(document).on('click', '.variable-category-header', function () {
            const icon = $(this).find('.collapse-icon');
            const target = $(this).attr('data-bs-target');

            $(target).on('shown.bs.collapse', function () {
                icon.text('-');
            }).on('hidden.bs.collapse', function () {
                icon.text('+');
            });
        });

        // إرسال النموذج مع التحقق المحسن
        $('#smsForm').on('submit', function (e) {
            e.preventDefault();

            if (!CustomerSMS.validateForm()) {
                return;
            }

            CustomerSMS.sendRequest($(this).serialize());
        });

        // تهيئة التصميم الافتراضي
        $('.form-check-input:checked').closest('.message-type-option').find('.form-check-label').addClass('border-primary bg-light');
    },

    /*
    |--------------------------------------------------------------------------
    | Form Validation
    |--------------------------------------------------------------------------
    | التحقق من صحة النموذج
    */
    validateForm() {
        const messageType = $('input[name="message_type"]:checked').val();

        $('.text-danger').remove();
        $('.is-invalid').removeClass('is-invalid');

        if (messageType === 'template') {
            const templateId = $('#template_id').val();
            if (!templateId) {
                this.showFieldError('#template_id', 'يرجى اختيار قالب الرسالة');
                return false;
            }
        } else if (messageType === 'custom') {
            const customMessage = $('#custom_message').val().trim();
            if (!customMessage) {
                this.showFieldError('#custom_message', 'يرجى كتابة نص الرسالة');
                return false;
            }

            if (customMessage.length > 1000) {
                this.showFieldError('#custom_message', 'نص الرسالة يتجاوز الحد الأقصى المسموح (1000 حرف)');
                return false;
            }
        } else {
            toastr.warning('يرجى اختيار نوع الرسالة');
            return false;
        }

        return true;
    },

    /*
    |--------------------------------------------------------------------------
    | Show Field Error
    |--------------------------------------------------------------------------
    | عرض خطأ الحقل
    */
    showFieldError(fieldSelector, message) {
        const field = $(fieldSelector);
        field.addClass('is-invalid');
        field.after(`<div class="text-danger small mt-1">${message}</div>`);
        field.focus();
        toastr.warning(message);
    },

    /*
    |--------------------------------------------------------------------------
    | Preview Template Message
    |--------------------------------------------------------------------------
    | معاينة رسالة القالب
    */
    previewTemplateMessage(templateId) {
        $.ajax({
            url: window.routes.customersPreviewSMS,
            type: 'POST',
            data: {
                template_id: templateId,
                _token: window.csrfToken
            },
            success: function (response) {
                if (response.success) {
                    $('#preview_content').html(CustomerSMS.formatPreviewText(response.preview));
                    $('#char_count').text(response.length);
                    $('#message_preview').slideDown(300);
                }
            },
            error: function () {
                toastr.error('حدث خطأ أثناء معاينة الرسالة');
            }
        });
    },

    /*
    |--------------------------------------------------------------------------
    | Format Preview Text
    |--------------------------------------------------------------------------
    | تنسيق نص المعاينة
    */
    formatPreviewText(text) {
        return text.replace(/\n/g, '<br>');
    },

    /*
    |--------------------------------------------------------------------------
    | Send SMS Request
    |--------------------------------------------------------------------------
    | إرسال طلب الرسائل النصية
    */
    sendRequest(formData) {
        const submitBtn = $('#smsForm button[type="submit"]');
        const originalText = submitBtn.html();

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>جاري الإرسال...');

        $.ajax({
            url: window.routes.customersSendSMS,
            type: 'POST',
            data: formData + '&_token=' + window.csrfToken,
            success: function (response) {
                if (response.success) {
                    CustomerSMS.hide();

                    let successMessage = response.message;
                    if (response.total_batches && response.estimated_time) {
                        successMessage += `<br><small>سيتم الإرسال على ${response.total_batches} دفعة خلال ${response.estimated_time}</small>`;
                    }

                    toastr.success(successMessage);

                    // مسح التحديد بعد الإرسال الناجح
                    if (window.CustomerSelection) {
                        CustomerSelection.clearAll();
                    }
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                let message = 'حدث خطأ أثناء إرسال الرسائل';

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    message = Object.values(errors).flat().join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                toastr.error(message);
            },
            complete: function () {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    },

    /*
    |--------------------------------------------------------------------------
    | Insert At Cursor
    |--------------------------------------------------------------------------
    | إدراج النص في موضع المؤشر
    */
    insertAtCursor(textArea, text) {
        const startPos = textArea.selectionStart;
        const endPos = textArea.selectionEnd;
        const beforeText = textArea.value.substring(0, startPos);
        const afterText = textArea.value.substring(endPos);

        textArea.value = beforeText + text + afterText;
        textArea.selectionStart = textArea.selectionEnd = startPos + text.length;
        textArea.focus();
    },

    /*
    |--------------------------------------------------------------------------
    | Hide Modal
    |--------------------------------------------------------------------------
    | إخفاء المودال
    */
    hide() {
        if (this.state.modalInstance) {
            this.state.modalInstance.hide();
        } else {
            $('#smsModal').modal('hide');
        }

        // تنظيف الحالة
        this.state.isLoading = false;
        this.state.currentCustomerIds = [];
        this.state.modalInstance = null;
    }
};

/*
|--------------------------------------------------------------------------
| Export to Global Scope
|--------------------------------------------------------------------------
| تصدير للنطاق العام
*/
window.CustomerSMS = CustomerSMS;