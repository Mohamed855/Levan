'use strict';

document.addEventListener('DOMContentLoaded', function () {
    (function () {
        const wizardValidation = document.querySelector('#wizard-validation');
        if (!wizardValidation) return;

        const nextButtons = [].slice.call(wizardValidation.querySelectorAll('.btn-next'));
        const prevButtons = [].slice.call(wizardValidation.querySelectorAll('.btn-prev'));
        const validationStepper = new Stepper(wizardValidation, { linear: false });
        const formEl = wizardValidation.querySelector('#wizard-validation-form');

        // ==================== FORM VALIDATION ====================
        const fv = FormValidation.formValidation(formEl, {
            fields: {
                name: {
                    validators: {
                        notEmpty: { message: 'يرجى إدخال اسم الباقة' },
                        stringLength: { min: 2, max: 255, message: 'اسم الباقة يجب أن يكون بين 2 و 255 حرفاً' }
                    }
                },
                code: {
                    validators: {
                        notEmpty: { message: 'يرجى إدخال كود الباقة' },
                        regexp: {
                            regexp: /^[A-Z][A-Z0-9_]*$/,
                            message: 'كود الباقة يجب أن يبدأ بحرف كبير ويحتوي على أحرف وأرقام وشرطة سفلية فقط'
                        },
                        stringLength: { max: 50, message: 'كود الباقة يجب ألا يتجاوز 50 حرفاً' }
                    }
                },
                plan_type: {
                    validators: {
                        notEmpty: { message: 'يرجى تحديد نوع الباقة' }
                    }
                },
                color: {
                    validators: {
                        notEmpty: { message: 'يرجى تحديد لون الباقة' }
                    }
                },
                is_active: {
                    validators: {
                        notEmpty: { message: 'يرجى تحديد حالة الباقة' }
                    }
                },
                subscription_billing_cycle: {
                    validators: {
                        notEmpty: { message: 'يرجى تحديد دورة الفوترة' }
                    }
                },
                price: {
                    validators: {
                        notEmpty: { message: 'يرجى إدخال السعر' },
                        numeric: { message: 'السعر يجب أن يكون رقماً صالحاً' },
                        greaterThan: { min: 0, inclusive: true, message: 'السعر يجب ألا يقل عن 0' }
                    }
                },
                discount_percentage: {
                    validators: {
                        callback: {
                            message: 'نسبة الخصم يجب أن تكون رقماً بين 0 و 100',
                            callback: function (input) {
                                const value = input.value;
                                if (value === '' || value === null) return true;
                                const num = parseFloat(value);
                                return !isNaN(num) && num >= 0 && num <= 100;
                            }
                        }
                    }
                },
                yearly_renewal_price: {
                    validators: {
                        callback: {
                            message: 'سعر العرض السنوي يجب أن يكون رقماً أكبر من أو يساوي 0',
                            callback: function (input) {
                                const value = input.value;
                                if (value === '' || value === null) return true;
                                const num = parseFloat(value);
                                return !isNaN(num) && num >= 0;
                            }
                        }
                    }
                },
                limits_mode: {
                    validators: {
                        notEmpty: { message: 'يرجى تحديد وضع الحدود' }
                    }
                },
                included_users: {
                    validators: {
                        callback: {
                            message: 'يرجى إدخال عدد المستخدمين المشمولين (1 على الأقل)',
                            callback: function (input) {
                                const limitsMode = document.getElementById('limits_mode')?.value || 'limited';
                                if (limitsMode === 'unlimited') return true;
                                const num = parseInt(input.value, 10);
                                return !isNaN(num) && num >= 1;
                            }
                        }
                    }
                },
                properties_limit: {
                    validators: {
                        callback: {
                            message: 'يرجى إدخال حد العقارات (1 على الأقل)',
                            callback: function (input) {
                                const limitsMode = document.getElementById('limits_mode')?.value || 'limited';
                                if (limitsMode === 'unlimited') return true;
                                const num = parseInt(input.value, 10);
                                return !isNaN(num) && num >= 1;
                            }
                        }
                    }
                },
                storage_quota_gb: {
                    validators: {
                        callback: {
                            message: 'يرجى إدخال حصة التخزين (1 جيجابايت على الأقل)',
                            callback: function (input) {
                                const limitsMode = document.getElementById('limits_mode')?.value || 'limited';
                                if (limitsMode === 'unlimited') return true;
                                const num = parseInt(input.value, 10);
                                return !isNaN(num) && num >= 1;
                            }
                        }
                    }
                },
                active_auctions_limit: {
                    validators: {
                        callback: {
                            message: 'يرجى إدخال حد المزادات النشطة (1 على الأقل)',
                            callback: function (input) {
                                const limitsMode = document.getElementById('limits_mode')?.value || 'limited';
                                if (limitsMode === 'unlimited') return true;
                                const num = parseInt(input.value, 10);
                                return !isNaN(num) && num >= 1;
                            }
                        }
                    }
                },
                branches_limit: {
                    validators: {
                        callback: {
                            message: 'يرجى إدخال حد الفروع المسموحة (1 على الأقل)',
                            callback: function (input) {
                                const limitsMode = document.getElementById('limits_mode')?.value || 'limited';
                                if (limitsMode === 'unlimited') return true;
                                const unlimited = document.getElementById('unlimited_branches')?.value;
                                if (unlimited === '1') return true;
                                const num = parseInt(input.value, 10);
                                return !isNaN(num) && num >= 1;
                            }
                        }
                    }
                },
                domain_management_enabled: {
                    validators: {
                        notEmpty: { message: 'يرجى تحديد حالة إدارة الدومينات' }
                    }
                },
                description: {
                    validators: {
                        stringLength: { max: 1000, message: 'الوصف يجب ألا يتجاوز 1000 حرف' }
                    }
                }
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    eleValidClass: '',
                    rowSelector: '.col-12, .col-md-6, .col-md-4, .col-md-3'
                }),
                autoFocus: new FormValidation.plugins.AutoFocus(),
                submitButton: new FormValidation.plugins.SubmitButton()
            }
        });

        // ==================== LIMITS MODE TOGGLE ====================
        const limitsModeSelect = document.getElementById('limits_mode');
        const limitsFields = document.querySelectorAll('.limits-field');
        const unlimitedAlert = document.getElementById('unlimited-mode-alert');

        function toggleLimitsMode() {
            if (!limitsModeSelect) return;
            const isUnlimited = limitsModeSelect.value === 'unlimited';

            limitsFields.forEach(el => {
                el.style.display = isUnlimited ? 'none' : 'block';
            });

            if (unlimitedAlert) {
                unlimitedAlert.style.display = isUnlimited ? 'block' : 'none';
            }

            // Revalidate fields
            ['included_users', 'properties_limit', 'storage_quota_gb', 'active_auctions_limit', 'branches_limit'].forEach(field => {
                if (fv.fields[field]) {
                    fv.resetField(field, true);
                }
            });
        }

        if (limitsModeSelect) {
            limitsModeSelect.addEventListener('change', toggleLimitsMode);
            if (window.jQuery) {
                window.jQuery(limitsModeSelect).on('change', toggleLimitsMode);
            }
            toggleLimitsMode();
        }

        // ==================== BRANCHES LIMIT TOGGLE ====================
        const unlimitedBranchesSelect = document.getElementById('unlimited_branches');
        const branchesLimitContainer = document.getElementById('branches_limit_container');

        function toggleBranchesLimit() {
            if (!unlimitedBranchesSelect || !branchesLimitContainer) return;
            const isUnlimited = unlimitedBranchesSelect.value === '1';
            branchesLimitContainer.style.display = isUnlimited ? 'none' : 'block';
            if (fv.fields['branches_limit']) {
                fv.resetField('branches_limit', true);
            }
        }

        if (unlimitedBranchesSelect) {
            unlimitedBranchesSelect.addEventListener('change', toggleBranchesLimit);
            if (window.jQuery) {
                window.jQuery(unlimitedBranchesSelect).on('change', toggleBranchesLimit);
            }
            toggleBranchesLimit();
        }

        // ==================== CODE UPPERCASE FORMATTING ====================
        const codeInput = document.getElementById('code');
        if (codeInput) {
            codeInput.addEventListener('input', function () {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9_]/g, '');
            });
        }

        // ==================== FEATURES MANAGEMENT ====================
        const featuresList = document.getElementById('features-list');
        const addFeatureBtn = document.getElementById('btn-add-feature');
        let featureIndex = featuresList ? featuresList.querySelectorAll('.feature-item').length : 0;

        function addFeatureValidation(index) {
            const fieldName = `features[${index}]`;
            fv.addField(fieldName, {
                validators: {
                    notEmpty: { message: 'نص الميزة لا يمكن أن يكون فارغاً' },
                    stringLength: { max: 255, message: 'نص الميزة يجب ألا يتجاوز 255 حرفاً' }
                }
            });
        }

        function removeFeatureValidation(index) {
            const fieldName = `features[${index}]`;
            if (fv.fields[fieldName]) fv.removeField(fieldName);
        }

        function addFeature(value = '') {
            const template = document.getElementById('feature-template');
            if (!template || !featuresList) return;
            const clone = template.content.cloneNode(true);
            const html = clone.querySelector('.feature-item').outerHTML.replace(/{index}/g, featureIndex);
            featuresList.insertAdjacentHTML('beforeend', html);
            featuresList.lastElementChild.querySelector('input[name*="features"]').value = value;
            addFeatureValidation(featureIndex);
            featureIndex++;
        }

        function restoreFeatures(values = []) {
            if (!featuresList) return;
            featuresList.querySelectorAll('.feature-item').forEach(item => {
                const input = item.querySelector('input[name*="features"]');
                if (input) {
                    const match = input.name.match(/features\[(\d+)\]/);
                    if (match) removeFeatureValidation(parseInt(match[1], 10));
                }
                item.remove();
            });

            featureIndex = 0;
            if (values.length > 0) {
                values.forEach(val => addFeature(val));
            } else {
                addFeature();
            }
        }

        if (addFeatureBtn && featuresList) {
            addFeatureBtn.addEventListener('click', () => addFeature());

            featuresList.addEventListener('click', function (e) {
                if (e.target.closest('.btn-remove-feature')) {
                    const item = e.target.closest('.feature-item');
                    if (!item) return;
                    const input = item.querySelector('input[name*="features"]');
                    if (input) {
                        const match = input.name.match(/features\[(\d+)\]/);
                        if (match) removeFeatureValidation(parseInt(match[1], 10));
                    }
                    item.remove();
                    if (featuresList.querySelectorAll('.feature-item').length === 0) addFeature();
                }
            });

            featuresList.querySelectorAll('input[name*="features"]').forEach(input => {
                const match = input.name.match(/features\[(\d+)\]/);
                if (match) addFeatureValidation(parseInt(match[1], 10));
            });

            if (featuresList.querySelectorAll('.feature-item').length === 0) addFeature();
        }

        // ==================== LIVE PREVIEW IN STEP 3 ====================
        function updateReviewCard() {
            const name = document.getElementById('name')?.value || '-';
            const code = document.getElementById('code')?.value || '-';

            const planTypeSelect = document.getElementById('plan_type');
            const planTypeLabel = planTypeSelect && planTypeSelect.options[planTypeSelect.selectedIndex] ? planTypeSelect.options[planTypeSelect.selectedIndex].text : '-';

            const billingCycleSelect = document.getElementById('subscription_billing_cycle');
            const billingCycleLabel = billingCycleSelect && billingCycleSelect.options[billingCycleSelect.selectedIndex] ? billingCycleSelect.options[billingCycleSelect.selectedIndex].text : '-';

            const limitsModeVal = document.getElementById('limits_mode')?.value || 'limited';
            const isUnlimited = limitsModeVal === 'unlimited';

            const price = parseFloat(document.getElementById('price')?.value || '0');
            const discount = parseFloat(document.getElementById('discount_percentage')?.value || '0');
            const finalPrice = discount > 0 ? (price - (price * discount / 100)) : price;

            const yearlyRenewal = document.getElementById('yearly_renewal_price')?.value;
            const annualOfferText = yearlyRenewal && parseFloat(yearlyRenewal) > 0 ? `${parseFloat(yearlyRenewal).toFixed(2)} ر.س` : 'غير متوفر';

            const includedUsers = document.getElementById('included_users')?.value || '-';
            const propertiesLimit = document.getElementById('properties_limit')?.value || '-';
            const storageQuota = document.getElementById('storage_quota_gb')?.value || '-';
            const auctionsLimit = document.getElementById('active_auctions_limit')?.value || '-';

            const unlimitedBranchesVal = document.getElementById('unlimited_branches')?.value;
            const branchesLimit = document.getElementById('branches_limit')?.value || '1';
            const branchesText = isUnlimited || unlimitedBranchesVal === '1' ? '∞ غير محدود' : `${branchesLimit} فرع`;

            const domainSelect = document.getElementById('domain_management_enabled');
            const domainsText = domainSelect?.value === '1' ? 'مفعّلة' : 'غير مفعّلة';

            const availableFrom = document.getElementById('available_from')?.value;
            const availableText = availableFrom ? availableFrom : 'متاحة فوراً';

            // Populate DOM
            const nameEl = document.getElementById('review-plan-name');
            if (nameEl) nameEl.textContent = name;

            const codeEl = document.getElementById('review-plan-code');
            if (codeEl) codeEl.textContent = code;

            const typeEl = document.getElementById('review-plan-type');
            if (typeEl) typeEl.textContent = planTypeLabel;

            const cycleEl = document.getElementById('review-billing-cycle');
            if (cycleEl) cycleEl.textContent = `دورة: ${billingCycleLabel}`;

            const limitsModeEl = document.getElementById('review-limits-mode');
            if (limitsModeEl) limitsModeEl.textContent = isUnlimited ? 'غير محدود (∞)' : 'محدود';

            const finalPriceEl = document.getElementById('review-final-price');
            if (finalPriceEl) finalPriceEl.textContent = `${finalPrice.toFixed(2)} ر.س`;

            const priceBreakdownEl = document.getElementById('review-price-breakdown');
            if (priceBreakdownEl) {
                priceBreakdownEl.textContent = discount > 0
                    ? `السعر الأساسي: ${price.toFixed(2)} ر.س (خصم ${discount}%)`
                    : `السعر الأساسي: ${price.toFixed(2)} ر.س`;
            }

            const annualOfferEl = document.getElementById('review-annual-offer');
            if (annualOfferEl) annualOfferEl.textContent = annualOfferText;

            const usersEl = document.getElementById('review-users');
            if (usersEl) usersEl.textContent = isUnlimited ? '∞ غير محدود' : `${includedUsers} مستخدم`;

            const propsEl = document.getElementById('review-properties');
            if (propsEl) propsEl.textContent = isUnlimited ? '∞ غير محدود' : `${propertiesLimit} عقار`;

            const storageEl = document.getElementById('review-storage');
            if (storageEl) storageEl.textContent = isUnlimited ? '∞ غير محدود' : `${storageQuota} GB`;

            const auctionsEl = document.getElementById('review-auctions');
            if (auctionsEl) auctionsEl.textContent = isUnlimited ? '∞ غير محدود' : `${auctionsLimit} مزاد`;

            const branchesEl = document.getElementById('review-branches');
            if (branchesEl) branchesEl.textContent = branchesText;

            const domainsEl = document.getElementById('review-domains');
            if (domainsEl) domainsEl.textContent = domainsText;

            const availableFromEl = document.getElementById('review-available-from');
            if (availableFromEl) availableFromEl.textContent = availableText;

            // Features list
            const reviewFeaturesUl = document.getElementById('review-features-list');
            if (reviewFeaturesUl && featuresList) {
                reviewFeaturesUl.innerHTML = '';
                featuresList.querySelectorAll('input[name*="features"]').forEach(input => {
                    if (input.value.trim()) {
                        const li = document.createElement('li');
                        li.textContent = input.value.trim();
                        reviewFeaturesUl.appendChild(li);
                    }
                });
            }

            // Publication Alert
            const isActive = document.getElementById('is_active')?.value === '1';
            const alertBox = document.getElementById('publish-status-alert');
            const alertText = document.getElementById('publish-status-text');
            const alertIcon = document.getElementById('publish-status-icon');

            if (alertBox && alertText && alertIcon) {
                if (!isActive) {
                    alertBox.className = 'alert alert-secondary d-flex align-items-center mb-0';
                    alertIcon.className = 'ti ti-eye-off fs-3 me-2';
                    alertText.textContent = 'الباقة غير مفعلة ولن تظهر للعملاء في صفحات التسجيل أو الـ Checkout حتى يتم تفعيلها.';
                } else if (availableFrom && new Date(availableFrom) > new Date()) {
                    alertBox.className = 'alert alert-info d-flex align-items-center mb-0';
                    alertIcon.className = 'ti ti-clock fs-3 me-2';
                    alertText.textContent = `الباقة مفعلة ومجدولة للإتاحة تلقائياً في تاريخ: ${availableFrom}. لن تظهر قبل هذا التاريخ.`;
                } else {
                    alertBox.className = 'alert alert-success d-flex align-items-center mb-0';
                    alertIcon.className = 'ti ti-check-circle fs-3 me-2';
                    alertText.textContent = 'الباقة نشطة ومتاحة الآن، ستظهر للعملاء في قوائم الاختيار والـ Checkout فور النشر.';
                }
            }
        }

        // ==================== STEPPER NAVIGATION ====================
        nextButtons.forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                const currentStep = validationStepper._currentIndex;

                if (currentStep === 0) {
                    const fieldsToValidate = [
                        'name',
                        'code',
                        'plan_type',
                        'color',
                        'is_active',
                        'subscription_billing_cycle',
                        'price',
                        'limits_mode',
                        'domain_management_enabled',
                    ];

                    const isUnlimited = document.getElementById('limits_mode')?.value === 'unlimited';
                    if (!isUnlimited) {
                        fieldsToValidate.push('included_users', 'properties_limit', 'storage_quota_gb', 'active_auctions_limit');
                        if (document.getElementById('unlimited_branches')?.value === '0') {
                            fieldsToValidate.push('branches_limit');
                        }
                    }

                    let allValid = true;
                    fieldsToValidate.forEach(fieldName => {
                        const field = formEl.querySelector(`[name="${fieldName}"]`);
                        if (!field || !field.value.trim()) {
                            allValid = false;
                            fv.validateField(fieldName);
                        }
                    });

                    if (!allValid) return;
                    validationStepper.next();
                } else if (currentStep === 1) {
                    const features = featuresList.querySelectorAll('.feature-item');
                    if (features.length === 0) {
                        alert('يجب إضافة ميزة واحدة على الأقل');
                        return;
                    }

                    let allFeaturesValid = true;
                    features.forEach(feature => {
                        const input = feature.querySelector('input[name*="features"]');
                        if (input && input.name) {
                            if (!input.value.trim()) {
                                allFeaturesValid = false;
                                fv.validateField(input.name);
                            }
                        }
                    });

                    if (!allFeaturesValid) return;

                    updateReviewCard();
                    validationStepper.next();
                }
            });
        });

        prevButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.tagName.toLowerCase() === 'a' || btn.getAttribute('href')) {
                    return;
                }
                if (validationStepper && validationStepper._currentIndex === 0) {
                    window.history.back();
                } else if (validationStepper) {
                    validationStepper.previous();
                }
            });
        });

        // ==================== WIZARD DRAFT INTEGRATION ====================
        window.wizardCreateHooks = window.wizardCreateHooks || {};
        window.wizardCreateHooks[formEl.id] = {
            beforeRestoreDraft({ values, localValues }) {
                const localFeatures = Object.entries(localValues || {})
                    .filter(([name]) => /^features\[\d+\]$/.test(name))
                    .sort(([first], [second]) => first.localeCompare(second, undefined, { numeric: true }))
                    .map(([, value]) => value);

                restoreFeatures(localFeatures.length > 0 ? localFeatures : (values.features || []));
            }
        };

        // ==================== SELECT2 INITIALIZATION ====================
        if (window.jQuery && window.jQuery('.select2').length) {
            window.jQuery('.select2').each(function () {
                const $this = window.jQuery(this);
                $this.wrap('<div class="position-relative"></div>');
                $this.select2({
                    placeholder: $this.data('placeholder') || 'اختر خياراً',
                    dropdownParent: $this.parent(),
                    allowClear: true,
                    width: '100%',
                    language: 'ar',
                    dir: 'rtl'
                }).on('change', function () {
                    if (fv.fields[$this.attr('name')]) {
                        fv.revalidateField($this.attr('name'));
                    }
                    this.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        }
    })();
});
