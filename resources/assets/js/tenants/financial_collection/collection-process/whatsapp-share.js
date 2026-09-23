'use strict';

/**
 * WhatsApp Share Manager
 * معالج مشاركة الرسائل عبر الواتساب
 */
class WhatsAppShareManager {
    constructor() {
        this.routes = window.routes || {};
        this.csrfToken = window.csrfToken;
        this.currentCustomer = window.currentCustomer;
        this.init();
    }

    /**
     * تهيئة معالج الواتساب
     */
    init() {
        $(document).ready(() => {
            // تخزين معرف العميل الحالي من المتغير العام
            if (window.currentCustomer && window.currentCustomer.id) {
                this.currentCustomerId = window.currentCustomer.id;
            }

            this.initializeSelect2();
            this.bindEventHandlers();
        });
    }

    /**
     * تهيئة Select2 للقوالب
     */
    initializeSelect2() {
        if (!$('#whatsapp_template_id').hasClass('select2-hidden-accessible')) {
            $('#whatsapp_template_id').select2({
                placeholder: 'اختر قالب الرسالة',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#whatsappShareModal')
            });
        }
    }

    /**
     * ربط معالجات الأحداث
     */
    bindEventHandlers() {
        // معالجة تغيير قالب الرسالة
        $(document).on('change', '#whatsapp_template_id', (e) => {
            this.handleTemplateChange($(e.currentTarget).val());
        });

        // فتح الواتساب
        $(document).on('click', '#openWhatsAppBtn', (e) => {
            e.preventDefault();
            this.openWhatsApp();
        });

        // إعادة تعيين المودال عند إغلاقه
        $('#whatsappShareModal').on('hidden.bs.modal', () => {
            this.resetModal();
        });
    }

    /**
     * عرض مودال مشاركة الواتساب
     */
    showWhatsAppShareModal(customerId) {
        // التحقق من وجود معرف العميل
        if (!customerId) {
            toastr.error('معرف العميل مطلوب');
            return;
        }
        this.initializeSelect2();
        const modal = new bootstrap.Modal(document.getElementById('whatsappShareModal'));
        modal.show();
        $('#whatsappShareModal').data('customer-id', customerId);
        this.currentCustomerId = customerId;
        this.resetModal();
    }

    /**
     * معالجة تغيير قالب الرسالة
     */
    handleTemplateChange(templateId) {
        // الحصول على معرف العميل من عدة مصادر للتأكد
        const customerId = $('#whatsappShareModal').data('customer-id') ||
            this.currentCustomerId ||
            (window.currentCustomer ? window.currentCustomer.id : null);
        if (!customerId) {
            toastr.error('معرف العميل غير متوفر');
            return;
        }

        if (templateId && customerId) {
            // عرض رسالة التحميل
            $('#whatsapp_message').val('جاري تحميل الرسالة...');
            $('#openWhatsAppBtn').prop('disabled', true);

            // بناء المسار مع تمرير معرف العميل
            const whatsappPreviewUrl = this.routes.whatsappPreview
                ? this.routes.whatsappPreview.replace(':customerId', customerId)
                : '';

            if (!whatsappPreviewUrl) {
                toastr.error('مسار المعاينة غير متوفر');
                return;
            }

            // إرسال طلب AJAX لمعاينة الرسالة
            $.ajax({
                url: whatsappPreviewUrl,
                type: 'GET',
                data: {
                    template_id: templateId,
                    customer_id: customerId
                },
                success: (response) => {
                    if (response.success && response.preview) {
                        $('#whatsapp_message').val(response.preview);
                        $('#openWhatsAppBtn').prop('disabled', false);
                        $('#whatsappShareModal').data('processed-message', response.preview);

                        // عرض أرقام الهواتف
                        this.displayPhoneOptions(response.phones, response.primary_phone);

                        // toastr.success('تم تحميل القالب بنجاح');
                    } else {
                        $('#whatsapp_message').val('');
                        $('#openWhatsAppBtn').prop('disabled', true);
                        toastr.error(response.message || 'حدث خطأ أثناء معالجة الرسالة');
                    }
                },
                error: (xhr) => {
                    $('#whatsapp_message').val('');
                    $('#openWhatsAppBtn').prop('disabled', true);

                    let errorMessage = 'حدث خطأ أثناء معالجة الرسالة';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    toastr.error(errorMessage);
                }
            });
        } else {
            $('#whatsapp_message').val('');
            $('#openWhatsAppBtn').prop('disabled', true);
            $('#whatsappShareModal').removeData('processed-message');

            // إعادة تعيين خيارات الهاتف
            $('#phone-options').empty().addClass('d-none');
            $('#no-phones-message').removeClass('d-none');
        }
    }

