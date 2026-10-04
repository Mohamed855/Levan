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
            tenant_id: {
                validators: {
                    notEmpty: { message: 'يرجى اختيار العميل (التينانت)' }
                }
            },
            type: {
                validators: {
                    notEmpty: { message: 'يرجى تحديد نوع العضو في فريق الاختبار' }
                }
            },
            is_active: {
                validators: {
                    notEmpty: { message: 'يرجى تحديد حالة العضو' }
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
