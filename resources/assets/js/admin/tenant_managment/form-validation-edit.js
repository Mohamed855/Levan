'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const wizardValidation = document.querySelector('#wizard-validation');
    if (!wizardValidation) return;

    const formEl = wizardValidation.querySelector('#wizard-validation-form');

    let validationStepper = null;
    if (typeof Stepper !== 'undefined') {
        validationStepper = new Stepper(wizardValidation, { linear: false });
    }

    // ==================== FORM VALIDATION ====================
    const fv = FormValidation.formValidation(formEl, {
        fields: {
            company_name: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال اسم المنشأة' },
                    stringLength: { min: 2, max: 255, message: 'اسم المنشأة يجب أن يكون بين 2 و 255 حرفاً' }
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
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: '',
                rowSelector: '.col-12, .col-md-6, .col-md-4'
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
            submitButton: new FormValidation.plugins.SubmitButton()
        }
    });

    // ==================== إعادة التحقق عند تغيير كلمة المرور ====================
    const passwordField = formEl.querySelector('[name="password"]');
    if (passwordField) {
        passwordField.addEventListener('input', function () {
            // إعادة التحقق من حقل تأكيد كلمة المرور عند تغيير كلمة المرور
            const confirmField = formEl.querySelector('[name="password_confirmation"]');
            if (confirmField && confirmField.value !== '') {
                fv.revalidateField('password_confirmation');
            }
        });
    }

    // ==================== FORM SUBMISSION ====================
    fv.on('core.form.valid', function () {
        // إزالة onSubmit="return false" من الفورم أو تفعيل الإرسال
        formEl.removeAttribute('onSubmit');
        formEl.submit();
    });

    // ==================== SELECT2 INITIALIZATION (إن وجدت) ====================
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
});
