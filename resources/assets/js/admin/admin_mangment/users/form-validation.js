'use strict';

document.addEventListener('DOMContentLoaded', function (e) {
    (function () {
        const wizardValidation = document.querySelector('#wizard-validation'),
            wizardValidationBtnNextList = [].slice.call(wizardValidation.querySelectorAll('.btn-next')),
            wizardValidationBtnPrevList = [].slice.call(wizardValidation.querySelectorAll('.btn-prev'));

        if (typeof wizardValidation !== undefined && wizardValidation !== null) {
            const validationStepper = new Stepper(wizardValidation, {
                linear: false
            });

            // تحديد ما إذا كانت الصفحة للإنشاء أم التعديل
            const isEditMode = document.querySelector('input[name="_method"]') !== null;

            // التحقق للخطوة الأولى - المعلومات الأساسية
            const FormValidation1 = FormValidation.formValidation(wizardValidation, {
                fields: {
                    name: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى إدخال الاسم الكامل'
                            },
                            stringLength: {
                                min: 2,
                                max: 255,
                                message: 'الاسم يجب أن يكون بين 2 و 255 حرف'
                            }
                        }
                    },
                    employee_number: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى إدخال الرقم الوظيفي'
                            },
                            stringLength: {
                                min: 1,
                                max: 50,
                                message: 'الرقم الوظيفي يجب أن يكون بين 1 و 50 حرف'
                            },
                            regexp: {
                                regexp: /^[A-Za-z0-9]+$/,
                                message: 'الرقم الوظيفي يجب أن يحتوي على أحرف إنجليزية وأرقام فقط'
                            }
                        }
                    },
                    email: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى إدخال البريد الإلكتروني'
                            },
                            emailAddress: {
                                message: 'يرجى إدخال بريد إلكتروني صحيح'
                            }
                        }
                    },
                    phone: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى إدخال رقم الهاتف'
                            },
                            stringLength: {
                                min: 10,
                                max: 10,
                                message: 'رقم الهاتف يجب أن يكون 10 أرقام بالضبط'
                            }
                        }
                    },
                    branch_id: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى اختيار الفرع'
                            }
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap5: new FormValidation.plugins.Bootstrap5({
                        eleValidClass: '',
                        rowSelector: '.col-sm-6, .col-12'
                    }),
                    autoFocus: new FormValidation.plugins.AutoFocus(),
                    submitButton: new FormValidation.plugins.SubmitButton()
                }
            }).on('core.form.valid', function () {
                validationStepper.next();
            });

            // إعداد قواعد التحقق من كلمة المرور حسب نوع الصفحة
            let passwordValidationFields = {};

            if (isEditMode) {
                // في وضع التعديل - كلمة المرور اختيارية
                passwordValidationFields = {
                    password: {
                        validators: {
                            callback: {
                                message: 'كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل',
                                callback: function (input) {
                                    const value = input.value;
                                    // إذا كان الحقل فارغ، السماح بذلك
                                    if (value === '') {
                                        return true;
                                    }

                                    // إذا كان هناك قيمة، التحقق من الطول
                                    return value.length >= 8;
                                }
                            }
                        }
                    },
                    password_confirmation: {
                        validators: {
                            callback: {
                                message: 'كلمة المرور وتأكيدها غير متطابقتين',
                                callback: function (input) {
                                    const password = wizardValidation.querySelector('[name="password"]').value;
                                    const confirmation = input.value;

                                    // إذا كانت كلمة المرور فارغة، يجب أن يكون التأكيد فارغ أيضاً
                                    if (password === '' && confirmation === '') {
                                        return true;
                                    }

                                    // إذا كان هناك كلمة مرور، يجب أن يتطابق التأكيد
                                    if (password !== '' && confirmation === '') {
                                        return {
                                            valid: false,
                                            message: 'يرجى تأكيد كلمة المرور'
                                        };
                                    }

                                    return password === confirmation;
                                }
                            }
                        }
                    }
                };
            } else {
                // في وضع الإنشاء - كلمة المرور مطلوبة
                passwordValidationFields = {
                    password: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى إدخال كلمة المرور'
                            },
                            stringLength: {
                                min: 8,
                                message: 'كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل'
                            }
                        }
                    },
                    password_confirmation: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى تأكيد كلمة المرور'
                            },
                            identical: {
                                compare: function () {
                                    return wizardValidation.querySelector('[name="password"]').value;
                                },
                                message: 'كلمة المرور وتأكيدها غير متطابقتين'
                            }
                        }
                    }
                };
            }

            // التحقق للخطوة الثانية - كلمة المرور والصورة
            const FormValidation2 = FormValidation.formValidation(wizardValidation, {
                fields: {
                    ...passwordValidationFields,
                    image: {
                        validators: {
                            file: {
                                extension: 'jpeg,jpg,png,gif',
                                type: 'image/jpeg,image/jpg,image/png,image/gif',
                                maxSize: 2097152,
                                message: 'يرجى اختيار صورة صالحة (JPG, PNG, GIF) بحجم أقل من 2MB'
                            }
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap5: new FormValidation.plugins.Bootstrap5({
                        eleValidClass: '',
                        rowSelector: '.col-sm-6, .col-12'
                    }),
                    autoFocus: new FormValidation.plugins.AutoFocus(),
                    submitButton: new FormValidation.plugins.SubmitButton()
                }
            }).on('core.form.valid', function () {
                const form = wizardValidation.querySelector('#wizard-validation-form');
                if (form) {
                    form.submit();
                }
            });

            // أزرار التالي
            wizardValidationBtnNextList.forEach(function (wizardValidationBtnNext) {
                wizardValidationBtnNext.addEventListener('click', function (e) {
                    const currentStep = validationStepper._currentIndex;

                    switch (currentStep) {
                        case 0:
                            FormValidation1.validate();
                            break;
                        case 1:
                            FormValidation2.validate();
                            break;
                        default:
                            break;
                    }
                });
            });

            // أزرار السابق
            wizardValidationBtnPrevList.forEach(function (wizardValidationBtnPrev) {
                wizardValidationBtnPrev.addEventListener('click', function (e) {
                    switch (validationStepper._currentIndex) {
                        case 1:
                            validationStepper.previous();
                            break;
                        case 0:
                        default:
                            console.log('first step');
                            break;
                    }
                });
            });

            // إعادة التحقق عند تغيير كلمة المرور
            const passwordInput = wizardValidation.querySelector('[name="password"]');
            const passwordConfirmationInput = wizardValidation.querySelector('[name="password_confirmation"]');

            if (passwordInput) {
                passwordInput.addEventListener('input', function () {
                    if (FormValidation2.getFields().password_confirmation) {
                        FormValidation2.revalidateField('password_confirmation');
                    }
                });
            }

            if (passwordConfirmationInput) {
                passwordConfirmationInput.addEventListener('input', function () {
                    if (FormValidation2.getFields().password) {
                        FormValidation2.revalidateField('password');
                    }
                });
            }
        }
    })();
});

