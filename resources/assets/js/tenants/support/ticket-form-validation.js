'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const wizardValidation = document.querySelector('#wizard-validation');
    if (!wizardValidation) return;

    const formEl = wizardValidation.querySelector('#wizard-validation-form');

    // تفعيل Stepper
    let validationStepper = null;
    if (typeof Stepper !== 'undefined') {
        validationStepper = new Stepper(wizardValidation, { linear: false });
    }

    // تفعيل التحقق من النموذج
    const fv = FormValidation.formValidation(formEl, {
        fields: {
            subject: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال عنوان التذكرة' },
                    stringLength: { max: 255, message: 'العنوان طويل جدًا' },
                },
            },
            message: {
                validators: {
                    notEmpty: { message: 'يرجى كتابة وصف المشكلة' },
                    stringLength: { min: 10, message: 'الرسالة قصيرة جدًا' },
                },
            },
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                rowSelector: '.col-md-6, .col-12',
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
            submitButton: new FormValidation.plugins.SubmitButton(),
        },
    });

   fv.on('core.form.valid', function () {
    // الإرسال العادي عبر الفورم نفسها
    if (!formEl.hasAttribute('data-submitted')) {
        formEl.setAttribute('data-submitted', 'true');
        formEl.requestSubmit(); // أفضل من form.submit()
    }
});

    // تفعيل Select2
    if (window.jQuery) {
        const $ = window.jQuery;
        $('.select2').each(function () {
            const $this = $(this);
            $this.wrap('<div class="position-relative"></div>');
            $this.select2({
                placeholder: $this.data('placeholder') || 'اختر خيارًا',
                dropdownParent: $this.parent(),
                allowClear: true,
                width: '100%',
                language: 'ar',
                dir: 'rtl',
            });
        });
    }
});
