/**
 * Form Wizard Validation for Customers (4 Steps)
 * Updated with flexible phone validation
 */

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

            // التحقق للخطوة الأولى - المعلومات الشخصية
            const FormValidation1 = FormValidation.formValidation(wizardValidation, {
                fields: {
                    name: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى إدخال اسم العميل'
                            },
                            stringLength: {
                                min: 2,
                                max: 255,
                                message: 'اسم العميل يجب أن يكون بين 2 و 255 حرف'
                            }
                        }
                    },
                    first_name: {
                        validators: {
                            notEmpty: { message: 'يرجى إدخال الاسم الأول' },
                            stringLength: { max: 255, message: 'أقصى حد 255 حرف' }
                        },
                        enabled: false
                    },
                    family_name: {
                        validators: {
                            notEmpty: { message: 'يرجى إدخال اسم العائلة' },
                            stringLength: { max: 255, message: 'أقصى حد 255 حرف' }
                        },
                        enabled: false
                    },
                    customer_type: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى اختيار نوع العميل'
                            }
                        }
                    },
                    identity_number: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى إدخال رقم الهوية/رقم الجواز'
                            },
                            stringLength: {
                                min: 5,
                                max: 15,
                                message: 'يجب أن يتكون رقم الهوية/رقم الجواز من 5 الى 15 رقم -حرف'
                            }
                        }
                    },
                    company_id: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى اختيار الشركة'
                            }
                        }
                    },
                    portfolio_id: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى اختيار المحفظة'
                            }
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap5: new FormValidation.plugins.Bootstrap5({
                        eleValidClass: '',
                        rowSelector: '.col-sm-6, .col-sm-12, .col-12'
                    }),
                    autoFocus: new FormValidation.plugins.AutoFocus(),
                    submitButton: new FormValidation.plugins.SubmitButton()
                }
            }).on('core.form.valid', function () {
                validationStepper.next();
            });
            window.FormValidation1 = FormValidation1;   // ← متاح لكل الملفات


            // التحقق للخطوة الثانية - معلومات الشركة
            const FormValidation2 = FormValidation.formValidation(wizardValidation, {
                fields: {
                    debt_amount: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى إدخال مبلغ المديونية'
                            },
                            numeric: {
                                message: 'مبلغ المديونية يجب أن يكون رقم'
                            }

                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap5: new FormValidation.plugins.Bootstrap5({
                        eleValidClass: '',
                        rowSelector: '.col-sm-6, .col-sm-12, .col-12'
                    }),
                    autoFocus: new FormValidation.plugins.AutoFocus(),
                    submitButton: new FormValidation.plugins.SubmitButton()
                }
            }).on('core.form.valid', function () {
                validationStepper.next();
            });

            ////////////////////////////////////////////////////
            window.FormValidation2 = FormValidation2;
            window.dynamicFieldNames = {};
            //////////////////////////////////////////////////

            // دالة مخصصة للتحقق من صيغة رقم الهاتف السعودي
            function validateSaudiPhone(value) {
                if (!value) return { valid: false, message: 'يرجى إدخال رقم الهاتف' };

                // إزالة المسافات
                value = value.replace(/\s/g, '');

                // التحقق من الصيغ المقبولة مع رسائل مخصصة

                // إذا بدأ بـ 9665
                if (value.startsWith('9665')) {
                    if (value.length === 12 && /^9665[0-9]{8}$/.test(value)) {
                        return { valid: true };
                    } else if (value.length < 12) {
                        return { valid: false, message: 'رقم الهاتف يجب أن يكون 12 رقم (9665XXXXXXXX)' };
                    } else {
                        return { valid: false, message: 'رقم الهاتف يجب أن يكون 12 رقم فقط (9665XXXXXXXX)' };
                    }
                }

                // إذا بدأ بـ 05
                if (value.startsWith('05')) {
                    if (value.length === 10 && /^05[0-9]{8}$/.test(value)) {
                        return { valid: true };
                    } else if (value.length < 10) {
                        return { valid: false, message: 'رقم الهاتف يجب أن يكون 10 أرقام (05XXXXXXXX)' };
                    } else {
                        return { valid: false, message: 'رقم الهاتف يجب أن يكون 10 أرقام فقط (05XXXXXXXX)' };
                    }
                }

                // إذا بدأ بـ 5 (وليس 05)
                if (value.startsWith('5') && !value.startsWith('05')) {
                    if (value.length === 9 && /^5[0-9]{8}$/.test(value)) {
                        return { valid: true };
                    } else if (value.length < 9) {
                        return { valid: false, message: 'رقم الهاتف يجب أن يكون 9 أرقام (5XXXXXXXX)' };
                    } else {
                        return { valid: false, message: 'رقم الهاتف يجب أن يكون 9 أرقام فقط (5XXXXXXXX)' };
                    }
                }

                // إذا لم يبدأ بأي من الصيغ المقبولة
                return { valid: false, message: 'رقم الهاتف يجب أن يبدأ بـ 9665 أو 05 أو 5' };
            }

            // التحقق للخطوة الثالثة - بيانات الاتصال
            const FormValidation3 = FormValidation.formValidation(wizardValidation, {
                fields: {
                    phone: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى إدخال رقم الهاتف الأساسي'
                            },
                            callback: {
                                callback: function (input) {
                                    const result = validateSaudiPhone(input.value);
                                    return {
                                        valid: result.valid,
                                        message: result.message || 'رقم الهاتف غير صحيح'
                                    };
                                }
                            }
                        }
                    },
                    account_number: {
                        validators: {
                            stringLength: {
                                max: 100,
                                message: 'رقم الحساب يجب ألا يتجاوز 100 حرف'
                            }
                        }
                    },
                    commission_rate: {
                        validators: {
                            numeric: {
                                message: 'نسبة العمولة يجب أن تكون رقم'
                            },
                            between: {
                                min: 0,
                                max: 100,
                                message: 'نسبة العمولة يجب أن تكون بين 0 و 100'
                            }
                        }
                    },
                    contract_date: {
                        validators: {
                            date: {
                                format: 'YYYY-MM-DD',
                                message: 'يرجى إدخال تاريخ التعاقد بالتنسيق الصحيح'
                            }
                        }
                    },
                    default_date: {
                        validators: {
                            date: {
                                format: 'YYYY-MM-DD',
                                message: 'يرجى إدخال تاريخ التعثر بالتنسيق الصحيح'
                            }
                        }
                    }
                },
                plugins: {
                    trigger: new FormValidation.plugins.Trigger(),
                    bootstrap5: new FormValidation.plugins.Bootstrap5({
                        eleValidClass: '',
                        rowSelector: '.col-sm-4, .col-sm-6, .col-sm-12, .col-12'
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

            // جعل دالة التحقق متاحة عالمياً
            window.validateSaudiPhone = validateSaudiPhone;

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
                        case 2:
                            FormValidation3.validate();
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
                        case 3:
                        case 2:
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

            // إضافة أحداث للـ Select2 لإعادة التحقق
            $(wizardValidation).find('.select2').on('select2:select select2:clear', function () {
                const fieldName = $(this).attr('name');

                // تحديد أي نموذج تحقق يجب استخدامه
                if (['name', 'customer_type', 'identity_number', 'nationality', 'company_id', 'portfolio_id'].includes(fieldName)) {
                    FormValidation1.revalidateField(fieldName);
                } else if (['debt_amount', 'relationship_manager_id'].includes(fieldName)) {
                    FormValidation2.revalidateField(fieldName);
                } else if (['phone', 'account_number'].includes(fieldName)) {
                    FormValidation3.revalidateField(fieldName);
                } else {
                    FormValidation4.revalidateField(fieldName);
                }
            });

            // التحقق من الهواتف الإضافية
            function validateAdditionalPhones() {
                let isValid = true;
                const additionalPhones = document.querySelectorAll('.additional-phone');

                // إزالة رسائل الخطأ السابقة
                document.querySelectorAll('.phone-error').forEach(el => el.remove());

                additionalPhones.forEach(function (phoneInput) {
                    if (phoneInput.value.trim() !== '') {
                        // استخدام دالة التحقق المخصصة
                        const result = validateSaudiPhone(phoneInput.value);
                        if (!result.valid) {
                            isValid = false;

                            // إضافة رسالة خطأ
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'invalid-feedback phone-error';
                            errorDiv.textContent = result.message;
                            phoneInput.classList.add('is-invalid');
                            phoneInput.parentNode.appendChild(errorDiv);
                        } else {
                            phoneInput.classList.remove('is-invalid');
                        }
                    } else {
                        phoneInput.classList.remove('is-invalid');
                    }
                });

                return isValid;
            }

            // جعل الدالة متاحة عالمياً
            window.validateAdditionalPhones = validateAdditionalPhones;
        }
    })();
});

