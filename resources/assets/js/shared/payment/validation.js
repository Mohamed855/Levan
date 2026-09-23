'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const paymentForm = document.querySelector('#payment-form');
    if (!paymentForm) return;

    // تهيئة التحقق من النموذج
    const fv = FormValidation.formValidation(paymentForm, {
        fields: {
            // بيانات البطاقة
            card_number: {
                validators: {
                    notEmpty: { message: 'رقم البطاقة مطلوب' },
                    callback: {
                        message: 'رقم البطاقة يجب أن يكون 16 رقماً',
                        callback: (input) => {
                            const value = input.value.replace(/\s/g, '');
                            return value.length === 16 && /^[0-9]+$/.test(value);
                        }
                    },
                    creditCard: { message: 'رقم البطاقة غير صحيح' }
                }
            },
            card_name_display: {
                validators: {
                    notEmpty: { message: 'اسم حامل البطاقة مطلوب' },
                    stringLength: {
                        min: 3,
                        max: 50,
                        message: 'الاسم يجب أن يكون بين 3 و 50 حرف'
                    },
                    regexp: {
                        regexp: /^[A-Za-z\s]+$/,
                        message: 'الاسم يجب أن يكون بأحرف إنجليزية فقط'
                    }
                }
            },
            exp_date_display: {
                validators: {
                    notEmpty: { message: 'تاريخ الانتهاء مطلوب' },
                    regexp: {
                        regexp: /^(0[1-9]|1[0-2])\/([0-9]{2})$/,
                        message: 'تاريخ الانتهاء غير صالح (MM/YY)'
                    },
                    callback: {
                        message: 'البطاقة منتهية الصلاحية',
                        callback: (input) => {
                            if (!input.value) return true;

                            const [monthStr, yearStr] = input.value.split('/');
                            const month = parseInt(monthStr, 10);
                            const year = parseInt('20' + yearStr, 10);
                            const now = new Date();
                            const currentYear = now.getFullYear();
                            const currentMonth = now.getMonth() + 1;

                            return !(year < currentYear || (year === currentYear && month < currentMonth));
                        }
                    }
                }
            },
            cvc: {
                validators: {
                    notEmpty: { message: 'رمز الأمان (CVV) مطلوب' },
                    regexp: {
                        regexp: /^[0-9]{3,4}$/,
                        message: 'رمز الأمان يجب أن يكون 3 أو 4 أرقام'
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

    // عند نجاح التحقق من النموذج
    fv.on('core.form.valid', function () {
        const submitBtn = paymentForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> جاري المعالجة...';
        paymentForm.submit();
    });

    // تحديث التحقق عند ترك أي حقل
    paymentForm.querySelectorAll('input').forEach(function (input) {
        input.addEventListener('blur', function () {
            fv.revalidateField(input.name || input.id);
        });
    });

    function preventUnchangedEvent(e, currentValue, previousValue) {
        if (currentValue === previousValue) {
            e.stopImmediatePropagation();
            e.preventDefault();
            return true;
        }

        return false;
    }

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

    /**
     * معالجة keydown للحقول النصية
     */
    function handleTextKeydown(e, pattern) {
        const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', ' '];

        if (e.ctrlKey || e.metaKey || allowedKeys.includes(e.key)) {
            return;
        }

        if (!pattern.test(e.key)) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }

    /**
     * معالجة اللصق للحقول الرقمية
     */
    function handleNumericPaste(e, field, maxLength, previousValueRef) {
        e.preventDefault();

        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const numericOnly = pastedText.replace(/[^0-9]/g, '');
        const trimmed = numericOnly.slice(0, maxLength);

        if (trimmed) {
            const start = field.selectionStart;
            const end = field.selectionEnd;
            const before = field.value.substring(0, start);
            const after = field.value.substring(end);

            const newValue = (before + trimmed + after).slice(0, maxLength);
            field.value = newValue;
            previousValueRef.value = newValue;

            const newPos = Math.min(start + trimmed.length, maxLength);
            field.setSelectionRange(newPos, newPos);

            field.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // Numeric Fields Handler
    // ═══════════════════════════════════════════════════════════════
    const numericFields = {
        'cvc': 4
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

        field.addEventListener('paste', (e) => {
            handleNumericPaste(e, field, maxLength, previousValueRef);
        });

        field.addEventListener('focus', function () {
            previousValueRef.value = this.value;
        });
    });

    // ═══════════════════════════════════════════════════════════════
    // Card Name Handler (English Only - Uppercase)
    // ═══════════════════════════════════════════════════════════════
    const cardNameInput = document.getElementById('card_name_display');
    if (cardNameInput) {
        const previousValueRef = { value: cardNameInput.value };

        cardNameInput.addEventListener('keydown', (e) => {
            handleTextKeydown(e, /^[a-zA-Z]$/);
        });

        cardNameInput.addEventListener('input', function (e) {
            const cursorPos = this.selectionStart;

            this.value = this.value.replace(/[^a-zA-Z\s]/g, '').toUpperCase();

            if (preventUnchangedEvent(e, this.value, previousValueRef.value)) return;

            previousValueRef.value = this.value;
            this.setSelectionRange(cursorPos, cursorPos);
        });

        cardNameInput.addEventListener('focus', function () {
            previousValueRef.value = this.value;
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // Billing Name Handler (Arabic/English)
    // ═══════════════════════════════════════════════════════════════
    const billingNameInput = document.getElementById('billing_name');
    if (billingNameInput) {
        const previousValueRef = { value: billingNameInput.value };

        billingNameInput.addEventListener('keydown', (e) => {
            handleTextKeydown(e, /^[a-zA-Z\u0600-\u06FF]$/);
        });

        billingNameInput.addEventListener('input', function (e) {
            this.value = this.value.replace(/[^a-zA-Z\u0600-\u06FF\s]/g, '');

            if (preventUnchangedEvent(e, this.value, previousValueRef.value)) return;

            previousValueRef.value = this.value;
        });

        billingNameInput.addEventListener('focus', function () {
            previousValueRef.value = this.value;
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // Card Number Handler (16 digits with formatting)
    // ═══════════════════════════════════════════════════════════════
    const cardNumberInput = document.getElementById('card_number');
    if (cardNumberInput) {
        const previousValueRef = { value: cardNumberInput.value };

        cardNumberInput.addEventListener('keydown', function (e) {
            const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'];

            if (e.ctrlKey || e.metaKey || allowedKeys.includes(e.key)) {
                return;
            }

            const digitsOnly = this.value.replace(/\s/g, '');

            if (!/^[0-9]$/.test(e.key) || digitsOnly.length >= 16) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        cardNumberInput.addEventListener('input', function (e) {
            const cursorPos = this.selectionStart;
            const oldLength = this.value.length;

            let value = this.value.replace(/[^0-9]/g, '').slice(0, 16);
            const formatted = value.match(/.{1,4}/g)?.join(' ') || value;

            this.value = formatted;

            // تعديل موضع المؤشر بعد التنسيق
            const newCursorPos = formatted.length > oldLength ? cursorPos + 1 : cursorPos;
            this.setSelectionRange(newCursorPos, newCursorPos);

            if (preventUnchangedEvent(e, this.value, previousValueRef.value)) return;

            previousValueRef.value = this.value;
        });

        cardNumberInput.addEventListener('paste', function (e) {
            e.preventDefault();

            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const digitsOnly = pastedText.replace(/[^0-9]/g, '').slice(0, 16);

            if (digitsOnly) {
                const formatted = digitsOnly.match(/.{1,4}/g)?.join(' ') || digitsOnly;
                this.value = formatted;
                previousValueRef.value = formatted;

                this.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });

        cardNumberInput.addEventListener('focus', function () {
            previousValueRef.value = this.value;
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // Cleave.js Initialization
    // ═══════════════════════════════════════════════════════════════
    if (typeof Cleave !== 'undefined') {
        // تاريخ الانتهاء
        const expiryDate = document.getElementById('exp_date_display');
        if (expiryDate) {
            new Cleave(expiryDate, {
                date: true,
                datePattern: ['m', 'y']
            });
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // Submit: Remove spaces from card number
    // ═══════════════════════════════════════════════════════════════
    paymentForm.addEventListener('submit', function () {
        const cardNumberField = document.getElementById('card_number');
        if (cardNumberField) {
            cardNumberField.value = cardNumberField.value.replace(/\s/g, '');
        }
    });
});
