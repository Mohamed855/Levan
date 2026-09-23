'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const paymentForm = document.querySelector('#payment-form');
    if (!paymentForm) return;

    // ═══════════════════════════════════════════════════════════════
    // FormValidation Configuration
    // ═══════════════════════════════════════════════════════════════
    const fv = FormValidation.formValidation(paymentForm, {
        fields: {
            customer_name: {
                validators: {
                    notEmpty: { message: 'الاسم مطلوب' },
                    stringLength: {
                        min: 3,
                        max: 50,
                        message: 'الاسم يجب أن يتراوح بين 3 و 50 حرفًا'
                    },
                    regexp: {
                        regexp: /^[a-zA-Z\u0621-\u064A]+(?:\s[a-zA-Z\u0621-\u064A]+)*$/,
                        message: 'الاسم يجب أن يحتوي على حروف ومسافة واحدة فقط بين الكلمات'
                    }
                }
            },
            customer_email: {
                validators: {
                    notEmpty: { message: 'البريد الإلكتروني مطلوب' },
                    regexp: {
                        regexp: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
                        message: 'صيغة البريد الإلكتروني غير صحيحة'
                    }
                }
            },

            customer_phone: {
                validators: {
                    notEmpty: { message: 'رقم الجوال مطلوب' },
                    regexp: {
                        regexp: /^(05|5)[0-9]{8}$/,
                        message: 'رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام'
                    }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: '',
                rowSelector: '.col-md-6, .col-md-3, .col-12,.col-md-12'
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
            submitButton: new FormValidation.plugins.SubmitButton()
        }
    });

    // ═══════════════════════════════════════════════════════════════
    // Form Submit Handler
    // ═══════════════════════════════════════════════════════════════
    fv.on('core.form.valid', function () {
        const submitBtn = paymentForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> جاري المعالجة...';
        paymentForm.submit();
    });

    // ═══════════════════════════════════════════════════════════════
    // Revalidate on Blur
    // ═══════════════════════════════════════════════════════════════
    paymentForm.querySelectorAll('input').forEach(input => {
        input.addEventListener('blur', function () {
            fv.revalidateField(this.name || this.id);
        });
    });

    // ═══════════════════════════════════════════════════════════════
    // Helper Functions
    // ═══════════════════════════════════════════════════════════════

    /**
     * منع إطلاق event إذا لم تتغير القيمة
     */
    function preventUnchangedEvent(e, currentValue, previousValue) {
        if (currentValue === previousValue) {
            e.stopImmediatePropagation();
            e.preventDefault();
            return true;
        }

        return false;
    }

    // ═══════════════════════════════════════════════════════════════
    // Numeric Fields Handler
    // ═══════════════════════════════════════════════════════════════
    const numericFields = {
        'customer_phone': 10,
    };

    Object.entries(numericFields).forEach(([fieldId, maxLength]) => {
        const field = document.getElementById(fieldId);
        if (!field) return;

        const previousValueRef = { value: field.value };

        field.addEventListener('keydown', (e) => {
            handleNumericKeydown(e, maxLength, field.value);
        });

        field.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, maxLength);

            if (preventUnchangedEvent(e, this.value, previousValueRef.value)) return;

            previousValueRef.value = this.value;
        });

        field.addEventListener('focus', function () {
            previousValueRef.value = this.value;
        });
    });

    /**
    * معالجة keydown للحقول الرقمية
    */
    function handleNumericKeydown(e, maxLength, currentValue) {
        const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'];

        if (e.ctrlKey || e.metaKey || allowedKeys.includes(e.key)) {
            return;
        }

        if (!/^[0-9]$/.test(e.key) || currentValue.length >= maxLength) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }
});
