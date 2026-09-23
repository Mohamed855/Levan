'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const trialForm = document.querySelector('#trial-form');
    if (!trialForm) return;

    // تهيئة FormValidation
    const fv = FormValidation.formValidation(trialForm, {
        fields: {
            fullname: {
                validators: {
                    notEmpty: {
                        message: 'الاسم الكامل مطلوب'
                    },
                    stringLength: {
                        min: 3,
                        max: 100,
                        message: 'الاسم يجب أن يكون بين 3 و 100 حرف'
                    },
                    regexp: {
                        regexp: /^[\u0621-\u064Aa-zA-Z\s]+$/,
                        message: 'الاسم يجب أن يحتوي على أحرف فقط'
                    }
                }
            },
            email: {
                validators: {
                    notEmpty: {
                        message: 'البريد الإلكتروني مطلوب'
                    },
                    emailAddress: {
                        message: 'البريد الإلكتروني غير صالح'
                    }
                }
            },
            phone: {
                validators: {
                    notEmpty: {
                        message: 'رقم الجوال مطلوب'
                    },
                    regexp: {
                        regexp: /^(05|5)[0-9]{8}$/,
                        message: 'رقم الجوال غير صالح (يجب أن يبدأ بـ 05 ويتكون من 10 أرقام)'
                    }
                }
            },
            // city: {
            //     validators: {
            //         notEmpty: {
            //             message: 'المدينة مطلوبة'
            //         },
            //         stringLength: {
            //             min: 2,
            //             max: 50,
            //             message: 'المدينة يجب أن تكون بين 2 و 50 حرف'
            //         }
            //     }
            // }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: '',
                rowSelector: function (field, ele) {
                    return '.col-md-6, .col-md-3, .col-12';
                }
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
            submitButton: new FormValidation.plugins.SubmitButton()
        }
    });

    // عند نجاح التحقق من النموذج
    fv.on('core.form.valid', function () {
        const submitBtn = trialForm.querySelector('button[type="submit"]');

        // عرض رسالة تحميل
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري المعالجة...';

        // إرسال النموذج
        trialForm.submit();
    });

    // تحديث التحقق عند تغيير الحقول
    const allInputs = trialForm.querySelectorAll('input');
    allInputs.forEach(function (input) {
        input.addEventListener('blur', function () {
            if (this.value) {
                fv.revalidateField(this.getAttribute('name') || this.id);
            }
        });
    });

    // Cleave.js لتنسيق رقم الجوال فقط
    if (typeof Cleave !== 'undefined') {
        const phone = document.getElementById('phone');
        if (phone) {
            new Cleave(phone, {
                numericOnly: true,
                blocks: [2, 3, 5],
                delimiter: ''
            });
        }
    }
});
