'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const wizardValidation = document.querySelector('#wizard-validation');
    if (!wizardValidation) return;

    const formEl = wizardValidation.querySelector('#wizard-validation-form');
    if (!formEl) return;

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
                    notEmpty: { message: 'يرجى إدخال اسم العميل' },
                    stringLength: { min: 2, max: 255, message: 'الاسم يجب أن يكون بين 2 و 255 حرفاً' }
                }
            },
            
            status: {
                validators: {
                    notEmpty: { message: 'يرجى اختيار الحالة' }
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

    // ==================== FORM SUBMISSION ====================
    fv.on('core.form.valid', function () {
        formEl.submit();
    });
});