    /**
     * عرض خيارات أرقام الهواتف
     */
    displayPhoneOptions(phones, primaryPhone) {
        const phoneOptions = $('#phone-options');
        const noPhonesMessage = $('#no-phones-message');

        phoneOptions.empty();

        // إنشاء قائمة أرقام الهواتف بشكل أفقي
        let phonesHtml = '<div class="d-flex flex-wrap gap-2">';

        // إضافة الهاتف الأساسي إذا كان موجودًا
        if (primaryPhone) {
            phonesHtml += `
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="selected_phone" id="phone_primary" value="${primaryPhone}" checked>
                    <label class="form-check-label d-flex align-items-center gap-1" for="phone_primary">
                        <span class="small">${primaryPhone}</span>
                        <span class="badge bg-primary rounded-pill" style="font-size: 0.65rem;">أساسي</span>
                    </label>
                </div>
            `;
        }

        // إضافة بقية أرقام الهواتف
        if (phones && phones.length > 0) {
            phones.forEach((phone, index) => {
                const isPrimary = phone.is_primary;
                const badgeClass = isPrimary ? 'bg-warning' : 'bg-info';
                const badgeText = isPrimary ? 'افتراضي' : 'إضافي';

                phonesHtml += `
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="selected_phone" id="phone_${index}" value="${phone.phone}" ${!primaryPhone && index === 0 ? 'checked' : ''}>
                        <label class="form-check-label d-flex align-items-center gap-1" for="phone_${index}">
                            <span class="small">${phone.phone}</span>
                            <span class="badge ${badgeClass} rounded-pill" style="font-size: 0.65rem;">${badgeText}</span>
                        </label>
                    </div>
                `;
            });
        }

        phonesHtml += '</div>';

        if (primaryPhone || (phones && phones.length > 0)) {
            phoneOptions.html(phonesHtml);
            phoneOptions.removeClass('d-none');
            noPhonesMessage.addClass('d-none');
        } else {
            phoneOptions.addClass('d-none');
            noPhonesMessage.removeClass('d-none');
        }
    }

    openWhatsApp() {
        const customerId = $('#whatsappShareModal').data('customer-id') ||
            this.currentCustomerId ||
            (window.currentCustomer ? window.currentCustomer.id : null);

        const message = $('#whatsappShareModal').data('processed-message');
        const selectedPhone = $('input[name="selected_phone"]:checked').val();

        if (!customerId) {
            toastr.error('معرف العميل غير متوفر');
            return;
        }

        if (!message) {
            toastr.error('الرجاء اختيار قالب رسالة أولاً');
            return;
        }

        if (!selectedPhone) {
            toastr.error('الرجاء اختيار رقم هاتف');
            return;
        }

        const btn = $('#openWhatsAppBtn');
        const originalText = btn.html();

        btn.html('<span class="spinner-border spinner-border-sm me-1"></span> جاري الفتح...');
        btn.prop('disabled', true);

        const formattedPhone = this.formatSaudiPhoneNumberForWhatsApp(selectedPhone);
        const whatsappUrl = `https://wa.me/${formattedPhone}?text=${encodeURIComponent(message)}`;

        window.open(whatsappUrl, '_blank');
        setTimeout(() => {
            const modalElement = document.getElementById('whatsappShareModal');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
            // toastr.success('تم فتح الواتساب بنجاح');
        }, 500);

        btn.html(originalText);
        btn.prop('disabled', false);
    }

    /**
     * تنسيق رقم الهاتف السعودي للواتساب
     */
    formatSaudiPhoneNumberForWhatsApp(phone) {
        phone = phone.replace(/[\s\-()]/g, '');
        if (phone.startsWith('05') && phone.length === 10) {
            return '966' + phone.substring(1);
        }
        else if (phone.startsWith('5') && phone.length === 9) {
            return '966' + phone;
        }
        else if (phone.startsWith('+966')) {
            return phone.substring(1);
        }
        else if (phone.startsWith('966')) {
            return phone;
        }

        return phone;
    }

    /**
     * إعادة تعيين المودال
     */
    resetModal() {
        $('#whatsapp_template_id').val('').trigger('change');
        $('#whatsapp_message').val('');
        $('#whatsappShareModal').removeData('customer-id');
        $('#whatsappShareModal').removeData('processed-message');
        $('#openWhatsAppBtn').prop('disabled', true);
        $('#phone-options').empty().addClass('d-none');
        $('#no-phones-message').removeClass('d-none');
    }
}

// تهيئة معالج الواتساب عند تحميل الصفحة
$(document).ready(function () {
    if (window.routes && window.csrfToken) {
        window.WhatsAppShareManager = new WhatsAppShareManager();

        // تعريف الدالة العامة لعرض المودال
        window.showWhatsAppShareModal = function (customerId) {
            window.WhatsAppShareManager.showWhatsAppShareModal(customerId);
        };
    }
});