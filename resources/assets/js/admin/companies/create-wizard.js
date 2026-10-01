'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('company-wizard-form');
    const wizardEl = document.getElementById('company-wizard');

    if (!wizardEl || !form) {
        return;
    }

    const plansData = window.plansData || [];

    let validationStepper = null;
    if (typeof Stepper !== 'undefined') {
        validationStepper = new Stepper(wizardEl, { linear: false });
    }

    // Format numbers
    function formatNumber(number) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(number);
    }

    // Initialize FormValidation
    const fv = FormValidation.formValidation(form, {
        fields: {
            // Step 1
            company_name: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال اسم الشركة' },
                    stringLength: { min: 2, max: 255, message: 'اسم الشركة يجب أن يكون بين 2 و 255 حرفاً' }
                }
            },
            commercial_number: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال رقم السجل التجاري' },
                    stringLength: { min: 10, max: 10, message: 'رقم السجل التجاري يجب أن يتكون من 10 أرقام' },
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
                    notEmpty: { message: 'يرجى إدخال العنوان' }
                }
            },

            // Step 2
            name: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال اسم المسؤول' },
                    stringLength: { min: 2, max: 255, message: 'الاسم يجب أن يكون بين 2 و 255 حرفاً' }
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

            // Step 3
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
                            const payType = document.getElementById('payment-status')?.value;
                            if (payType === 'paid') {
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
                            const payType = document.getElementById('payment-status')?.value;
                            const payMethod = document.getElementById('payment-method-select')?.value;
                            if (payType === 'paid' && payMethod === 'bank_transfer') {
                                return input.files && input.files.length > 0;
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
                rowSelector: '.col-md-6, .col-12, .col-md-4'
            }),
            autoFocus: new FormValidation.plugins.AutoFocus()
        }
    });

    // Revalidate Select2 fields on change
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        $('.select2').on('change.select2', function () {
            const fieldName = $(this).attr('name');
            if (fieldName) {
                fv.revalidateField(fieldName);
            }
        });
    }

    // Dynamic Plan limits and details update
    function updatePlanDetails(planId) {
        const plan = plansData.find(p => String(p.id) === String(planId));
        if (!plan) {
            document.getElementById('plan_users_display').value = '-';
            document.getElementById('plan_properties_display').value = '-';
            document.getElementById('plan_storage_display').value = '-';
            document.getElementById('plan_auctions_display').value = '-';
            return;
        }

        document.getElementById('plan_users_display').value = plan.included_users || 1;
        document.getElementById('plan_properties_display').value = plan.properties_limit !== null ? plan.properties_limit : 'غير محدود (∞)';
        document.getElementById('plan_storage_display').value = plan.storage_quota_gb !== null ? (plan.storage_quota_gb + ' GB') : 'غير محدود (∞)';
        document.getElementById('plan_auctions_display').value = plan.active_auctions_limit !== null ? plan.active_auctions_limit : 'غير محدود (∞)';

        // Auto calculate end date based on plan duration
        const startDateVal = document.getElementById('start_date')?.value;
        if (startDateVal) {
            const start = new Date(startDateVal);
            if (!isNaN(start.getTime())) {
                const end = new Date(start);
                if (plan.subscription_duration_type === 'limited' && plan.subscription_billing_cycle === 'monthly') {
                    end.setMonth(end.getMonth() + 1);
                } else {
                    end.setFullYear(end.getFullYear() + 1);
                }
                const formattedEnd = end.toISOString().split('T')[0];
                document.getElementById('end_date').value = formattedEnd;
            }
        }
    }

    const planSelect = document.getElementById('plan_id');
    if (planSelect) {
        planSelect.addEventListener('change', function () {
            updatePlanDetails(this.value);
            fv.revalidateField('plan_id');
        });
    }

    // Payment status change handlers
    const paymentStatus = document.getElementById('payment-status');
    const paymentMethodWrapper = document.getElementById('payment-method-wrapper');
    const receiptUploadWrapper = document.getElementById('receipt-upload-wrapper');
    const trialDateWrapper = document.getElementById('trial-date-wrapper');
    const paymentMethodSelect = document.getElementById('payment-method-select');

    function handlePaymentStatusChange(status) {
        if (status === 'paid') {
            paymentMethodWrapper.style.display = 'block';
            trialDateWrapper.style.display = 'none';
        } else if (status === 'trial') {
            paymentMethodWrapper.style.display = 'none';
            receiptUploadWrapper.style.display = 'none';
            trialDateWrapper.style.display = 'block';
        } else {
            paymentMethodWrapper.style.display = 'none';
            receiptUploadWrapper.style.display = 'none';
            trialDateWrapper.style.display = 'none';
        }
        fv.revalidateField('payment_type');
    }

    if (paymentStatus) {
        paymentStatus.addEventListener('change', function () {
            handlePaymentStatusChange(this.value);
        });
    }

    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function () {
            if (this.value === 'bank_transfer' && paymentStatus?.value === 'paid') {
                receiptUploadWrapper.style.display = 'block';
            } else {
                receiptUploadWrapper.style.display = 'none';
            }
            fv.revalidateField('payment_method');
        });
    }

    // Discount toggle
    const discountType = document.getElementById('discount-type');
    const discountWrapper = document.getElementById('discount-wrapper');
    if (discountType) {
        discountType.addEventListener('change', function () {
            discountWrapper.style.display = this.value === 'yes' ? 'block' : 'none';
        });
    }

    // Review Summary Screen Update (Step 4)
    function updateReviewSummary() {
        // Step 1: Company
        const compName = document.getElementById('company_name')?.value || '-';
        const commNum = document.getElementById('commercial_number')?.value || '-';
        const taxNum = document.getElementById('tax_number')?.value || '-';
        const citySelect = document.getElementById('city_id');
        const cityName = citySelect && citySelect.selectedIndex >= 0 ? citySelect.options[citySelect.selectedIndex].text : '-';
        const address = document.getElementById('address')?.value || '-';

        const emailNotif = form.querySelector('[name="email_notifications_enabled"]')?.checked;
        const twoFa = form.querySelector('[name="two_factor_enabled"]')?.checked;
        const extInteg = form.querySelector('[name="external_integrations_enabled"]')?.checked;
        const settingsArr = [];
        if (emailNotif) settingsArr.push('تنبيهات البريد');
        if (twoFa) settingsArr.push('التحقق الثنائي');
        if (extInteg) settingsArr.push('التكاملات الخارجية');
        const settingsText = settingsArr.length > 0 ? settingsArr.join('، ') : 'بدون إعدادات إضافية';

        document.getElementById('review-company-name').textContent = compName;
        document.getElementById('review-commercial-number').textContent = commNum;
        document.getElementById('review-tax-number').textContent = taxNum;
        document.getElementById('review-city').textContent = cityName;
        document.getElementById('review-address').textContent = address;
        document.getElementById('review-settings-summary').textContent = settingsText;

        // Step 2: Admin User
        document.getElementById('review-admin-name').textContent = document.getElementById('name')?.value || '-';
        document.getElementById('review-admin-phone').textContent = document.getElementById('phone')?.value || '-';
        document.getElementById('review-admin-email').textContent = document.getElementById('email')?.value || '-';

        // Step 3: Plan & Subscription
        const planId = document.getElementById('plan_id')?.value;
        const plan = plansData.find(p => String(p.id) === String(planId));

        document.getElementById('review-plan-name').textContent = plan ? plan.name : '-';
        document.getElementById('review-plan-users').textContent = (plan ? (plan.included_users || 1) : '-') + ' مستخدم';
        document.getElementById('review-plan-props').textContent = plan ? (plan.properties_limit !== null ? plan.properties_limit : 'غير محدود (∞)') : '-';
        document.getElementById('review-plan-storage').textContent = plan ? (plan.storage_quota_gb !== null ? plan.storage_quota_gb + ' GB' : 'غير محدود (∞)') : '-';
        document.getElementById('review-plan-auctions').textContent = plan ? (plan.active_auctions_limit !== null ? plan.active_auctions_limit : 'غير محدود (∞)') : '-';

        const featuresContainer = document.getElementById('review-plan-features');
        if (plan && Array.isArray(plan.features) && plan.features.length > 0) {
            featuresContainer.innerHTML = plan.features.map(f => `<span class="badge bg-label-info">${f}</span>`).join(' ');
        } else {
            featuresContainer.textContent = '-';
        }

        document.getElementById('review-start-date').textContent = document.getElementById('start_date')?.value || '-';
        document.getElementById('review-end-date').textContent = document.getElementById('end_date')?.value || '-';

        const isImmediate = document.getElementById('activate_immediately')?.checked;
        const rAct = document.getElementById('review-activation-status');
        if (rAct) {
            rAct.className = isImmediate ? 'badge bg-label-success' : 'badge bg-label-warning';
            rAct.textContent = isImmediate ? 'مفعل فوراً' : 'قيد المراجعة';
        }

        const payType = document.getElementById('payment-status')?.value || '';
        const rPay = document.getElementById('review-payment-status');
        if (rPay) {
            const payMap = {
                paid: ['تم الدفع', 'bg-label-success'],
                unpaid: ['لم يتم الدفع', 'bg-label-danger'],
                trial: ['فترة تجربة', 'bg-label-info']
            };
            const item = payMap[payType] || ['-', 'bg-label-secondary'];
            rPay.className = 'badge ' + item[1];
            rPay.textContent = item[0];
        }

        const rMethod = document.getElementById('review-payment-method');
        if (rMethod) {
            rMethod.textContent = payType === 'paid' && paymentMethodSelect && paymentMethodSelect.selectedIndex >= 0
                ? paymentMethodSelect.options[paymentMethodSelect.selectedIndex].text
                : '-';
        }

        // Calculate price
        const basePrice = plan ? parseFloat(plan.price_after_discount || plan.price || 0) : 0;
        const discountVal = discountType?.value === 'yes' ? parseFloat(document.getElementById('discount_value')?.value || 0) : 0;
        const finalPrice = payType === 'trial' ? 0 : Math.max(0, basePrice - discountVal);
        document.getElementById('review-final-total').textContent = formatNumber(finalPrice) + ' ر.س';
    }

    // Server step validation helper via /admin/wizard-drafts/validate-step
    async function validateServerStep(fields) {
        try {
            const payload = new FormData(form);
            payload.append('target_path', new URL(form.action).pathname);
            fields.forEach(f => payload.append('fields[]', f));

            const response = await fetch('/admin/wizard-drafts/validate-step', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json'
                },
                body: payload
            });

            return response.ok;
        } catch (e) {
            console.error('Server validation error:', e);
            return true;
        }
    }

    // Step 1 -> Next
    document.getElementById('btn-next-company')?.addEventListener('click', async function (e) {
        e.preventDefault();
        const step1Fields = ['company_name', 'commercial_number', 'tax_number', 'city_id', 'address'];
        const results = await Promise.all(step1Fields.map(f => fv.validateField(f)));

        if (results.every(r => r === 'Valid') && await validateServerStep(step1Fields)) {
            if (validationStepper) validationStepper.to(1);
        }
    });

    // Step 2 -> Prev
    document.getElementById('btn-prev-admin')?.addEventListener('click', function () {
        if (validationStepper) validationStepper.to(0);
    });

    // Step 2 -> Next
    document.getElementById('btn-next-admin')?.addEventListener('click', async function (e) {
        e.preventDefault();
        const step2Fields = ['name', 'phone', 'email', 'password', 'password_confirmation'];
        const results = await Promise.all(step2Fields.map(f => fv.validateField(f)));

        if (results.every(r => r === 'Valid') && await validateServerStep(step2Fields)) {
            if (validationStepper) validationStepper.to(2);
        }
    });

    // Step 3 -> Prev
    document.getElementById('btn-prev-payment')?.addEventListener('click', function () {
        if (validationStepper) validationStepper.to(1);
    });

    // Step 3 -> Next
    document.getElementById('btn-next-payment')?.addEventListener('click', async function (e) {
        e.preventDefault();
        const step3Fields = ['plan_id', 'start_date', 'payment_type'];
        if (paymentStatus?.value === 'paid') {
            step3Fields.push('payment_method');
            if (paymentMethodSelect?.value === 'bank_transfer') {
                step3Fields.push('receipt_upload');
            }
        }

        const results = await Promise.all(step3Fields.map(f => fv.validateField(f)));
        if (results.every(r => r === 'Valid') && await validateServerStep(step3Fields)) {
            updateReviewSummary();
            if (validationStepper) validationStepper.to(3);
        }
    });

    // Step 4 -> Prev
    document.getElementById('btn-prev-review')?.addEventListener('click', function () {
        if (validationStepper) validationStepper.to(2);
    });
});