// وظائف إضافية
$(document).ready(function () {
    // تهيئة Select2 مع أحداث التحقق
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

    // تهيئة Flatpickr للتواريخ
    $('.flatpickr-date').flatpickr({
        dateFormat: 'Y-m-d',
        locale: 'ar',
        allowInput: true,
        maxDate: 'today',
        onClose: function (selectedDates, dateStr, instance) {
            // إعادة التحقق من صحة الحقل عند إغلاق التاريخ
            setTimeout(() => {
                $(instance.element).trigger('blur');
            }, 100);
        }
    });

    // فلترة المحافظ حسب الشركة
    $('#company_id').on('change', function () {
        const selectedCompany = $(this).val();
        const portfolioSelect = $('#portfolio_id');

        // إعادة تعيين المحافظ
        portfolioSelect.val('').trigger('change');

        if (selectedCompany) {
            // تحميل المحافظ عبر AJAX
            $.ajax({
                url: window.routes.portfoliosByCompany,
                type: 'GET',
                data: { company_id: selectedCompany },
                success: function (response) {
                    if (response.success) {
                        // مسح الخيارات الحالية
                        portfolioSelect.empty().append('<option value=""></option>');

                        // إضافة الخيارات الجديدة
                        response.data.forEach(function (portfolio) {
                            portfolioSelect.append(
                                '<option value="' + portfolio.id + '" ' +
                                'data-has-split="' + (portfolio.has_split_names ? 1 : 0) + '">' +
                                portfolio.name +
                                '</option>'
                            );
                        });
                    }
                },
                error: function () {
                    toastr.error('حدث خطأ في تحميل المحافظ');
                }
            });
        } else {
            // إظهار جميع المحافظ
            portfolioSelect.find('option').show();
        }

        // إعادة تحديث Select2
        portfolioSelect.trigger('change');
    });

    // تقييد الأرقام فقط لحقل رقم الهوية
    $('#identity_number').on('input', function () {
        // السماح بالأرقام والأحرف فقط
        this.value = this.value.replace(/[^a-zA-Z0-9]/g, '');
    });

    // تقييد الأرقام فقط للحقول الرقمية
    $('#debt_amount, #commission_rate').on('input', function () {
        // السماح بالأرقام والنقطة فقط
        this.value = this.value.replace(/[^0-9.]/g, '');
    });

    // تقييد عدد المطالبات للأرقام فقط بدون شروط
    $('#claims_count').on('input', function () {
        // السماح بالأرقام فقط
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // تهيئة قيود الهاتف المحدثة
    function initializePhoneRestrictions() {
        $('.phone-input').off('input').on('input', function () {
            // السماح بالأرقام فقط
            this.value = this.value.replace(/[^0-9]/g, '');

            // تقييد الطول الأقصى إلى 12 رقم (لصيغة 9665XXXXXXXX)
            if (this.value.length > 12) {
                this.value = this.value.substring(0, 12);
            }

            // إزالة رسائل الخطأ عند الكتابة
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        });
    }

    // تطبيق القيود عند تحميل الصفحة
    initializePhoneRestrictions();

    // إعادة تطبيق القيود عند إضافة هاتف جديد
    window.initializePhoneRestrictions = initializePhoneRestrictions;

    // معالجة التحقق من مبلغ المديونية
    $('#debt_amount').on('input blur', function () {
        const value = parseFloat($(this).val());
        if ($(this).val() !== '' && (isNaN(value) || value <= 0)) {
            $(this).addClass('is-invalid');
            if ($(this).siblings('.invalid-feedback').length === 0) {
                $(this).after('<div class="invalid-feedback">مبلغ المديونية يجب أن يكون أكبر من صفر</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        }
    });

    // معالجة رقم الهاتف الأساسي مع التحقق المحدث
    $('#phone').on('input blur', function () {
        const value = $(this).val();
        if (value !== '') {
            const result = window.validateSaudiPhone(value);
            if (!result.valid) {
                $(this).addClass('is-invalid');
                $(this).siblings('.invalid-feedback').remove();
                $(this).after('<div class="invalid-feedback d-block">' + result.message + '</div>');
            } else {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').remove();
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        }
    });
});
