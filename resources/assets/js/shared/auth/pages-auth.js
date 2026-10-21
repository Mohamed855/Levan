'use strict';
const formAuthentication = document.querySelector('#formAuthentication');

document.addEventListener('DOMContentLoaded', function(e) {
    (function() {
        // Form validation for Add new record
        if (formAuthentication) {
            const fv = FormValidation.formValidation(formAuthentication, {
                fields: {
                    username: {
                        validators: {
                            notEmpty: {
                                message: 'الرجاء إدخال اسم المستخدم'
                            },
                            stringLength: {
                                min: 6,
                                message: 'يجب أن يكون اسم المستخدم أكثر من 6 أحرف'
                            }
                        }
                    },
                    email: {
                        validators: {
                            notEmpty: {
                                message: 'الرجاء إدخال بريدك الإلكتروني'
                            },
                            emailAddress: {
                                message: 'الرجاء إدخال عنوان بريد إلكتروني صحيح'
                            }
                        }
                    },
                    'email-username': {
                        validators: {
                            notEmpty: {
                                message: 'الرجاء إدخال البريد الإلكتروني أو اسم المستخدم'
                            },
                            stringLength: {
                                min: 6,
                                message: 'يجب أن يكون اسم المستخدم أكثر من 6 أحرف'
                            }
                        }
                    },
                    password: {
                        validators: {
                            notEmpty: {
                                message: 'الرجاء إدخال كلمة المرور'
                            },
                            stringLength: {
                                min: 6,
                                message: 'يجب أن تكون كلمة المرور أكثر من 6 أحرف'
                            }
                        }
                    },
                    'confirm-password': {
                        validators: {
                            notEmpty: {
                                message: 'الرجاء تأكيد كلمة المرور'
                            },
                            identical: {
                                compare: function() {
                                    return formAuthentication.querySelector('[name="password"]').value;
                                },
                                message: 'كلمة المرور وتأكيدها غير متطابقين'
                            },
                            stringLength: {
                                min: 6,
                                message: 'يجب أن تكون كلمة المرور أكثر من 6 أحرف'
                            }
                        }
                    },
                    terms: {
                        validators: {
                            notEmpty: {
                                message: 'الرجاء الموافقة على الشروط والأحكام'
                            }
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap5: new FormValidation.plugins.Bootstrap5({
                        eleValidClass: '',
                        rowSelector: '.mb-6'
                    }),
                    submitButton: new FormValidation.plugins.SubmitButton(),

                    defaultSubmit: new FormValidation.plugins.DefaultSubmit(),
                    autoFocus: new FormValidation.plugins.AutoFocus()
                },
                init: instance => {
                    instance.on('plugins.message.placed', function(e) {
                        if (e.element.parentElement.classList.contains('input-group')) {
                            e.element.parentElement.insertAdjacentElement('afterend', e.messageElement);
                        }
                    });
                }
            });
        }

        //  Two Steps Verification
        const numeralMask = document.querySelectorAll('.numeral-mask');

        // Verification masking
        if (numeralMask.length) {
            numeralMask.forEach(e => {
                new Cleave(e, {
                    numeral: true
                });
            });
        }
    })();
});