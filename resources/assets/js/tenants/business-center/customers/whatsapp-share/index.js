 $(document).ready(function () {
    $(document).on('click', '.js-whatsapp-share-btn', function () {
        showWhatsAppShareModal($(this).data('id'));
    });

    window.showWhatsAppShareModal = function (customerId) {
        if (!customerId) {
            toastr.error('معرف العميل مطلوب');
            return;
        }

        if (!$('#whatsapp_template_id').hasClass('select2-hidden-accessible')) {
            $('#whatsapp_template_id').select2({
                placeholder: 'اختر قالب الرسالة',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#whatsappShareModal')
            });
        }

        const modal = new bootstrap.Modal(document.getElementById('whatsappShareModal'));
        modal.show();

        $('#whatsappShareModal').data('customer-id', customerId);

        $('#whatsapp_template_id').val('').trigger('change');
        $('#whatsapp_message').val('');
        $('#whatsappShareModal').removeData('processed-message');
        $('#openWhatsAppBtn').prop('disabled', true);
        
        $('#phone-options').empty().addClass('d-none');
        $('#no-phones-message').removeClass('d-none');
    };

    $(document).on('change', '#whatsapp_template_id', function () {
        const templateId = $(this).val();
        const customerId = $('#whatsappShareModal').data('customer-id');

        if (!customerId) {
            toastr.error('معرف العميل غير متوفر');
            return;
        }

        if (templateId && customerId) {
            $('#whatsapp_message').val('جاري تحميل الرسالة...');
            $('#openWhatsAppBtn').prop('disabled', true);

            $.ajax({
                url: window.routes['customers.whatsapp-preview-template'],
                type: 'GET',
                data: {
                    template_id: templateId,
                    customer_id: customerId
                },
                success: function (response) {
                    if (response.success && response.preview) {
                        $('#whatsapp_message').val(response.preview);
                        $('#openWhatsAppBtn').prop('disabled', false);
                        $('#whatsappShareModal').data('processed-message', response.preview);
                        
                        displayPhoneOptions(response.phones, response.primary_phone);
                        
                        toastr.success('تم تحميل القالب بنجاح');
                    } else {
                        $('#whatsapp_message').val('');
                        $('#openWhatsAppBtn').prop('disabled', true);
                        toastr.error(response.message || 'حدث خطأ أثناء معالجة الرسالة');
                    }
                },
                error: function (xhr, status, error) {
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
            
            $('#phone-options').empty().addClass('d-none');
            $('#no-phones-message').removeClass('d-none');
        }
    });

    function displayPhoneOptions(phones, primaryPhone) {
        const phoneOptions = $('#phone-options');
        const noPhonesMessage = $('#no-phones-message');
        
        phoneOptions.empty();
        
        let phonesHtml = '<div class="d-flex flex-wrap gap-2">';
        
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
        
        if (phones && phones.length > 0) {
            phones.forEach(function(phone, index) {
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

    $(document).on('click', '#openWhatsAppBtn', function (e) {
        e.preventDefault();

        const customerId = $('#whatsappShareModal').data('customer-id');
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

        const btn = $(this);
        const originalText = btn.html();

        btn.html('<span class="spinner-border spinner-border-sm me-1"></span> جاري الفتح...');
        btn.prop('disabled', true);

        const formattedPhone = formatSaudiPhoneNumberForWhatsApp(selectedPhone);

        const whatsappUrl = `https://wa.me/${formattedPhone}?text=${encodeURIComponent(message)}`;

        window.open(whatsappUrl, '_blank');

        setTimeout(function () {
            bootstrap.Modal.getInstance(document.getElementById('whatsappShareModal')).hide();
            toastr.success('تم فتح الواتساب بنجاح');
        }, 500);

        btn.html(originalText);
        btn.prop('disabled', false);
    });

    function formatSaudiPhoneNumberForWhatsApp(phone) {
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

    $('#whatsappShareModal').on('hidden.bs.modal', function () {
        $('#whatsapp_template_id').val('').trigger('change');
        $('#whatsapp_message').val('');
        $('#whatsappShareModal').removeData('customer-id');
        $('#whatsappShareModal').removeData('processed-message');
        $('#openWhatsAppBtn').prop('disabled', true);
        $('#phone-options').empty().addClass('d-none');
        $('#no-phones-message').removeClass('d-none');
    });
});