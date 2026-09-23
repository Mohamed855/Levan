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
        let fv = FormValidation.formValidation(formEl, {
            fields: {
                name: {
                    validators: {
                        notEmpty: { message: 'يرجى إدخال اسم الباقة' },
                        stringLength: { min: 2, max: 255, message: 'اسم الباقة يجب أن يكون بين 2 و 255 حرف' }
                    }
                },
                is_active: {
                    validators: {
                        notEmpty: { message: 'يرجى تحديد حالة الباقة' }
                    }
                },
                price: {
                    validators: {
                        notEmpty: { message: 'يرجى إدخال السعر ' },
                        numeric: { message: 'السعر  يجب أن يكون رقمًا صالحًا' },
                        greaterThan: { min: 0, inclusive: false, message: 'السعر  يجب أن يكون أكبر من 0' }
                    }
                },

                yearly_renewal_price: {
                    validators: {
                        notEmpty: { message: 'يرجى إدخال سعر التجديد السنوي ' },
                        numeric: { message: 'سعر التجديد السنوي  يجب أن يكون رقمًا صالحًا' },
                        greaterThan: { min: 0, inclusive: false, message: 'سعر التجديد السنوي  يجب أن يكون أكبر من 0' }
                    }
                },
                discount_percentage: {
                    validators: {
                        notEmpty: { message: 'يرجى إدخال نسبة الخصم ' },
                        callback: {
                            message: 'نسبة الخصم يجب أن تكون رقمًا بين 0 و 100',
                            callback: function (input) {
                                const value = input.value;
                                if (value === '' || value === null) {
                                    // الحقل اختياري
                                    return true;
                                }

                                const num = parseFloat(value);
                                if (isNaN(num)) return false;

                                return num >= 0 && num <= 100;
                            }
                        }
                    }
                },
                extra_user_license_price: {
                    validators: {
                        notEmpty: { message: 'يرجى إدخال سعر  ترخيص المستخدم الإضافي ' },
                        numeric: { message: 'سعر  ترخيص المستخدم الإضافي  يجب أن يكون رقمًا صالحًا' },
                        greaterThan: { min: 0, inclusive: false, message: 'سعر  ترخيص المستخدم الإضافي  يجب أن يكون أكبر من 0' }
                    }
                },

                included_users: {
                    validators: {
                        notEmpty: { message: 'يرجى إدخال عدد المستخدمين ' },
                        numeric: { message: 'عدد المستخدمين  يجب أن يكون رقمًا صالحًا' },
                        greaterThan: { min: 0, inclusive: false, message: 'عدد المستخدمين  يجب أن يكون أكبر من 0' }
                    }
                },
                branches_limit: {
                    validators: {
                        callback: {
                            message: 'يرجى إدخال عدد الفروع عندما يكون الحد محدودًا',
                            callback: function (input) {
                                const unlimited = document.getElementById('unlimited_branches').value;
                                if (unlimited === '0') {
                                    return input.value.trim() !== '' && parseInt(input.value, 10) > 0;
                                }

                                return true;
                            }
                        }
                    }
                },
                unlimited_branches: {
                    validators: {
                        notEmpty: { message: 'يرجى تحديد النوع   ' },
                    }
                },

                domain_management_enabled: {
                    validators: {
                        notEmpty: { message: 'يرجى تحديد حالة إدارة الدومينات   ' },
                    }
                },
                description: {
                    validators: {
                        stringLength: {
                            max: 1000,
                            message: 'الوصف يجب أن لا يتجاوز 1000 حرف'
                        }
                    }
                }
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    eleValidClass: '',
                    rowSelector: '.col-12, .col-6, .col-md-6, .col-md-3, .col-md-2, .col-md-10'
                }),
                autoFocus: new FormValidation.plugins.AutoFocus(),
                submitButton: new FormValidation.plugins.SubmitButton()
            }
        });

        // ==================== BRANCHES LIMIT TOGGLE ====================
        const unlimitedBranchesSelect = document.getElementById('unlimited_branches');
        const branchesLimitContainer = document.getElementById('branches_limit_container');
        const branchesLimitInput = document.getElementById('branches_limit');

        function toggleBranchesLimit() {
            if (!unlimitedBranchesSelect || !branchesLimitContainer || !branchesLimitInput) return;

            const value = unlimitedBranchesSelect.value;

            if (value === '1') {
                // غير محدود
                branchesLimitContainer.style.display = 'none';
                branchesLimitInput.removeAttribute('required');
                branchesLimitInput.value = '';
            } else if (value === '0') {
                // محدود
                branchesLimitContainer.style.display = 'block';
                branchesLimitInput.setAttribute('required', 'required');
            } else {
                // لم يتم الاختيار بعد
                branchesLimitContainer.style.display = 'none';
                branchesLimitInput.removeAttribute('required');
                branchesLimitInput.value = '';
            }

            // إعادة التحقق من حقل عدد الفروع عند تغيير النوع
            fv.revalidateField('branches_limit');
        }

        if (unlimitedBranchesSelect) {
            toggleBranchesLimit();
            unlimitedBranchesSelect.addEventListener('change', toggleBranchesLimit);

            if (typeof window.jQuery !== 'undefined') {
                window.jQuery(unlimitedBranchesSelect).on('change', toggleBranchesLimit);
            }
        }

        // ==================== FEATURES MANAGEMENT ====================
        const featuresList = document.getElementById('features-list');
        const addFeatureBtn = document.getElementById('btn-add-feature');
        let featureIndex = featuresList ? featuresList.querySelectorAll('.feature-item').length : 0;

        function addFeature() {
            const template = document.getElementById('feature-template');
            if (!template) return;
            const clone = template.content.cloneNode(true);
            const html = clone.querySelector('.feature-item').outerHTML.replace(/{index}/g, featureIndex);
            featuresList.insertAdjacentHTML('beforeend', html);
            addFeatureValidation(featureIndex);
            featureIndex++;
        }

        function addFeatureValidation(index) {
            const fieldName = `features[${index}]`;
            fv.addField(fieldName, {
                validators: {
                    notEmpty: { message: 'اسم الميزة مطلوب' },
                    stringLength: { min: 2, max: 255, message: 'اسم الميزة يجب أن يكون بين 2 و 255 حرف' }
                }
            });
        }

        function removeFeatureValidation(index) {
            const fieldName = `features[${index}]`;
            if (fv.fields[fieldName]) fv.removeField(fieldName);
        }

        if (addFeatureBtn && featuresList) {
            addFeatureBtn.addEventListener('click', addFeature);

            featuresList.addEventListener('click', function (e) {
                if (e.target.closest('.btn-remove-feature')) {
                    const item = e.target.closest('.feature-item');
                    if (!item) return;
                    const input = item.querySelector('input[name*="features"]');
                    if (input) {
                        const match = input.name.match(/features\[(\d+)\]/);
                        if (match) removeFeatureValidation(parseInt(match[1]));
                    }

                    item.remove();
                    if (featuresList.querySelectorAll('.feature-item').length === 0) addFeature();
                }
            });

            featuresList.querySelectorAll('input[name*="features"]').forEach(input => {
                const match = input.name.match(/features\[(\d+)\]/);
                if (match) addFeatureValidation(parseInt(match[1]));
            });

            if (featuresList.querySelectorAll('.feature-item').length === 0) addFeature();
        }

        // ==================== STEPPER NAVIGATION ====================
        nextButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const currentStep = validationStepper._currentIndex;

                if (currentStep === 0) {
                    const requiredFields = ['name', 'is_active', 'price', 'discount_percentage', 'yearly_renewal_price', 'extra_user_license_price', 'included_users', 'unlimited_branches', 'domain_management_enabled'];

                    if (!validateRequiredFields(requiredFields)) return;

                    // التحقق اليدوي من عدد الفروع عندما يكون النوع محدود
                    const unlimitedValue = unlimitedBranchesSelect ? unlimitedBranchesSelect.value : '';
                    if (unlimitedValue === '0') {
                        const branchesValue = branchesLimitInput ? branchesLimitInput.value.trim() : '';
                        const branchesInt = parseInt(branchesValue, 10);

                        if (!branchesValue || isNaN(branchesInt) || branchesInt <= 0) {
                            fv.updateFieldStatus('branches_limit', 'Invalid', 'callback');
                            fv.validateField('branches_limit');
                            return;
                        }
                    } else {
                        // إذا لم يكن محدود، نظف حالة التحقق
                        fv.resetField('branches_limit', true);
                    }

                    validateOptionalFields(['description']);
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

                    if (allFeaturesValid) validationStepper.next();
                }
            });
        });

        prevButtons.forEach(btn => btn.addEventListener('click', () => validationStepper.previous()));

        function validateRequiredFields(fields) {
            let allFilled = true;
            fields.forEach(fieldName => {
                const field = formEl.querySelector(`[name="${fieldName}"]`);
                if (!field || !field.value.trim()) {
                    allFilled = false;
                    fv.validateField(fieldName);
                }
            });
            return allFilled;
        }

        function validateOptionalFields(fields) {
            fields.forEach(fieldName => {
                const field = formEl.querySelector(`[name="${fieldName}"]`);
                if (field && field.value) fv.validateField(fieldName);
            });
        }

        // ==================== FORM SUBMISSION ====================
        fv.on('core.form.valid', function () {
            if (featuresList.querySelectorAll('.feature-item').length === 0) {
                alert('يجب إضافة ميزة واحدة على الأقل');
                validationStepper.to(1);
                return false;
            }

            formEl.submit();
        });

        // ==================== SELECT2 INITIALIZATION ====================
        const $select2 = (window.jQuery && window.jQuery('.select2')) || null;
        if ($select2 && $select2.length) {
            $select2.each(function () {
                const $this = window.jQuery(this);
                $this.wrap('<div class="position-relative"></div>');
                $this.select2({
                    placeholder: $this.data("placeholder") || "اختر خيارًا",
                    dropdownParent: $this.parent(),
                    allowClear: true,
                    width: '100%',
                    language: 'ar',
                    dir: 'rtl'
                }).on('change', function () {
                    fv.revalidateField($this.attr('name'));
                });
            });
        }
    })();
});
