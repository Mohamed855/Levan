'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const wizardValidation = document.querySelector('#wizard-validation');
    if (!wizardValidation) return;

    const formEl = wizardValidation.querySelector('#wizard-validation-form');

    let validationStepper = null;
    if (typeof Stepper !== 'undefined') {
        validationStepper = new Stepper(wizardValidation, { linear: false });
    }

    const fv = FormValidation.formValidation(formEl, {
        fields: {
            name: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال اسم الفرع' },
                    stringLength: { min: 2, max: 255, message: 'الاسم يجب أن يكون بين 2 و 255 حرفاً' }
                }
            },
            code: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال كود الفرع' },
                    stringLength: { min: 2, max: 50, message: 'الكود يجب أن يكون بين 2 و 50 حرفاً' },
                    regexp: {
                        regexp: /^[A-Z0-9]+$/,
                        message: 'الكود يجب أن يحتوي على أحرف كبيرة وأرقام فقط'
                    }
                }
            },
            phone: {
                validators: {
                    stringLength: { max: 20, message: 'رقم الهاتف يجب ألا يتجاوز 20 رقم' },
                    regexp: {
                        regexp: /^[0-9+\s-]*$/,
                        message: 'رقم الهاتف يجب أن يحتوي على أرقام فقط'
                    }
                }
            },
            city: {
                validators: {
                    stringLength: { max: 100, message: 'اسم المدينة يجب ألا يتجاوز 100 حرف' }
                }
            },
            address: {
                validators: {
                    stringLength: { max: 500, message: 'العنوان يجب ألا يتجاوز 500 حرف' }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: '',
                rowSelector: '.col-12, .col-6, .col-md-6'
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
            submitButton: new FormValidation.plugins.SubmitButton()
        }
    });

    fv.on('core.form.valid', function () {
        formEl.submit();
    });

    if (window.jQuery) {
        const $ = window.jQuery;
        $('.select2').each(function () {
            const $this = $(this);
            $this.wrap('<div class="position-relative"></div>');
            $this
                .select2({
                    placeholder: $this.data('placeholder') || 'اختر خياراً',
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