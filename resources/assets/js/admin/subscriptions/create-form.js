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
    const btnSubmit = document.querySelector('.btn-submit');

    if (!wizard || !form) {
        console.error('Form or Wizard not found!');
        return;
    }

    const plansData = window.plansData || [];
    const draftData = window.companyDraftData || {};

    // Restore draft fields
    Object.entries(draftData).forEach(([name, value]) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (field && !field.value && value !== null && name !== 'password' && name !== 'password_confirmation') {
            if (field.type === 'checkbox') {
                field.checked = Boolean(value);
            } else {
                field.value = value;
            }
        }
    });

    let selectedPlan = null;
    let validationStepper = null;

    if (typeof Stepper !== 'undefined') {
        validationStepper = new Stepper(wizard, { linear: false });
        // Restore draft step if saved previously
        if (draftData && draftData.draft_step && parseInt(draftData.draft_step) > 1) {
            const stepIdx = Math.min(3, parseInt(draftData.draft_step) - 1);
            setTimeout(() => {
                validationStepper.to(stepIdx);
            }, 300);
        }
    }

    // FormValidation instance
    const fv = FormValidation.formValidation(form, {
        fields: {
            // ── Step 1: Company ──
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
            tax_number: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال الرقم الضريبي' }
                }
            },
            city_id: {
                validators: {
                    notEmpty: { message: 'يرجى اختيار المدينة' }
                }
            },
            address: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال العنوان' },
                    stringLength: { max: 1000, message: 'العنوان يجب ألا يتجاوز 1000 حرف' }
                }
            },

            // ── Step 2: Admin User ──
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
            password_confirmation: {
                validators: {
                    notEmpty: { message: 'يرجى تأكيد كلمة المرور' },
                    identical: {
                        compare: function () {
                            const pw = form.querySelector('[name="password"]');
                            return pw ? pw.value : '';
                        },
                        message: 'كلمة المرور وتأكيدها غير متطابقتين'
                    }
                }
            },

            // ── Step 3: Subscription & Payment ──
            plan_id: {
                validators: {
                    notEmpty: { message: 'يرجى اختيار الباقة' }
                }
            },
            start_date: {
                validators: {
                    notEmpty: { message: 'يرجى تحديد تاريخ البداية' },
                    date: { format: 'YYYY-MM-DD', message: 'تاريخ البداية غير صالح' }
                }
            },
            end_date: {
                validators: {
                    callback: {
                        message: 'تاريخ النهاية مطلوب لهذه الباقة ويجب أن يكون بعد تاريخ البداية',
                        callback: function (input) {
                            const planId = document.getElementById('plan_id')?.value;
                            const plan = plansData.find(p => String(p.id) === String(planId));
                            if (!plan) return true;
                            if (plan.subscription_duration_type === 'unlimited') {
                                if (!input.value) return false;
                                const start = document.getElementById('start_date')?.value;
                                return !start || input.value > start;
                            }
                            return true;
                        }
                    }
                }
            },
            total_users: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال عدد المستخدمين' },
                    digits: { message: 'عدد المستخدمين يجب أن يكون رقماً صحيحاً' },
                    greaterThan: { min: 1, message: 'عدد المستخدمين يجب أن يكون 1 على الأقل' }
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
                            const paymentType = document.getElementById('payment-status')?.value;
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
                            const paymentMethod = document.getElementById('payment-method-select')?.value;
                            const paymentType = document.getElementById('payment-status')?.value;
                            if (paymentType === 'paid' && paymentMethod === 'bank_transfer') {
                                return input.files && input.files.length > 0;
                            }
                            return true;
                        }
                    },
                    file: {
                        extension: 'jpeg,jpg,png,pdf',
                        type: 'image/jpeg,image/png,application/pdf',
                        maxSize: 5242880,
                        message: 'يرجى رفع ملف صالح (صورة أو PDF) بحجم أقصى 5MB'
                    }
                }
            },
            discount_value: {
                validators: {
                    callback: {
                        message: 'يرجى إدخال قيمة تسوية صحيحة',
                        callback: function (input) {
                            const hasDiscount = document.getElementById('discount-type')?.value;
                            const paymentType = document.getElementById('payment-status')?.value;
                            if (paymentType !== 'trial' && hasDiscount === 'yes') {
                                return input.value !== '' && parseFloat(input.value) >= 0;
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
                            const paymentType = document.getElementById('payment-status')?.value;
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

    // Select2 integration
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
                if (fieldName) {
                    fv.revalidateField(fieldName);
                    handleFieldChange(fieldName, $this.val());
                }
            });
        });
    }

    function handleFieldChange(fieldName, value) {
        if (fieldName === 'plan_id' && value) {
            selectedPlan = plansData.find(p => String(p.id) === String(value));
            syncPlanDuration();
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
        const paymentMethodWrapper = document.getElementById('payment-method-wrapper');
        const paymentMethodSelect = document.getElementById('payment-method-select');
        const receiptUploadWrapper = document.getElementById('receipt-upload-wrapper');
        const receiptUpload = document.getElementById('receipt-upload');
        const discountWrapper = document.getElementById('discount-type-wrapper');
        const discountSelect = document.getElementById('discount-type');
        const discountValueWrapper = document.getElementById('discount-value-wrapper');
        const discountValueInput = document.getElementById('discount-value-input');
        const trialEndDateWrapper = document.getElementById('trial-end-date-wrapper');
        const trialEndDateInput = document.getElementById('trial-end-date');

        if (value === 'paid') {
            if (paymentMethodWrapper) paymentMethodWrapper.style.display = 'block';
            if (discountWrapper) discountWrapper.style.display = 'block';
            if (trialEndDateWrapper) trialEndDateWrapper.style.display = 'none';

            fv.enableValidator('payment_method');
            fv.disableValidator('trial_end_date');

            const selectedMethod = paymentMethodSelect ? paymentMethodSelect.value : '';
            if (selectedMethod === 'bank_transfer') {
                if (receiptUploadWrapper) receiptUploadWrapper.style.display = 'block';
                fv.enableValidator('receipt_upload');
            } else {
                if (receiptUploadWrapper) receiptUploadWrapper.style.display = 'none';
                fv.disableValidator('receipt_upload');
            }

            handleDiscountTypeChange(discountSelect ? discountSelect.value : 'no');
        } else if (value === 'unpaid') {
            if (paymentMethodWrapper) paymentMethodWrapper.style.display = 'none';
            if (receiptUploadWrapper) receiptUploadWrapper.style.display = 'none';
            if (trialEndDateWrapper) trialEndDateWrapper.style.display = 'none';
            if (discountWrapper) discountWrapper.style.display = 'block';

            fv.disableValidator('payment_method');
            fv.disableValidator('receipt_upload');
            fv.disableValidator('trial_end_date');

            if (window.jQuery && paymentMethodSelect) {
                window.jQuery(paymentMethodSelect).val('').trigger('change');
            }

            handleDiscountTypeChange(discountSelect ? discountSelect.value : 'no');
        } else if (value === 'trial') {
            if (paymentMethodWrapper) paymentMethodWrapper.style.display = 'none';
            if (receiptUploadWrapper) receiptUploadWrapper.style.display = 'none';
            if (discountWrapper) discountWrapper.style.display = 'none';
            if (discountValueWrapper) discountValueWrapper.style.display = 'none';
            if (trialEndDateWrapper) trialEndDateWrapper.style.display = 'block';

            fv.disableValidator('payment_method');
            fv.disableValidator('receipt_upload');
            fv.disableValidator('discount_value');
            fv.enableValidator('trial_end_date');

            if (discountValueInput) discountValueInput.value = '0';
            calculatePrices();
        } else {
            if (paymentMethodWrapper) paymentMethodWrapper.style.display = 'none';
            if (receiptUploadWrapper) receiptUploadWrapper.style.display = 'none';
            if (discountWrapper) discountWrapper.style.display = 'none';
            if (discountValueWrapper) discountValueWrapper.style.display = 'none';
            if (trialEndDateWrapper) trialEndDateWrapper.style.display = 'none';

            fv.disableValidator('payment_method');
            fv.disableValidator('receipt_upload');
            fv.disableValidator('discount_value');
            fv.disableValidator('trial_end_date');
        }
    }

    function handlePaymentMethodChange(value) {
        const receiptUploadWrapper = document.getElementById('receipt-upload-wrapper');
        const paymentType = document.getElementById('payment-status')?.value;

        if (paymentType === 'paid' && value === 'bank_transfer') {
            if (receiptUploadWrapper) receiptUploadWrapper.style.display = 'block';
            fv.enableValidator('receipt_upload');
            fv.revalidateField('receipt_upload');
        } else {
            if (receiptUploadWrapper) receiptUploadWrapper.style.display = 'none';
            fv.disableValidator('receipt_upload');
            const receiptUpload = document.getElementById('receipt-upload');
            if (receiptUpload) receiptUpload.value = '';
        }
    }

    function handleDiscountTypeChange(value) {
        const paymentType = document.getElementById('payment-status')?.value;
        const discountValueWrapper = document.getElementById('discount-value-wrapper');
        const discountValueInput = document.getElementById('discount-value-input');

        if (paymentType === 'trial') {
            if (discountValueWrapper) discountValueWrapper.style.display = 'none';
            if (discountValueInput) discountValueInput.value = '0';
            fv.disableValidator('discount_value');
            calculatePrices();
            return;
        }

        if (value === 'yes') {
            if (discountValueWrapper) discountValueWrapper.style.display = 'block';
            fv.enableValidator('discount_value');
            if (discountValueInput) {
                setTimeout(() => discountValueInput.focus(), 200);
            }
        } else {
            if (discountValueWrapper) discountValueWrapper.style.display = 'none';
            if (discountValueInput) discountValueInput.value = '0';
            fv.disableValidator('discount_value');
            calculatePrices();
        }
    }

    function calculatePrices() {
        if (!selectedPlan) {
            resetPrices();
            return;
        }

        const paymentType = document.getElementById('payment-status')?.value;
        const discountValue = paymentType === 'trial'
            ? 0
            : (parseFloat(document.getElementById('discount-value-input')?.value) || 0);

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

        const vatElem = document.getElementById('final-vat');
        if (vatElem) vatElem.textContent = formatNumber(vat);

        const discountElem = document.getElementById('final-discount');
        if (discountElem) discountElem.textContent = formatNumber(discount);

        const totalElem = document.getElementById('final-total');
        if (totalElem) totalElem.textContent = formatNumber(finalTotal);
    }

    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const totalUsersInput = document.getElementById('total_users');
    const planSelect = document.getElementById('plan_id');

    function syncPlanDuration() {
        const planId = planSelect ? planSelect.value : '';
        const plan = plansData.find(p => String(p.id) === String(planId));
        if (!plan || !startDateInput || !endDateInput) return;

        selectedPlan = plan;

        if (totalUsersInput && !totalUsersInput.value) {
            totalUsersInput.value = plan.included_users || 1;
        }

        const limited = plan.subscription_duration_type === 'limited';
        endDateInput.disabled = limited;

        const endDateRequired = document.getElementById('end-date-required');
        const endDateHint = document.getElementById('end-date-hint');

        if (limited) {
            if (endDateRequired) endDateRequired.style.display = 'none';
            if (endDateHint) endDateHint.textContent = 'محسوب تلقائياً حسب دورة الفوترة للباقة';
            if (startDateInput.value) {
                const d = new Date(startDateInput.value + 'T00:00:00');
                if (plan.subscription_billing_cycle === 'monthly') {
                    d.setMonth(d.getMonth() + 1);
                } else {
                    d.setFullYear(d.getFullYear() + 1);
                }
                endDateInput.value = d.toISOString().slice(0, 10);
            }
        } else {
            if (endDateRequired) endDateRequired.style.display = 'inline';
            if (endDateHint) endDateHint.textContent = 'تاريخ النهاية إلزامي للباقات غير محددة المدة';
        }
    }

    if (startDateInput) startDateInput.addEventListener('change', syncPlanDuration);
    if (planSelect) planSelect.addEventListener('change', syncPlanDuration);

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

    // ── Helper: Save Draft via Ajax ──
    async function saveDraftData(step) {
        try {
            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());
            payload.draft_step = step;
            delete payload.receipt_upload;
            delete payload.password;
            delete payload.password_confirmation;

            const csrfToken = form.querySelector('[name=_token]')?.value || '';
            const response = await fetch('/admin/companies/draft', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            return response.ok;
        } catch (e) {
            console.error('Draft save failed:', e);
            return false;
        }
    }

    // Attach click for all "حفظ كمسودة" buttons
    document.querySelectorAll('.btn-save-draft').forEach(btn => {
        btn.addEventListener('click', async function () {
            const step = this.dataset.step || '1';
            btn.disabled = true;
            const success = await saveDraftData(step);
            btn.disabled = false;
            if (success && typeof toastr !== 'undefined') {
                toastr.success('تم حفظ المسودة بنجاح');
            } else if (!success && typeof toastr !== 'undefined') {
                toastr.error('تعذر حفظ المسودة');
            }
        });
    });

    // ── Helper: Update Review Summary Screen (Step 4) ──
    function updateReviewSummary() {
        // Card 1: Company
        const compName = document.getElementById('company_name')?.value || '-';
        const commNum = document.getElementById('commercial_number')?.value || '-';
        const taxNum = document.getElementById('tax_number')?.value || '-';
        const citySelect = document.getElementById('city_id');
        const cityName = citySelect && citySelect.selectedIndex >= 0 ? citySelect.options[citySelect.selectedIndex].text : '-';
        const address = document.getElementById('address')?.value || '-';

        const rCompName = document.getElementById('review-company-name');
        if (rCompName) rCompName.textContent = compName;
        const rCommNum = document.getElementById('review-commercial-number');
        if (rCommNum) rCommNum.textContent = commNum;
        const rTaxNum = document.getElementById('review-tax-number');
        if (rTaxNum) rTaxNum.textContent = taxNum;
        const rCity = document.getElementById('review-city');
        if (rCity) rCity.textContent = cityName;
        const rAddr = document.getElementById('review-address');
        if (rAddr) rAddr.textContent = address;

        // Card 2: Admin User
        const adminName = document.getElementById('name')?.value || '-';
        const adminPhone = document.getElementById('phone')?.value || '-';
        const adminEmail = document.getElementById('email')?.value || '-';

        const rAdminName = document.getElementById('review-admin-name');
        if (rAdminName) rAdminName.textContent = adminName;
        const rAdminPhone = document.getElementById('review-admin-phone');
        if (rAdminPhone) rAdminPhone.textContent = adminPhone;
        const rAdminEmail = document.getElementById('review-admin-email');
        if (rAdminEmail) rAdminEmail.textContent = adminEmail;

        // Card 3: Subscription & Payment
        const planName = selectedPlan ? selectedPlan.name : '-';
        const startDate = document.getElementById('start_date')?.value || '-';
        const endDate = document.getElementById('end_date')?.value || '-';
        const totalUsers = document.getElementById('total_users')?.value || '-';
        const isImmediate = document.getElementById('activate_immediately')?.checked;
        const paymentType = document.getElementById('payment-status')?.value || '';
        const paymentMethodSelect = document.getElementById('payment-method-select');
        const paymentMethodText = paymentMethodSelect && paymentMethodSelect.selectedIndex >= 0 ? paymentMethodSelect.options[paymentMethodSelect.selectedIndex].text : '-';
        const finalTotalText = document.getElementById('final-total')?.textContent || '0.00';

        const rPlan = document.getElementById('review-plan-name');
        if (rPlan) rPlan.textContent = planName;
        const rStart = document.getElementById('review-start-date');
        if (rStart) rStart.textContent = startDate;
        const rEnd = document.getElementById('review-end-date');
        if (rEnd) rEnd.textContent = endDate;
        const rUsers = document.getElementById('review-total-users');
        if (rUsers) rUsers.textContent = totalUsers + ' مستخدم';

        const rAct = document.getElementById('review-activation-status');
        if (rAct) {
            rAct.className = isImmediate ? 'badge bg-label-success' : 'badge bg-label-warning';
            rAct.textContent = isImmediate ? 'مفعل فوراً' : 'قيد المراجعة';
        }

        const rPay = document.getElementById('review-payment-status');
        if (rPay) {
            const payMap = {
                paid: ['تم الدفع', 'bg-label-success'],
                unpaid: ['لم يتم الدفع', 'bg-label-danger'],
                trial: ['فترة تجربة', 'bg-label-info']
            };
            const item = payMap[paymentType] || ['-', 'bg-label-secondary'];
            rPay.className = 'badge ' + item[1];
            rPay.textContent = item[0];
        }

        const rMethod = document.getElementById('review-payment-method');
        if (rMethod) {
            rMethod.textContent = paymentType === 'paid' ? paymentMethodText : '-';
        }

        const rTotal = document.getElementById('review-final-total');
        if (rTotal) {
            rTotal.textContent = finalTotalText + ' ر.س';
        }
    }

    // ── Stepper Navigation Buttons ──

    async function validateServerStep(fields) {
        const endpoint = document.body.dataset.wizardDraftsUrl;
        if (!endpoint) return true;

        const payload = new FormData(form);
        payload.append('target_path', new URL(form.action).pathname);
        fields.forEach(field => payload.append('fields[]', field));

        const response = await fetch(`${endpoint.replace(/\/$/, '')}/validate-step`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                Accept: 'application/json'
            },
            body: payload
        });

        return response.ok;
    }

    const btnPrevCompany = document.getElementById('btn-prev-company');
    if (btnPrevCompany) {
        btnPrevCompany.addEventListener('click', function () {
            window.location.assign(form.action);
        });
    }

    // Step 1 -> Next: Validate Step 1 fields
    const btnNextCompany = document.getElementById('btn-next-company');
    if (btnNextCompany) {
        btnNextCompany.addEventListener('click', async function (e) {
            e.preventDefault();
            Promise.all([
                fv.validateField('company_name'),
                fv.validateField('commercial_number'),
                fv.validateField('tax_number'),
                fv.validateField('city_id'),
                fv.validateField('address'),
            ]).then(async function (results) {
                if (results.every(r => r === 'Valid') && await validateServerStep([
                    'company_name', 'commercial_number', 'tax_number', 'city_id', 'address'
                ]) && validationStepper) {
                    saveDraftData(1);
                    validationStepper.to(1);
                }
            });
        });
    }

    // Step 2 -> Prev: Back to Step 1
    const btnPrevAdmin = document.getElementById('btn-prev-admin');
    if (btnPrevAdmin) {
        btnPrevAdmin.addEventListener('click', function () {
            if (validationStepper) validationStepper.to(0);
        });
    }

    // Step 2 -> Next: Validate Step 2 fields
    const btnNextAdmin = document.getElementById('btn-next-admin');
    if (btnNextAdmin) {
        btnNextAdmin.addEventListener('click', async function (e) {
            e.preventDefault();
            Promise.all([
                fv.validateField('name'),
                fv.validateField('phone'),
                fv.validateField('email'),
                fv.validateField('password'),
                fv.validateField('password_confirmation'),
            ]).then(async function (results) {
                if (results.every(r => r === 'Valid') && await validateServerStep([
                    'name', 'phone', 'email', 'password', 'password_confirmation'
                ]) && validationStepper) {
                    saveDraftData(2);
                    validationStepper.to(2);
                }
            });
        });
    }

    // Step 3 -> Prev: Back to Step 2
    const btnPrevPayment = document.getElementById('btn-prev-payment');
    if (btnPrevPayment) {
        btnPrevPayment.addEventListener('click', function () {
            if (validationStepper) validationStepper.to(1);
        });
    }

    // Step 3 -> Next: Validate Step 3 fields, update review screen, go to Step 4
    const btnNextPayment = document.getElementById('btn-next-payment');
    if (btnNextPayment) {
        btnNextPayment.addEventListener('click', async function (e) {
            e.preventDefault();

            const validations = [
                fv.validateField('plan_id'),
                fv.validateField('start_date'),
                fv.validateField('total_users'),
                fv.validateField('payment_type')
            ];

            const planId = document.getElementById('plan_id')?.value;
            const plan = plansData.find(p => String(p.id) === String(planId));
            if (plan && plan.subscription_duration_type === 'unlimited') {
                validations.push(fv.validateField('end_date'));
            }

            const paymentType = document.getElementById('payment-status')?.value;
            if (paymentType === 'paid') {
                validations.push(fv.validateField('payment_method'));
                if (document.getElementById('payment-method-select')?.value === 'bank_transfer') {
                    validations.push(fv.validateField('receipt_upload'));
                }
                if (document.getElementById('discount-type')?.value === 'yes') {
                    validations.push(fv.validateField('discount_value'));
                }
            } else if (paymentType === 'trial') {
                validations.push(fv.validateField('trial_end_date'));
            }

            Promise.all(validations).then(async function (results) {
                if (results.every(r => r === 'Valid') && await validateServerStep([
                    'plan_id', 'start_date', 'end_date', 'total_users', 'payment_type', 'payment_method',
                    'receipt_upload', 'has_discount', 'discount_value', 'trial_end_date', 'activate_immediately'
                ]) && validationStepper) {
                    updateReviewSummary();
                    saveDraftData(3);
                    validationStepper.to(3);
                }
            });
        });
    }

    // Step 4 -> Prev: Back to Step 3
    const btnPrevReview = document.getElementById('btn-prev-review');
    if (btnPrevReview) {
        btnPrevReview.addEventListener('click', function () {
            if (validationStepper) validationStepper.to(2);
        });
    }

    // Step 4: Edit buttons for each card
    document.querySelectorAll('.btn-edit-step').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetStep = parseInt(this.dataset.targetStep) || 0;
            if (validationStepper) {
                validationStepper.to(targetStep);
            }
        });
    });

    // Step 4: Final Submit Validation & Form Submission
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Enable disabled inputs before submitting so values are included
        if (endDateInput && endDateInput.disabled) {
            endDateInput.disabled = false;
        }

        fv.validate().then(function (status) {
            if (status === 'Valid') {
                if (btnSubmit) {
                    btnSubmit.setAttribute('disabled', 'disabled');
                    btnSubmit.innerHTML = 'جار الاعتماد والحفظ <span class="spinner-border spinner-border-sm mx-2" role="status"></span>';
                }
                form.submit();
            } else {
                if (endDateInput && selectedPlan && selectedPlan.subscription_duration_type === 'limited') {
                    endDateInput.disabled = true;
                }
                if (typeof toastr !== 'undefined') {
                    toastr.error('يرجى مراجعة كافة البيانات والتأكد من صحتها قبل الحفظ');
                }
            }
        });
    });

    // ── Initial State Restoration ──
    const oldPlanId = document.getElementById('plan_id')?.value;
    if (oldPlanId && plansData.length > 0) {
        selectedPlan = plansData.find(p => String(p.id) === String(oldPlanId));
        if (selectedPlan) {
            syncPlanDuration();
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