// وظائف إضافية
$(document).ready(function () {
    $('.select2').each(function () {
        $(this).select2({
            placeholder: function () {
                return $(this).data('placeholder');
            },
            allowClear: true,
            width: '100%',
            language: 'ar',
            dir: 'rtl'
        }).on('select2:select select2:clear', function () {
            // إزالة رسائل الخطأ عند الاختيار
            $(this).removeClass('is-invalid');
            $(this).closest('.col-sm-6, .col-sm-12, .col-12').find('.invalid-feedback').remove();
        });
    });

    // تقييد الأرقام فقط لحقل الهاتف وحد أقصى 10 أرقام
    $('#phone').on('input', function () {
        // السماح بالأرقام فقط
        this.value = this.value.replace(/[^0-9]/g, '');

        // تقييد الطول إلى 10 أرقام كحد أقصى
        if (this.value.length > 10) {
            this.value = this.value.substring(0, 10);
        }
    });

    // معاينة الصورة
    $('#upload').on('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#uploadedAvatar').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    // إظهار/إخفاء كلمة المرور
    $('.input-group-text').on('click', function () {
        const input = $(this).prev('input');
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('ti-eye-off').addClass('ti-eye');
        } else {
            input.attr('type', 'password');
            icon.removeClass('ti-eye').addClass('ti-eye-off');
        }
    });

    // إضافي: تنبيه المستخدم في وضع التعديل
    const isEditMode = $('input[name="_method"]').length > 0;
    if (isEditMode) {
        // إضافة تلميح بصري للمستخدم
        const passwordLabel = $('label[for="password"]');
        if (passwordLabel.length && !passwordLabel.find('.text-muted').length) {
            passwordLabel.append(' <small class="text-muted">(اختياري - اتركه فارغاً لعدم التغيير)</small>');
        }

        const confirmationLabel = $('label[for="password_confirmation"]');
        if (confirmationLabel.length && !confirmationLabel.find('.text-muted').length) {
            confirmationLabel.append(' <small class="text-muted">(اختياري)</small>');
        }
    }
});
