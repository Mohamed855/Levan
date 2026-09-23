'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const paymentForm = document.querySelector('#payment-form');
    if (!paymentForm) return;

    // ═══════════════════════════════════════════════════════════════
    // FormValidation Configuration
    // ═══════════════════════════════════════════════════════════════
    const fv = FormValidation.formValidation(paymentForm, {
        fields: {
            billing_company_name: {
                validators: {
                    notEmpty: { message: 'اسم المنشأة مطلوب' },
                    stringLength: {
                        min: 3,
                        max: 100,
                        message: 'اسم المنشأة يجب أن يكون بين 3 و 100 حرف'
                    }
                }
            },
            billing_commercial_number: {
                validators: {
                    notEmpty: { message: 'رقم السجل التجاري مطلوب' },
                    regexp: {
                        regexp: /^[0-9]{10}$/,
                        message: 'رقم السجل التجاري يجب أن يتكون من 10 أرقام'
                    }
                }
            },
            billing_name: {
                validators: {
                    notEmpty: { message: 'اسم المستخدم مطلوب' },
                    stringLength: {
                        min: 3,
                        max: 50,
                        message: 'اسم المستخدم يجب أن يكون بين 3 و 50 حرف'
                    },
                    regexp: {
                        regexp: /^[a-zA-Z\u0621-\u064A\s]+$/,
                        message: 'اسم المستخدم يجب أن يحتوي على حروف فقط (بدون أرقام)'
                    }
                }
            },
            billing_email: {
                validators: {
                    notEmpty: { message: 'البريد الإلكتروني مطلوب' },
                    emailAddress: { message: 'صيغة البريد الإلكتروني غير صحيحة' }
                }
            },
            billing_phone: {
                validators: {
                    notEmpty: { message: 'رقم الجوال مطلوب' },
                    regexp: {
                        regexp: /^(05|5)[0-9]{8}$/,
                        message: 'رقم الجوال غير صالح (يجب أن يبدأ بـ 05 ويتكون من 10 أرقام)'
                    }
                }
            },
            billing_password: {
                validators: {
                    notEmpty: { message: 'كلمة المرور مطلوبة' },
                    stringLength: {
                        min: 8,
                        max: 50,
                        message: 'كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل'
                    }
                }
            },
            billing_password_confirmation: {
                validators: {
                    notEmpty: { message: 'يرجى تأكيد كلمة المرور' },
                    identical: {
                        compare: () => paymentForm.querySelector('[name="billing_password"]').value,
                        message: 'كلمة المرور غير متطابقة'
                    }
                }
            },
            card_number: {
                enabled: false, // غير مفعل افتراضياً
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
                enabled: false, // غير مفعل افتراضياً
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
                enabled: false, // غير مفعل افتراضياً
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
                enabled: false, // غير مفعل افتراضياً
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

    // ═══════════════════════════════════════════════════════════════
    // Payment Method Change Handler
    // ═══════════════════════════════════════════════════════════════
    const paymentCards = document.querySelectorAll('.payment-method-card');
    
    paymentCards.forEach(card => {
        card.addEventListener('click', function() {
            const method = this.getAttribute('data-method');
            
            // إزالة active من الكل
            paymentCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            
            // تحديث الـ radio button
            document.querySelectorAll('input[name="payment_method"]').forEach(r => r.checked = false);
            document.getElementById('payment_method_' + method).checked = true;
            
            // إظهار/إخفاء الأقسام
            const cardSection = document.getElementById('card-payment-section');
            const bankSection = document.getElementById('bank-transfer-section');
            
            if (method === 'bank_transfer') {
                // التحويل البنكي
                bankSection.style.display = 'block';
                cardSection.style.display = 'none';
                
                // تعطيل validation حقول البطاقة
                fv.disableValidator('card_number');
                fv.disableValidator('card_name_display');
                fv.disableValidator('exp_date_display');
                fv.disableValidator('cvc');
                
                // إزالة required من حقول البطاقة
                document.getElementById('billings-card-num').required = false;
                document.getElementById('card_name_display').required = false;
                document.getElementById('exp_date_display').required = false;
                document.getElementById('cvc').required = false;
                
            } else if (method === 'card') {
                // بطاقة الائتمان
                cardSection.style.display = 'block';
                bankSection.style.display = 'none';
                
                // تفعيل validation حقول البطاقة
                fv.enableValidator('card_number');
                fv.enableValidator('card_name_display');
                fv.enableValidator('exp_date_display');
                fv.enableValidator('cvc');
                
                // إضافة required لحقول البطاقة
                document.getElementById('billings-card-num').required = true;
                document.getElementById('card_name_display').required = true;
                document.getElementById('exp_date_display').required = true;
                document.getElementById('cvc').required = true;
            }
        });
    });

    // ═══════════════════════════════════════════════════════════════
    // Bank Selection Handler
    // ═══════════════════════════════════════════════════════════════
    const bankCards = document.querySelectorAll('.bank-card');
    
    bankCards.forEach(card => {
        card.addEventListener('click', function() {
            bankCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            
            const bankId = this.getAttribute('data-bank-id');
            document.getElementById('bank_id').value = bankId;
        });
    });

    // ═══════════════════════════════════════════════════════════════
    // Form Submit Handler
    // ═══════════════════════════════════════════════════════════════
    fv.on('core.form.valid', function () {
        // التحقق من اختيار طريقة الدفع
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        
        if (!selectedMethod) {
            if (typeof toastr !== 'undefined') {
                toastr.error('يرجى اختيار طريقة الدفع');
            } else {
                alert('يرجى اختيار طريقة الدفع');
            }
            return false;
        }
        
        // التحقق من اختيار البنك في حالة التحويل البنكي
        if (selectedMethod.value === 'bank_transfer') {
            const bankId = document.getElementById('bank_id').value;
            if (!bankId) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('يرجى اختيار البنك للتحويل');
                } else {
                    alert('يرجى اختيار البنك للتحويل');
                }
                return false;
            }
        }
        
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
        'billing_phone': 10,
        'billing_commercial_number': 10,
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
    const cardNumberInput = document.getElementById('billings-card-num');
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
        const cardNumberField = document.getElementById('billings-card-num');
        if (cardNumberField) {
            cardNumberField.value = cardNumberField.value.replace(/\s/g, '');
        }
    });
});
