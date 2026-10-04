'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const wizardValidation = document.querySelector('#wizard-validation');
    if (!wizardValidation) {
        return;
    }

    const form = wizardValidation.querySelector('#wizard-validation-form');
    if (!form) {
        return;
    }

    const isEditMode = form.querySelector('input[name="_method"]') !== null;
    const userId = form.dataset.userId || form.querySelector('input[name="user_id"]')?.value || null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const routes = {
        name: window.routes?.checkNameUnique || '/admin/users/check-name-unique',
        email: window.routes?.checkEmailUnique || '/admin/users/check-email-unique',
        phone: window.routes?.checkPhoneUnique || '/admin/users/check-phone-unique',
    };

    const validationStepper = new Stepper(wizardValidation, {
        linear: false,
    });

    const wizardValidationBtnNextList = [].slice.call(wizardValidation.querySelectorAll('.btn-next'));
    const wizardValidationBtnPrevList = [].slice.call(wizardValidation.querySelectorAll('.btn-prev'));

    // Step 1: Basic Information Validation
    const FormValidation1 = FormValidation.formValidation(form, {
        fields: {
            name: {
                validators: {
                    notEmpty: {
                        message: 'الاسم مطلوب',
                    },
                    stringLength: {
                        min: 2,
                        max: 255,
                        message: 'الاسم يجب أن يكون بين 2 و 255 حرف',
                    },
                    regexp: {
                        regexp: /^[\u0600-\u06FFa-zA-Z\s\-\.]+$/,
                        message: 'الاسم يجب أن يحتوي على أحرف فقط',
                    },
                },
            },
            email: {
                validators: {
                    notEmpty: {
                        message: 'البريد الإلكتروني مطلوب',
                    },
                    emailAddress: {
                        message: 'البريد الإلكتروني غير صحيح',
                    },
                },
            },
            phone: {
                validators: {
                    notEmpty: {
                        message: 'رقم الهاتف مطلوب',
                    },
                    regexp: {
                        regexp: /^\+?[0-9]{7,15}$/,
                        message: 'رقم الهاتف غير صحيح (يجب أن يكون من 7 إلى 15 رقم)',
                    },
                    stringLength: {
                        max: 20,
                        message: 'رقم الهاتف يجب ألا يزيد عن 20 رقم',
                    },
                },
            },
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: '',
                rowSelector: '.col-sm-6, .col-12',
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
        },
    });

    // Step 2: Security & Image Validation
    const passwordValidationFields = isEditMode
        ? {
            password: {
                validators: {
                    callback: {
                        message: 'كلمة المرور يجب ألا تقل عن 8 أحرف',
                        callback: function (input) {
                            return input.value === '' || input.value.length >= 8;
                        },
                    },
                },
            },
            password_confirmation: {
                validators: {
                    callback: {
                        message: 'تأكيد كلمة المرور غير مطابق',
                        callback: function (input) {
                            const pwd = form.querySelector('[name="password"]')?.value || '';
                            if (pwd === '' && input.value === '') {
                                return true;
                            }
                            return input.value === pwd;
                        },
                    },
                },
            },
        }
        : {
            password: {
                validators: {
                    notEmpty: {
                        message: 'كلمة المرور مطلوبة',
                    },
                    stringLength: {
                        min: 8,
                        message: 'كلمة المرور يجب ألا تقل عن 8 أحرف',
                    },
                },
            },
            password_confirmation: {
                validators: {
                    notEmpty: {
                        message: 'تأكيد كلمة المرور مطلوب',
                    },
                    identical: {
                        compare: function () {
                            return form.querySelector('[name="password"]')?.value;
                        },
                        message: 'تأكيد كلمة المرور غير مطابق',
                    },
                },
            },
        };

    const FormValidation2 = FormValidation.formValidation(form, {
        fields: {
            ...passwordValidationFields,
            image: {
                validators: {
                    file: {
                        extension: 'jpeg,jpg,png,gif',
                        type: 'image/jpeg,image/jpg,image/png,image/gif',
                        maxSize: 2097152,
                        message: 'الصورة يجب أن تكون من نوع jpeg, png, jpg, gif وألا يتجاوز حجمها 2MB',
                    },
                },
            },
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: '',
                rowSelector: '.col-sm-6, .col-12',
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
        },
    });

    // Uniqueness state tracking
    const uniquenessState = {
        name: true,
        email: true,
        phone: true,
    };

    const activeAjaxRequests = {
        name: null,
        email: null,
        phone: null,
    };

    const timeouts = {
        name: null,
        email: null,
        phone: null,
    };

    function checkFieldUniqueness(fieldName, value, inputEl) {
        clearTimeout(timeouts[fieldName]);
        if (activeAjaxRequests[fieldName]) {
            activeAjaxRequests[fieldName].abort();
            activeAjaxRequests[fieldName] = null;
        }

        const trimmed = (value || '').trim();
        const initialValue = (inputEl.dataset.initialValue || '').trim();

        $(`#${fieldName}-feedback`).remove();

        if (trimmed === '') {
            uniquenessState[fieldName] = false;
            return;
        }

        if (isEditMode && trimmed === initialValue) {
            uniquenessState[fieldName] = true;
            inputEl.classList.remove('is-invalid');
            inputEl.classList.add('is-valid');
            return;
        }

        timeouts[fieldName] = setTimeout(function () {
            const data = {
                [fieldName]: trimmed,
                _token: csrfToken,
            };
            if (userId) {
                data.user_id = userId;
            }

            activeAjaxRequests[fieldName] = $.ajax({
                url: routes[fieldName],
                method: 'POST',
                data: data,
                success: function (res) {
                    $(`#${fieldName}-feedback`).remove();
                    if (res.available) {
                        uniquenessState[fieldName] = true;
                        inputEl.classList.remove('is-invalid');
                        inputEl.classList.add('is-valid');
                    } else {
                        uniquenessState[fieldName] = false;
                        inputEl.classList.remove('is-valid');
                        inputEl.classList.add('is-invalid');
                        $(inputEl).after(
                            `<div id="${fieldName}-feedback" class="invalid-feedback d-block"><i class="ti ti-x me-1"></i>${res.message}</div>`
                        );
                    }
                },
                error: function (xhr) {
                    if (xhr.statusText === 'abort') return;
                    $(`#${fieldName}-feedback`).remove();
                    uniquenessState[fieldName] = false;
                    inputEl.classList.remove('is-valid');
                    inputEl.classList.add('is-invalid');
                    $(inputEl).after(
                        `<div id="${fieldName}-feedback" class="invalid-feedback d-block"><i class="ti ti-x me-1"></i>حدث خطأ أثناء التحقق</div>`
                    );
                },
                complete: function () {
                    activeAjaxRequests[fieldName] = null;
                },
            });
        }, 600);
    }

    ['name', 'email', 'phone'].forEach(function (fieldName) {
        const inputEl = form.querySelector(`#${fieldName}`);
        if (!inputEl) return;

        inputEl.addEventListener('input', function () {
            checkFieldUniqueness(fieldName, this.value, inputEl);
        });

        inputEl.addEventListener('change', function () {
            checkFieldUniqueness(fieldName, this.value, inputEl);
        });
    });

    // Step navigation
    wizardValidationBtnNextList.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const currentStep = validationStepper._currentIndex;
            if (currentStep === 0) {
                FormValidation1.validate().then(function (status) {
                    if (status !== 'Valid') {
                        return;
                    }
                    if (!uniquenessState.name || !uniquenessState.email || !uniquenessState.phone) {
                        if (typeof toastr !== 'undefined') {
                            toastr.error('يرجى التأكد من أن جميع الحقول صحيحة ومتاحة');
                        }
                        return;
                    }
                    validationStepper.next();
                });
            } else if (currentStep === 1) {
                FormValidation2.validate().then(function (status) {
                    if (status === 'Valid') {
                        form.submit();
                    }
                });
            }
        });
    });

    wizardValidationBtnPrevList.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (validationStepper._currentIndex === 0) {
                window.history.back();
            } else {
                validationStepper.previous();
            }
        });
    });

    // Revalidate password confirmation when password changes
    const passwordInput = form.querySelector('[name="password"]');
    const passwordConfirmInput = form.querySelector('[name="password_confirmation"]');
    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            if (passwordConfirmInput && passwordConfirmInput.value !== '') {
                FormValidation2.revalidateField('password_confirmation');
            }
        });
    }

    // Toggle password visibility
    $('.input-group-text').on('click', function () {
        const input = $(this).closest('.input-group').find('input');
        const icon = $(this).find('i');
        if (!input.length) return;

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('ti-eye-off').addClass('ti-eye');
        } else {
            input.attr('type', 'password');
            icon.removeClass('ti-eye').addClass('ti-eye-off');
        }
    });

    // Image upload validation & preview
    const uploadInput = document.getElementById('upload');
    const uploadedAvatar = document.getElementById('uploadedAvatar');

    if (uploadInput && uploadedAvatar) {
        uploadInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            const feedbackSelector = '#upload-feedback';
            $(feedbackSelector).remove();

            if (!file) {
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                uploadInput.value = '';
                if (typeof toastr !== 'undefined') toastr.error('نوع الملف غير مدعوم (JPG, PNG, GIF فقط)');
                return;
            }

            const maxSize = 2 * 1024 * 1024; // 2MB
            if (file.size > maxSize) {
                uploadInput.value = '';
                if (typeof toastr !== 'undefined') toastr.error('حجم الصورة يجب ألا يتجاوز 2 ميجابايت');
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                const img = new Image();
                img.onload = function () {
                    if (this.width > 2000 || this.height > 2000 || this.width < 100 || this.height < 100) {
                        uploadInput.value = '';
                        if (typeof toastr !== 'undefined') {
                            toastr.error('أبعاد الصورة يجب أن تكون بين 100×100 و 2000×2000 بكسل');
                        }
                        return;
                    }
                    uploadedAvatar.src = event.target.result;
                };
                img.onerror = function () {
                    uploadInput.value = '';
                    if (typeof toastr !== 'undefined') toastr.error('فشل تحميل الصورة');
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    // Select2
    $('.select2').each(function () {
        $(this).select2({
            placeholder: $(this).data('placeholder') || 'اختر',
            allowClear: true,
            width: '100%',
            language: 'ar',
            dir: 'rtl',
        });
    });
});
