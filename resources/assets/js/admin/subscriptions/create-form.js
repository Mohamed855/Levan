'use strict';

document.addEventListener('DOMContentLoaded', function () {
    function formatNumber(number) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(number);
    }

    const form = document.getElementById('subscription-form');
    const wizard = document.getElementById('subscription-wizard');
    const btnSubmit = document.querySelector('button[type="submit"]');

    if (!wizard || !form) {
        console.error('Form or Wizard not found!');
        return;
    }

    const plansData = window.plansData || [];
    let selectedPlan = null;
    let validationStepper = null;

    if (typeof Stepper !== 'undefined') {
        validationStepper = new Stepper(wizard, { linear: false });
    }

    const fv = FormValidation.formValidation(form, {
        fields: {
            company_name: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال اسم الشركة' },
                    stringLength: { min: 2, max: 255, message: 'اسم الشركة يجب أن يكون بين 2 و 255 حرفاً' }
                }
            },
            commercial_number: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال رقم السجل التجاري' },
                    stringLength: { min: 10, max: 10, message: 'رقم السجل التجاري يجب أن يكون 10 أرقام' },
                    digits: { message: 'رقم السجل التجاري يجب أن يحتوي على أرقام فقط' }
                }
            },
            name: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال اسم المستخدم' },
                    stringLength: { min: 2, max: 255, message: 'اسم المستخدم يجب أن يكون بين 2 و 255 حرفاً' }
                }
            },
            phone: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال رقم الجوال' },
                    regexp: {
                        regexp: /^05[0-9]{8}$/,
                        message: 'يجب أن يبدأ رقم الجوال بـ 05 ويتكون من 10 أرقام'
                    }
                }
            },
            email: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال البريد الإلكتروني' },
                    emailAddress: { message: 'يرجى إدخال بريد إلكتروني صحيح' }
                }
            },
            password: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال كلمة المرور' },
                    stringLength: { min: 8, message: 'كلمة المرور يجب أن تكون 8 أحرف على الأقل' }
                }
            },
            // 1. إصلاح formEl → form
            password_confirmation: {
                validators: {
                    identical: {
                        compare: function () {
                            return form.querySelector('[name="password"]').value; //
                        },
                        message: 'كلمة المرور وتأكيدها غير متطابقتين'
                    }
                }
            },
            plan_id: {
                validators: {
                    notEmpty: { message: 'يرجى اختيار الباقة' }
                }
            },
            payment_type: {
                validators: {
                    notEmpty: { message: 'يرجى اختيار حالة الدفع' }
                }
            },
            payment_method: {
                validators: {
                    callback: {
                        message: 'يرجى اختيار وسيلة الدفع',
                        callback: function (input) {
                            const paymentType = document.getElementById('payment-status').value;
                            if (paymentType === 'paid') {
                                return input.value !== '';
                            }
                            return true;
                        }
                    }
                }
            },
            receipt_upload: {
                validators: {
                    callback: {
                        message: 'يرجى رفع إيصال التحويل البنكي',
                        callback: function (input) {
                            const paymentMethod = document.getElementById('payment-method-select').value;
                            const paymentType = document.getElementById('payment-status').value;

                            if (paymentType === 'paid' && paymentMethod === 'bank_transfer') {
                                return input.files && input.files.length > 0;
                            }
                            return true;
                        }
                    },
                    file: {
                        extension: 'jpeg,jpg,png,pdf',
                        type: 'image/jpeg,image/png,application/pdf',
                        maxSize: 2097152,
                        message: 'يرجى رفع ملف صالح (صورة أو PDF) بحجم أقصى 2MB'
                    }
                }
            },
            has_discount: {
                validators: {
                    callback: {
                        message: 'يرجى اختيار خيار التسوية',
                        callback: function (input) {
                            const paymentType = document.getElementById('payment-status').value;
                            if (paymentType !== 'trial') {
                                return input.value !== '';
                            }
                            return true;
                        }
                    }
                }
            },
            discount_value: {
                validators: {
                    callback: {
                        message: 'يرجى إدخال قيمة التسوية',
                        callback: function (input) {
                            const hasDiscount = document.getElementById('discount-type').value;
                            const paymentType = document.getElementById('payment-status').value;

                            if (paymentType !== 'trial' && hasDiscount === 'yes') {
                                return input.value && parseFloat(input.value) >= 0;
                            }
                            return true;
                        }
                    }
                }
            },
            trial_end_date: {
                validators: {
                    callback: {
                        message: 'يرجى اختيار تاريخ نهاية التجربة',
                        callback: function (input) {
                            const paymentType = document.getElementById('payment-status').value;
                            if (paymentType === 'trial') {
                                return input.value !== '';
                            }
                            return true;
                        }
                    }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: '',
                rowSelector: '.col-12, .col-md-6, .col-md-4, .mb-4'
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
            submitButton: new FormValidation.plugins.SubmitButton()
        }
    });

    if (window.jQuery) {
        const $ = window.jQuery;

        $('.select2').each(function () {
            const $this = $(this);
            $this.wrap('<div class="position-relative"></div>');
            $this.select2({
                placeholder: $this.data('placeholder') || 'اختر خيارًا',
                dropdownParent: $this.parent(),
                allowClear: true,
                width: '100%',
                language: 'ar',
                dir: 'rtl'
            }).on('change', function () {
                const fieldName = $this.attr('name');
                fv.revalidateField(fieldName);
                handleFieldChange(fieldName, $this.val());
            });
        });
    }

    function handleFieldChange(fieldName, value) {
        if (fieldName === 'plan_id' && value) {
            selectedPlan = plansData.find(p => p.id == value);
            calculatePrices();
        } else if (fieldName === 'payment_type') {
            handlePaymentTypeChange(value);
        } else if (fieldName === 'payment_method') {
            handlePaymentMethodChange(value);
        } else if (fieldName === 'has_discount') {
            handleDiscountTypeChange(value);
        }
    }

    function handlePaymentTypeChange(value) {
        const paymentMethodSelect = document.getElementById('payment-method-select');
        const paymentMethodContainer = paymentMethodSelect.closest('.col-md-6');

        const receiptUploadWrapper = document.getElementById('receipt-upload-wrapper');
        const receiptUpload = document.getElementById('receipt-upload');

        const discountContainer = document.getElementById('discount-type-wrapper');
        const discountSelect = document.getElementById('discount-type');

        const discountValueWrapper = document.getElementById('discount-value-wrapper');
        const discountValueInput = document.getElementById('discount-value-input');

        const trialEndDateWrapper = document.getElementById('trial-end-date-wrapper');
        const trialEndDateInput = document.getElementById('trial-end-date');

        if (value === 'paid') {
            paymentMethodContainer.style.display = 'block';
            discountContainer.style.display = 'block';
            trialEndDateWrapper.style.display = 'none';

            paymentMethodSelect.required = true;
            discountSelect.required = true;
            trialEndDateInput.required = false;

            fv.enableValidator('payment_method');
            fv.enableValidator('has_discount');
            fv.disableValidator('trial_end_date');

            const selectedMethod = paymentMethodSelect.value;
            if (selectedMethod === 'bank_transfer') {
                receiptUploadWrapper.style.display = 'block';
                receiptUpload.required = true;
                fv.enableValidator('receipt_upload');
            } else {
                receiptUploadWrapper.style.display = 'none';
                receiptUpload.required = false;
                fv.disableValidator('receipt_upload');
            }

            handleDiscountTypeChange(discountSelect.value);
        } else if (value === 'unpaid') {
            paymentMethodContainer.style.display = 'none';
            receiptUploadWrapper.style.display = 'none';
            trialEndDateWrapper.style.display = 'none';
            discountContainer.style.display = 'block';

            paymentMethodSelect.required = false;
            receiptUpload.required = false;
            trialEndDateInput.required = false;
            discountSelect.required = true;

            fv.disableValidator('payment_method');
            fv.disableValidator('receipt_upload');
            fv.disableValidator('trial_end_date');
            fv.enableValidator('has_discount');

            if (window.jQuery) {
                window.jQuery(paymentMethodSelect).val('').trigger('change');
            } else {
                paymentMethodSelect.value = '';
            }

            receiptUpload.value = '';
            handleDiscountTypeChange(discountSelect.value);
        } else if (value === 'trial') {
            paymentMethodContainer.style.display = 'none';
            receiptUploadWrapper.style.display = 'none';
            discountContainer.style.display = 'none';
            discountValueWrapper.style.display = 'none';
            trialEndDateWrapper.style.display = 'block';

            paymentMethodSelect.required = false;
            receiptUpload.required = false;
            discountSelect.required = false;
            discountValueInput.required = false;
            trialEndDateInput.required = true;

            fv.disableValidator('payment_method');
            fv.disableValidator('receipt_upload');
            fv.disableValidator('has_discount');
            fv.disableValidator('discount_value');
            fv.enableValidator('trial_end_date');

            if (window.jQuery) {
                window.jQuery(paymentMethodSelect).val('').trigger('change');
                window.jQuery(discountSelect).val('').trigger('change');
            } else {
                paymentMethodSelect.value = '';
                discountSelect.value = '';
            }

            receiptUpload.value = '';
            discountValueInput.value = '0';
            calculatePrices();
            fv.revalidateField('trial_end_date');
        } else {
            paymentMethodContainer.style.display = 'none';
            receiptUploadWrapper.style.display = 'none';
            discountContainer.style.display = 'none';
            discountValueWrapper.style.display = 'none';
            trialEndDateWrapper.style.display = 'none';

            paymentMethodSelect.required = false;
            receiptUpload.required = false;
            discountSelect.required = false;
            discountValueInput.required = false;
            trialEndDateInput.required = false;

            fv.disableValidator('payment_method');
            fv.disableValidator('receipt_upload');
            fv.disableValidator('has_discount');
            fv.disableValidator('discount_value');
            fv.disableValidator('trial_end_date');
        }
    }

    function handlePaymentMethodChange(value) {
        const receiptUploadWrapper = document.getElementById('receipt-upload-wrapper');
        const receiptUpload = document.getElementById('receipt-upload');
        const paymentType = document.getElementById('payment-status').value;

        if (paymentType === 'paid' && value === 'bank_transfer') {
            receiptUploadWrapper.style.display = 'block';
            receiptUpload.required = true;
            fv.enableValidator('receipt_upload');
            fv.revalidateField('receipt_upload');
        } else {
            receiptUploadWrapper.style.display = 'none';
            receiptUpload.required = false;
            fv.disableValidator('receipt_upload');
            receiptUpload.value = '';
        }
    }

    function handleDiscountTypeChange(value) {
        const paymentType = document.getElementById('payment-status').value;
        const discountValueWrapper = document.getElementById('discount-value-wrapper');
        const discountValueInput = document.getElementById('discount-value-input');

        if (paymentType === 'trial') {
            discountValueWrapper.style.display = 'none';
            discountValueInput.required = false;
            discountValueInput.value = '0';
            fv.disableValidator('discount_value');
            calculatePrices();
            return;
        }

        if (value === 'yes') {
            discountValueWrapper.style.display = 'block';
            discountValueInput.required = true;
            fv.enableValidator('discount_value');
            setTimeout(() => {
                discountValueInput.focus();
            }, 300);
        } else {
            discountValueWrapper.style.display = 'none';
            discountValueInput.required = false;
            discountValueInput.value = '0';
            fv.disableValidator('discount_value');
            calculatePrices();
        }
    }

    function calculatePrices() {
        if (!selectedPlan) {
            resetPrices();
            return;
        }

        const paymentType = document.getElementById('payment-status').value;
        const discountValue = paymentType === 'trial'
            ? 0
            : (parseFloat(document.getElementById('discount-value-input').value) || 0);

        const vatRate = 0.15;
        const totalWithVat = parseFloat(selectedPlan.price_after_discount) || 0;
        const vat = totalWithVat * vatRate;
        const subtotal = totalWithVat - vat;
        const finalTotal = paymentType === 'trial' ? 0 : Math.max(0, totalWithVat - discountValue);

        updateSummary(subtotal, vat, totalWithVat, discountValue, finalTotal);
    }

    function resetPrices() {
        updateSummary(0, 0, 0, 0, 0);
    }

    function updateSummary(subtotal = 0, vat = 0, totalWithVat = 0, discount = 0, finalTotal = 0) {
        const planName = selectedPlan ? selectedPlan.name : '-';
        const planElem = document.getElementById('final-plan-name');
        if (planElem) planElem.textContent = planName;

        const planPriceElem = document.getElementById('final-plan-price');
        if (planPriceElem) planPriceElem.textContent = formatNumber(totalWithVat);

        const subtotalElem = document.getElementById('final-subtotal');
        if (subtotalElem) subtotalElem.textContent = formatNumber(subtotal);

        const vatElem = document.getElementById('final-vat');
        if (vatElem) vatElem.textContent = formatNumber(vat);

        const totalWithVatElem = document.getElementById('final-total-with-vat');
        if (totalWithVatElem) totalWithVatElem.textContent = formatNumber(totalWithVat);

        const discountElem = document.getElementById('final-discount');
        if (discountElem) discountElem.textContent = formatNumber(discount);

        const totalElem = document.getElementById('final-total');
        if (totalElem) totalElem.textContent = formatNumber(finalTotal);
    }

    const btnNextPlan = document.getElementById('btn-next-plan');
    const btnPrev = document.querySelector('.btn-prev');

    // 2. إصلاح btnNextPlan ليتحقق من حقول الخطوة الأولى الفعلية
    if (btnNextPlan) {
        btnNextPlan.addEventListener('click', function (e) {
            e.preventDefault();

            Promise.all([
                fv.validateField('company_name'),
                fv.validateField('commercial_number'),
                fv.validateField('name'),
                fv.validateField('phone'),
                fv.validateField('email'),
                fv.validateField('password'),
                fv.validateField('password_confirmation'),
            ]).then(function (results) {
                const allValid = results.every(result => result === 'Valid');
                if (allValid && validationStepper) {
                    validationStepper.next();
                }
            });
        });
    }

    if (btnPrev) {
        btnPrev.addEventListener('click', function () {
            if (validationStepper) {
                validationStepper.previous();
            }
        });
    }

    const discountInput = document.getElementById('discount-value-input');
    if (discountInput) {
        discountInput.addEventListener('input', function () {
            calculatePrices();
            fv.revalidateField('discount_value');
        });
    }

    const paymentMethodSelect = document.getElementById('payment-method-select');
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function () {
            handlePaymentMethodChange(this.value);
        });
    }

    const receiptUpload = document.getElementById('receipt-upload');
    if (receiptUpload) {
        receiptUpload.addEventListener('change', function () {
            fv.revalidateField('receipt_upload');
        });
    }

    const trialEndDate = document.getElementById('trial-end-date');
    if (trialEndDate) {
        trialEndDate.addEventListener('change', function () {
            fv.revalidateField('trial_end_date');
        });
    }

    fv.on('core.form.valid', function () {
        const paymentType = document.getElementById('payment-status').value;
        const paymentMethod = document.getElementById('payment-method-select').value;

        if (paymentType === 'trial') {
            const trialEndDateValue = document.getElementById('trial-end-date').value;
            if (!trialEndDateValue) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('يرجى تحديد تاريخ نهاية التجربة');
                }
                return false;
            }
        }

        if (paymentType === 'paid' && paymentMethod === 'bank_transfer') {
            const receiptFile = document.getElementById('receipt-upload').files;
            if (!receiptFile || receiptFile.length === 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('يرجى رفع إيصال التحويل البنكي');
                }

                const receiptUploadWrapper = document.getElementById('receipt-upload-wrapper');
                receiptUploadWrapper.classList.add('fv-plugins-bootstrap5-row-invalid');
                let feedbackElement = receiptUploadWrapper.querySelector('.invalid-feedback');
                if (!feedbackElement) {
                    feedbackElement = document.createElement('div');
                    feedbackElement.className = 'invalid-feedback';
                    feedbackElement.textContent = 'يرجى رفع إيصال التحويل البنكي';
                    receiptUploadWrapper.appendChild(feedbackElement);
                }

                feedbackElement.style.display = 'block';
                return false;
            }
        }

        if (!selectedPlan) {
            alert('الرجاء اختيار الباقة');
            if (validationStepper) validationStepper.to(0);
            return false;
        }


        if (btnSubmit) {
            btnSubmit.setAttribute('disabled', 'disabled');
            btnSubmit.dataset.oldText = btnSubmit.innerHTML;
            btnSubmit.innerHTML = 'جار المعالجة <span class="spinner-border spinner-border-sm mx-2" role="status" aria-hidden="true"></span> ';
        }

        form.submit();
    });

    const oldPlanId = document.getElementById('plan_id').value;
    if (oldPlanId && plansData.length > 0) {
        selectedPlan = plansData.find(p => p.id == oldPlanId);
        if (selectedPlan) {
            calculatePrices();
        }
    }

    const paymentStatus = document.getElementById('payment-status');
    if (paymentStatus && paymentStatus.value) {
        handlePaymentTypeChange(paymentStatus.value);
    }

    if (paymentMethodSelect && paymentMethodSelect.value) {
        handlePaymentMethodChange(paymentMethodSelect.value);
    }

    const discountType = document.getElementById('discount-type');
    if (discountType && discountType.value) {
        handleDiscountTypeChange(discountType.value);
    }
});
