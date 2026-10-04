'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('subscription-wizard-form');
    const wizardEl = document.getElementById('subscription-wizard');

    if (!wizardEl || !form) {
        return;
    }

    const companiesData = window.companiesData || [];
    const plansData = window.plansData || [];

    let validationStepper = null;
    if (typeof Stepper !== 'undefined') {
        validationStepper = new Stepper(wizardEl, { linear: false });
    }

    function formatNumber(number) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(number);
    }

    // Initialize FormValidation
    const fv = FormValidation.formValidation(form, {
        fields: {
            company_id: {
                validators: {
                    notEmpty: { message: 'يرجى اختيار الشركة' }
                }
            },
            plan_id: {
                validators: {
                    notEmpty: { message: 'يرجى اختيار الباقة' }
                }
            },
            start_date: {
                validators: {
                    notEmpty: { message: 'يرجى تحديد تاريخ بداية الاشتراك' },
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
                        message: 'يرجى اختيار طريقة الدفع',
                        callback: function (input) {
                            const payType = document.getElementById('sub_payment-status')?.value;
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
                            const payType = document.getElementById('sub_payment-status')?.value;
                            const payMethod = document.getElementById('sub_payment-method-select')?.value;
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
                rowSelector: '.col-md-6, .col-12, .col-md-3, .col-md-4'
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

    // Company selection handler
    function updateCompanyCard(companyId) {
        const card = document.getElementById('company-details-card');
        if (!companyId) {
            if (card) card.style.display = 'none';
            return;
        }

        const comp = companiesData.find(c => String(c.id) === String(companyId));
        if (comp) {
            document.getElementById('company-commercial').textContent = comp.commercial_number || '-';
            document.getElementById('company-tax').textContent = comp.tax_number || '-';
            document.getElementById('company-city').textContent = comp.city ? comp.city.name : (comp.city_name || '-');
            document.getElementById('company-address').textContent = comp.address || '-';

            const lastSub = comp.last_subscription || comp.lastSubscription;
            const lastSubText = document.getElementById('company-last-sub-text');
            if (lastSub) {
                const planName = lastSub.plan ? (lastSub.plan.name || '-') : '-';
                const statusLabel = lastSub.status || 'منتهي';
                const endsAt = lastSub.ends_at ? String(lastSub.ends_at).substring(0, 10) : '-';
                lastSubText.innerHTML = `<span class="badge bg-label-danger me-1">${statusLabel}</span> باقة: ${planName} (انتهى بتاريخ: ${endsAt})`;
            } else {
                lastSubText.innerHTML = '<span class="text-muted">لا يوجد اشتراك سابق</span>';
            }

            if (card) card.style.display = 'block';
        } else {
            // Fallback via AJAX
            fetch(`/admin/subscriptions/companies/${companyId}/details`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('company-commercial').textContent = data.commercial_number || '-';
                document.getElementById('company-tax').textContent = data.tax_number || '-';
                document.getElementById('company-city').textContent = data.city_name || '-';
                document.getElementById('company-address').textContent = data.address || '-';

                const lastSubText = document.getElementById('company-last-sub-text');
                if (data.last_subscription) {
                    lastSubText.innerHTML = `<span class="badge bg-label-danger me-1">${data.last_subscription.status}</span> باقة: ${data.last_subscription.plan} (انتهى بتاريخ: ${data.last_subscription.ends_at})`;
                } else {
                    lastSubText.innerHTML = '<span class="text-muted">لا يوجد اشتراك سابق</span>';
                }
                if (card) card.style.display = 'block';
            })
            .catch(() => {
                if (card) card.style.display = 'none';
            });
        }
    }

    const companySelect = document.getElementById('company_id');
    if (companySelect) {
        $(companySelect).on('change', function () {
            updateCompanyCard(this.value);
            fv.revalidateField('company_id');
        });
        if (companySelect.value) {
            updateCompanyCard(companySelect.value);
        }
    }

    // Plan selection handler
    function updatePlanDetails(planId) {
        const plan = plansData.find(p => String(p.id) === String(planId));
        if (!plan) {
            document.getElementById('sub_plan_users_display').value = '-';
            document.getElementById('sub_plan_properties_display').value = '-';
            document.getElementById('sub_plan_storage_display').value = '-';
            document.getElementById('sub_plan_auctions_display').value = '-';
            document.getElementById('sub_plan_features_display').innerHTML = '<span class="text-muted">اختر الباقة لعرض مميزاتها</span>';
            return;
        }

        document.getElementById('sub_plan_users_display').value = plan.included_users || 1;
        document.getElementById('sub_plan_properties_display').value = plan.properties_limit !== null ? plan.properties_limit : 'غير محدود (∞)';
        document.getElementById('sub_plan_storage_display').value = plan.storage_quota_gb !== null ? (plan.storage_quota_gb + ' GB') : 'غير محدود (∞)';
        document.getElementById('sub_plan_auctions_display').value = plan.active_auctions_limit !== null ? plan.active_auctions_limit : 'غير محدود (∞)';

        const featContainer = document.getElementById('sub_plan_features_display');
        if (Array.isArray(plan.features) && plan.features.length > 0) {
            featContainer.innerHTML = plan.features.map(f => `<span class="badge bg-label-info">${f}</span>`).join(' ');
        } else {
            featContainer.innerHTML = '<span class="text-muted">لا توجد مميزات إضافية مسجلة</span>';
        }

        // Auto calculate end date based on plan duration
        const startDateVal = document.getElementById('sub_start_date')?.value;
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
                document.getElementById('sub_end_date').value = formattedEnd;
            }
        }
    }

    const planSelect = document.getElementById('plan_id');
    if (planSelect) {
        $(planSelect).on('change', function () {
            updatePlanDetails(this.value);
            fv.revalidateField('plan_id');
        });
        if (planSelect.value) {
            updatePlanDetails(planSelect.value);
        }
    }

    // Payment status change handlers
    const paymentStatus = document.getElementById('sub_payment-status');
    const paymentMethodWrapper = document.getElementById('sub_payment-method-wrapper');
    const receiptUploadWrapper = document.getElementById('sub_receipt-upload-wrapper');
    const trialDateWrapper = document.getElementById('sub_trial-date-wrapper');
    const paymentMethodSelect = document.getElementById('sub_payment-method-select');

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
        handlePaymentStatusChange(paymentStatus.value);
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
    const discountType = document.getElementById('sub_discount-type');
    const discountWrapper = document.getElementById('sub_discount-wrapper');
    if (discountType) {
        discountType.addEventListener('change', function () {
            discountWrapper.style.display = this.value === 'yes' ? 'block' : 'none';
        });
        discountWrapper.style.display = discountType.value === 'yes' ? 'block' : 'none';
    }

    // Review Summary Screen Update (Step 3)
    function updateReviewSummary() {
        // Step 1: Company
        const compId = document.getElementById('company_id')?.value;
        const comp = companiesData.find(c => String(c.id) === String(compId));
        const compSelect = document.getElementById('company_id');
        const compText = compSelect && compSelect.selectedIndex >= 0 ? compSelect.options[compSelect.selectedIndex].text : '-';

        document.getElementById('sub_review-company-name').textContent = comp ? comp.company_name : compText;
        document.getElementById('sub_review-commercial-number').textContent = comp ? (comp.commercial_number || '-') : '-';
        document.getElementById('sub_review-tax-number').textContent = comp ? (comp.tax_number || '-') : '-';
        document.getElementById('sub_review-city').textContent = comp ? (comp.city?.name || '-') : '-';

        const lastSub = comp ? (comp.last_subscription || comp.lastSubscription) : null;
        if (lastSub) {
            const planTitle = lastSub.plan?.name || '-';
            const endsAt = lastSub.ends_at ? String(lastSub.ends_at).substring(0, 10) : '-';
            document.getElementById('sub_review-last-sub').textContent = `${planTitle} (انتهى: ${endsAt})`;
        } else {
            document.getElementById('sub_review-last-sub').textContent = 'لا يوجد اشتراك سابق';
        }

        // Plan & Limits
        const planId = document.getElementById('plan_id')?.value;
        const plan = plansData.find(p => String(p.id) === String(planId));

        document.getElementById('sub_review-plan-name').textContent = plan ? plan.name : '-';
        document.getElementById('sub_review-plan-users').textContent = (plan ? (plan.included_users || 1) : '-') + ' مستخدم';
        document.getElementById('sub_review-plan-props').textContent = plan ? (plan.properties_limit !== null ? plan.properties_limit : 'غير محدود (∞)') : '-';
        document.getElementById('sub_review-plan-storage').textContent = plan ? (plan.storage_quota_gb !== null ? plan.storage_quota_gb + ' GB' : 'غير محدود (∞)') : '-';
        document.getElementById('sub_review-plan-auctions').textContent = plan ? (plan.active_auctions_limit !== null ? plan.active_auctions_limit : 'غير محدود (∞)') : '-';

        const featRev = document.getElementById('sub_review-plan-features');
        if (plan && Array.isArray(plan.features) && plan.features.length > 0) {
            featRev.innerHTML = plan.features.map(f => `<span class="badge bg-label-info">${f}</span>`).join(' ');
        } else {
            featRev.textContent = '-';
        }

        // Dates & Payments
        document.getElementById('sub_review-start-date').textContent = document.getElementById('sub_start_date')?.value || '-';
        document.getElementById('sub_review-end-date').textContent = document.getElementById('sub_end_date')?.value || '-';

        const isImmediate = document.getElementById('sub_activate_immediately')?.checked;
        const rAct = document.getElementById('sub_review-activation-status');
        if (rAct) {
            rAct.className = isImmediate ? 'badge bg-label-success' : 'badge bg-label-warning';
            rAct.textContent = isImmediate ? 'مفعل فوراً' : 'قيد المراجعة';
        }

        const payType = document.getElementById('sub_payment-status')?.value || '';
        const rPay = document.getElementById('sub_review-payment-status');
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

        const rMethod = document.getElementById('sub_review-payment-method');
        if (rMethod) {
            rMethod.textContent = payType === 'paid' && paymentMethodSelect && paymentMethodSelect.selectedIndex >= 0
                ? paymentMethodSelect.options[paymentMethodSelect.selectedIndex].text
                : '-';
        }

        // Calculate price
        const basePrice = plan ? parseFloat(plan.price_after_discount || plan.price || 0) : 0;
        const discountVal = discountType?.value === 'yes' ? parseFloat(document.getElementById('sub_discount_value')?.value || 0) : 0;
        const finalPrice = payType === 'trial' ? 0 : Math.max(0, basePrice - discountVal);
        document.getElementById('sub_review-final-total').textContent = formatNumber(finalPrice) + ' ر.س';
    }

    // Step navigation buttons
    const stepFields = {
        1: ['company_id', 'plan_id'],
        2: ['start_date', 'payment_type', 'payment_method', 'receipt_upload']
    };

    document.querySelectorAll('.btn-next').forEach(btn => {
        btn.addEventListener('click', async function () {
            const stepNum = parseInt(this.dataset.step, 10);
            const fieldsToValidate = stepFields[stepNum] || [];

            let isValid = true;
            for (const field of fieldsToValidate) {
                const status = await fv.revalidateField(field);
                if (status !== 'Valid') {
                    isValid = false;
                }
            }

            if (!isValid) return;

            if (stepNum === 2) {
                updateReviewSummary();
            }

            if (validationStepper) {
                validationStepper.next();
            }
        });
    });

    document.querySelectorAll('.btn-prev').forEach(btn => {
        btn.addEventListener('click', function () {
            if (validationStepper) {
                if (validationStepper._currentIndex === 0) {
                    window.history.back();
                } else {
                    validationStepper.previous();
                }
            }
        });
    });

    // Final Form Submission
    form.addEventListener('submit', function (e) {
        const submitBtn = document.getElementById('btn-submit-subscription');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري الحفظ والإنشاء...';
        }
    });

});
