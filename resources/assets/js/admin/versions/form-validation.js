'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const wizardValidation = document.querySelector('#wizard-validation');
    if (!wizardValidation) return;

    const nextButtons = wizardValidation.querySelectorAll('.btn-next');
    const prevButtons = wizardValidation.querySelectorAll('.btn-prev');
    const formEl = wizardValidation.querySelector('#wizard-validation-form');

    // ==================== INITIALIZE STEPPER ====================
    let validationStepper = null;
    if (typeof Stepper !== 'undefined') {
        validationStepper = new Stepper(wizardValidation, { linear: false });
    }

    // ==================== FORM VALIDATION ====================
    const fv = FormValidation.formValidation(formEl, {
        fields: {
            version: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال رقم الإصدارة' },
                    regexp: {
                        regexp: /^v?\d+\.\d+\.\d+$/,
                        message: 'يجب أن يكون التنسيق مثل v1.0.0 أو 2.3.1'
                    },
                    stringLength: { max: 50, message: 'رقم الإصدارة يجب ألا يتجاوز 50 حرفاً' }
                }
            },
            name: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال اسم الإصدارة' },
                    stringLength: { min: 2, max: 100, message: 'الاسم يجب أن يكون بين 2 و 100 حرفاً' }
                }
            },
            description: {
                validators: {
                    stringLength: { max: 500, message: 'الوصف يجب ألا يتجاوز 500 حرفاً' }
                }
            },
            changelog: {
                validators: {
                    stringLength: { max: 2000, message: 'سجل التغييرات يجب ألا يتجاوز 2000 حرفاً' }
                }
            },

            file_path: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال مسار الإصدارة' },
                    stringLength: { min: 2, max: 500, message: 'مسار الاصدارة يجب أن يكون بين 2 و 500 حرفاً' }
                }
            },

            stage: {
                validators: {
                    notEmpty: { message: 'يرجى اختيار مرحلة الإصدارة' }
                }
            },
            released_at: {
                validators: {
                    date: {
                        format: 'YYYY-MM-DD',
                        message: 'تاريخ الإصدار غير صالح'
                    }
                }
            },
            is_active: {
                validators: {
                    notEmpty: { message: 'يرجى تحديد حالة الإصدارة' }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: '',
                rowSelector: '.col-12, .col-6, .col-md-6, .col-md-3'
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
            submitButton: new FormValidation.plugins.SubmitButton()
        }
    });

    // ==================== NAVIGATION (Stepper) ====================
    if (nextButtons.length && prevButtons.length && validationStepper) {
        nextButtons.forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                fv.validate().then(status => {
                    if (status === 'Valid') validationStepper.next();
                });
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
});
