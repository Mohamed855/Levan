'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const wizardValidation = document.querySelector('#wizard-validation');
    if (!wizardValidation) return;

    const nextButtons = wizardValidation.querySelectorAll('.btn-next');
    const prevButtons = wizardValidation.querySelectorAll('.btn-prev');
    const formEl = wizardValidation.querySelector('#wizard-validation-form');

    // تفعيل الـ Stepper إذا وُجد
    let validationStepper = null;
    if (typeof Stepper !== 'undefined') {
        validationStepper = new Stepper(wizardValidation, { linear: false });
    }

    // ==================== FORM VALIDATION ====================
    const fv = FormValidation.formValidation(formEl, {
        fields: {
            name: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال اسم الكوبون' },
                    stringLength: { min: 2, max: 255, message: 'الاسم يجب أن يكون بين 2 و 255 حرفاً' }
                }
            },
            code: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال كود الكوبون' },
                    stringLength: { min: 3, max: 50, message: 'الكود يجب أن يكون بين 3 و 50 حرفاً' }
                }
            },
            discount_type: {
                validators: {
                    notEmpty: { message: 'يرجى اختيار نوع الخصم' }
                }
            },
            discount_value: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال قيمة الخصم' },
                    numeric: { message: 'يجب أن تكون قيمة الخصم رقماً صالحاً' },
                    greaterThan: {
                        min: 0,
                        inclusive: false,
                        message: 'قيمة الخصم يجب أن تكون أكبر من 0'
                    },
                    callback: {
                        message: 'النسبة المئوية يجب أن تكون بين 1 و 100',
                        callback: function (input) {
                            const type = formEl.querySelector('#discount_type')?.value;
                            if (type === 'percentage') {
                                const v = parseFloat(input.value);
                                return v >= 1 && v <= 100;
                            }

                            return true;
                        }
                    }
                }
            },
            max_discount: {
                validators: {
                    callback: {
                        message: 'الحد الأقصى للخصم يجب أن يكون رقمًا موجبًا',
                        callback: function (input) {
                            const type = formEl.querySelector('#discount_type')?.value;
                            if (type === 'percentage' && input.value) {
                                const v = parseFloat(input.value);
                                return v >= 0;
                            }

                            return true;
                        }
                    }
                }
            },
            expiry_date: {
                validators: {
                    date: { format: 'YYYY-MM-DD', message: 'تاريخ غير صالح' }
                }
            },
            usage_limit: {
                validators: {
                    callback: {
                        message: 'حد الاستخدام يجب أن يكون رقمًا صحيحًا موجبًا',
                        callback: function (input) {
                            if (!input.value) return true;
                            const v = Number(input.value);
                            return Number.isInteger(v) && v > 0;
                        }
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

    // ==================== COUPON CODE GENERATOR ====================
    window.generateCouponCode = function () {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let code = '';
        for (let i = 0; i < 8; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }

        const codeInput = document.getElementById('code');
        if (codeInput) {
            codeInput.value = code;
            codeInput.dispatchEvent(new Event('input', { bubbles: true }));
            codeInput.dispatchEvent(new Event('change', { bubbles: true }));
            if (fv) {
                fv.revalidateField('code');
            }
        }

        const btn = document.querySelector('.btn-generate-code, #btn-generate-code');
        const icon = btn ? btn.querySelector('i') : null;
        if (icon) {
            icon.classList.add('ti-spin');
            setTimeout(() => icon.classList.remove('ti-spin'), 500);
        }

        return code;
    };
    window.generateCode = window.generateCouponCode;

    const codeInput = formEl.querySelector('#code');
    if (codeInput) {
        if (!codeInput.value.trim()) {
            window.generateCouponCode();
        }

        codeInput.addEventListener('input', function () {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.toUpperCase();
            this.setSelectionRange(start, end);
        });
    }

    const generateBtn = document.querySelector('#btn-generate-code, .btn-generate-code');
    if (generateBtn) {
        generateBtn.addEventListener('click', function (e) {
            e.preventDefault();
            window.generateCouponCode();
        });
    }

    // ==================== NAVIGATION (Stepper) ====================
    if (nextButtons.length && validationStepper) {
        nextButtons.forEach(btn => {
            btn.addEventListener('click', e => {
                const isSubmit = btn.type === 'submit' || btn.classList.contains('btn-submit');
                if (isSubmit) {
                    e.preventDefault();
                    fv.validate().then(status => {
                        if (status === 'Valid') {
                            formEl.submit();
                        }
                    });
                    return;
                }

                e.preventDefault();
                fv.validate().then(status => {
                    if (status === 'Valid') validationStepper.next();
                });
            });
        });

        if (prevButtons.length) {
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
        }
    }

    // ==================== FORM SUBMISSION ====================
    fv.on('core.form.valid', function () {
        formEl.submit();
    });

    // ==================== SELECT2 INITIALIZATION ====================
    if (window.jQuery) {
        const $ = window.jQuery;
        $('.select2').each(function () {
            const $this = $(this);
            $this.wrap('<div class="position-relative"></div>');
            $this
                .select2({
                    placeholder: $this.data('placeholder') || 'اختر خيارًا',
                    dropdownParent: $this.parent(),
                    allowClear: true,
                    width: '100%',
                    language: 'ar',
                    dir: 'rtl'
                })
                .on('change', function () {
                    fv.revalidateField($this.attr('name'));
                });
        });
    }

    // ==================== CLEANUP UNUSED REFERENCES ====================
    // تأكد من أن أي متغيرات لا تُستخدم لن تُسبب أخطاء
    window.checkRangeOverlap = () => true;
    window.recalcBaseYearly = () => { };
});
