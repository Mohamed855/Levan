/**
* Form Wizard Validation for Collections
* =====================================
* التحقق من صحة نماذج التحصيلات مع wizard
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

            // التحقق للخطوة الأولى - معلومات العميل والمبلغ
            const FormValidation1 = FormValidation.formValidation(wizardValidation, {
                fields: {
                    customer_id: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى اختيار العميل'
                            }
                        }
                    },
                    collected_amount: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى إدخال المبلغ المحصل'
                            },
                            numeric: {
                                message: 'المبلغ يجب أن يكون رقماً'
                            },
                            greaterThan: {
                                min: 0.01,
                                message: 'المبلغ يجب أن يكون أكبر من صفر'
                            },
                            lessThan: {
                                max: 999999999.99,
                                message: 'المبلغ كبير جداً'
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

            // التحقق للخطوة الثانية - تفاصيل التحصيل
            const FormValidation2 = FormValidation.formValidation(wizardValidation, {
                fields: {
                    collection_type: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى اختيار نوع التحصيل'
                            }
                        }
                    },
                    payment_method_id: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى اختيار طريقة التحصيل'
                            }
                        }
                    },
                    payment_type: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى اختيار نوع السداد'
                            }
                        }
                    },
                    collection_date: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى اختيار تاريخ التحصيل'
                            },
                            date: {
                                format: 'YYYY-MM-DD',
                                message: 'تاريخ التحصيل غير صحيح'
                            },
                            callback: {
                                message: 'تاريخ التحصيل لا يمكن أن يكون في المستقبل',
                                callback: function (input) {
                                    const selectedDate = new Date(input.value);
                                    const today = new Date();
                                    today.setHours(23, 59, 59, 999); // نهاية اليوم
                                    return selectedDate <= today;
                                }
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

            // التحقق للخطوة الثالثة - معلومات السند والمرفقات
            const FormValidation3 = FormValidation.formValidation(wizardValidation, {
                fields: {
                    receipt_number: {
                        validators: {
                            notEmpty: {
                                message: 'يرجى إدخال رقم السند'
                            },
                            stringLength: {
                                min: 2,
                                max: 255,
                                message: 'رقم السند يجب أن يكون بين 2 و 255 حرف'
                            }
                        }
                    },
                    closing_code: {
                        validators: {
                            stringLength: {
                                max: 255,
                                message: 'رمز الإقفال طويل جداً'
                            }
                        }
                    },
                    attachment: {
                        validators: {
                            file: {
                                extension: 'pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
                                type: 'application/pdf,image/jpeg,image/jpg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                maxSize: 10485760, // 10MB
                                message: 'الملف غير مدعوم أو حجمه كبير جداً (الحد الأقصى 10 ميجابايت)'
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
                submitForm();
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
                        case 2:
                            validationStepper.previous();
                            break;
                        case 1:
                            validationStepper.previous();
                            break;
                        case 0:
                        default:
                            window.history.back();
                            break;
                    }
                });
            });

            // إضافة أحداث للـ Select2 لإعادة التحقق
            $(wizardValidation).find('.select2, .select2-ajax').on('select2:select select2:clear', function () {
                const fieldName = $(this).attr('name');

                if (['customer_id', 'collected_amount'].includes(fieldName)) {
                    FormValidation1.revalidateField(fieldName);
                } else if (['collection_type', 'payment_method_id', 'payment_type', 'collection_date'].includes(fieldName)) {
                    FormValidation2.revalidateField(fieldName);
                } else if (['receipt_number', 'closing_code'].includes(fieldName)) {
                    FormValidation3.revalidateField(fieldName);
                }
            });

            // إعادة التحقق عند تغيير الملف
            $('#attachment').on('change', function () {
                FormValidation3.revalidateField('attachment');
            });

            // إعادة التحقق عند تغيير التاريخ
            $('#collection_date').on('change', function () {
                FormValidation2.revalidateField('collection_date');
            });

            // إعادة التحقق عند تغيير المبلغ
            $('#collected_amount').on('input', function () {
                FormValidation1.revalidateField('collected_amount');
            });
        }
    })();
});

function submitForm() {
    const form = document.querySelector('#wizard-validation-form');
    if (form) {
        const submitBtn = form.querySelector('.btn-submit');
        if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>جاري الحفظ...';
            submitBtn.disabled = true;
        }
        form.submit();
    }
}

$(document).ready(function () {
    initializeSelect2Components();
    initializeFlatpickr();
    initializeFormEvents();
});

function initializeSelect2Components() {
    // Select2 العادي
    $('.select2').each(function () {
        $(this).select2({
            placeholder: function () {
                return $(this).data('placeholder');
            },
            allowClear: true,
            width: '100%',
            language: {
                noResults: function () {
                    return "لا توجد نتائج";
                },
                searching: function () {
                    return "جاري البحث...";
                }
            },
            dir: 'rtl'
        }).on('select2:select select2:clear', function () {
            $(this).removeClass('is-invalid');
            $(this).closest('.col-sm-6, .col-sm-12, .col-12').find('.invalid-feedback').remove();
        });
    });

    // Select2 مع AJAX للعملاء
    $('.select2-ajax').select2({
        placeholder: function () {
            return $(this).data('placeholder');
        },
        allowClear: true,
        width: '100%',
        language: {
            noResults: function () {
                return "لا توجد نتائج";
            },
            searching: function () {
                return "جاري البحث...";
            }
        },
        dir: 'rtl',
        minimumInputLength: 0, // السماح بالبحث الفارغ
        ajax: {
            url: window.routes.searchCustomers,
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    q: params.term || '',
                    page: params.page || 1
                };
            },
            processResults: function (data) {
                return {
                    results: data,
                    pagination: {
                        more: data.length === 20
                    }
                };
            },
            cache: true
        }
    }).on('select2:select select2:clear', function () {
        $(this).removeClass('is-invalid');
        $(this).closest('.col-sm-6, .col-sm-12, .col-12').find('.invalid-feedback').remove();
    });
}


function initializeFlatpickr() {
    $('.flatpickr-date').flatpickr({
        dateFormat: 'Y-m-d',
        locale: 'ar',
        allowInput: true,
        maxDate: 'today',
        onClose: function (selectedDates, dateStr, instance) {
            setTimeout(() => {
                $(instance.element).trigger('blur');
            }, 100);
        }
    });
}

function initializeFormEvents() {
    $('#collected_amount').on('input', function () {
        let value = $(this).val();
        value = value.replace(/[^0-9.]/g, '');

        const parts = value.split('.');
        if (parts.length > 2) {
            value = parts[0] + '.' + parts.slice(1).join('');
        }

        if (parts[1] && parts[1].length > 2) {
            value = parts[0] + '.' + parts[1].substring(0, 2);
        }

        $(this).val(value);
    });

    $('#generate-receipt').on('click', function () {
        const btn = $(this);
        const originalIcon = btn.html();

        btn.html('<i class="ti ti-loader-2 ti-spin"></i>').prop('disabled', true);

        $.ajax({
            url: window.routes.generateReceiptNumber,
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    $('#receipt_number').val(response.receipt_number);
                    toastr.success('تم توليد رقم سند جديد');
                } else {
                    toastr.error('حدث خطأ في توليد رقم السند');
                }
            },
            error: function () {
                toastr.error('حدث خطأ في توليد رقم السند');
            },
            complete: function () {
                btn.html(originalIcon).prop('disabled', false);
            }
        });
    });

    $('#attachment').on('change', function () {
        const file = this.files[0];
        if (file) {
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png',
                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

            if (!allowedTypes.includes(file.type)) {
                toastr.error('نوع الملف غير مدعوم');
                $(this).val('');
                return;
            }

            if (fileSize > 10) {
                toastr.error('حجم الملف يجب أن يكون أقل من 10 ميجابايت');
                $(this).val('');
                return;
            }

            const fileName = file.name;
            const fileInfo = `تم اختيار: ${fileName} (${fileSize} ميجابايت)`;

            if (!$('#file-info').length) {
                $(this).after('<small id="file-info" class="text-success mt-1 d-block"></small>');
            }
            $('#file-info').text(fileInfo);
        } else {
            $('#file-info').remove();
        }
    });

    $('#remove_attachment').on('change', function () {
        if ($(this).is(':checked')) {
            $('#attachment').prop('disabled', true);
            $('.current-file').addClass('text-decoration-line-through opacity-50');
        } else {
            $('#attachment').prop('disabled', false);
            $('.current-file').removeClass('text-decoration-line-through opacity-50');
        }
    });

    let receiptCheckTimeout;
    $('#receipt_number').on('input', function () {
        const receiptNumber = $(this).val();
        const collectionId = window.collectionId || null;

        clearTimeout(receiptCheckTimeout);

        if (receiptNumber.length >= 3) {
            receiptCheckTimeout = setTimeout(function () {
                checkReceiptNumberUniqueness(receiptNumber, collectionId);
            }, 500);
        }
    });

    $('input, select, textarea').on('invalid', function () {
        this.setCustomValidity('');
        if (!this.validity.valid) {
            this.setCustomValidity(getCustomValidationMessage(this));
        }
    });

    $('input, select, textarea').on('input change', function () {
        this.setCustomValidity('');
    });
}

function checkReceiptNumberUniqueness(receiptNumber, collectionId) {
    // يمكن إضافة AJAX call للتحقق من تفرد رقم السند
}

function getCustomValidationMessage(field) {
    const fieldName = field.name;
    const validity = field.validity;

    const messages = {
        customer_id: 'يرجى اختيار العميل',
        collected_amount: 'يرجى إدخال مبلغ صحيح',
        collection_type: 'يرجى اختيار نوع التحصيل',
        payment_method_id: 'يرجى اختيار طريقة التحصيل',
        payment_type: 'يرجى اختيار نوع السداد',
        collection_date: 'يرجى اختيار تاريخ التحصيل',
        receipt_number: 'يرجى إدخال رقم السند'
    };

    if (validity.valueMissing) {
        return messages[fieldName] || 'هذا الحقل مطلوب';
    }

    if (validity.typeMismatch) {
        return 'تنسيق البيانات غير صحيح';
    }

    if (validity.rangeOverflow) {
        return 'القيمة كبيرة جداً';
    }

    if (validity.rangeUnderflow) {
        return 'القيمة صغيرة جداً';
    }

    return 'البيانات غير صحيحة';
}