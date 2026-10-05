'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const paymentForm = document.querySelector('#payment-form');
    if (!paymentForm) return;

    let selectedFiles = [];
    let currentPaymentMethod = null;

    // ═══════════════════════════════════════════════════════════
    // Cleave.js Configuration
    // ═══════════════════════════════════════════════════════════
    const creditCardMask = document.querySelector('.billing-card-mask');
    const expiryDateMask = document.querySelector('.billing-expiry-date-mask');
    const cvvMask = document.querySelector('.billing-cvv-mask');

    if (typeof Cleave !== 'undefined') {
        if (creditCardMask) {
            new Cleave(creditCardMask, {
                creditCard: true,
                onCreditCardTypeChanged: function (type) {
                    const cardTypeEl = document.querySelector('.card-type');
                    if (cardTypeEl && type && type !== 'unknown') {
                        cardTypeEl.innerHTML =
                            '<img src="/static/payments/cards/' + type + '-cc.png" height="28"/>';
                    } else if (cardTypeEl) {
                        cardTypeEl.innerHTML = '';
                    }
                }
            });
        }
        if (expiryDateMask) {
            new Cleave(expiryDateMask, { date: true, delimiter: '/', datePattern: ['m', 'y'] });
        }
        if (cvvMask) {
            new Cleave(cvvMask, { numeral: true, numeralPositiveOnly: true });
        }
    }

    // ═══════════════════════════════════════════════════════════
    // Validation Engine
    // ═══════════════════════════════════════════════════════════

    /**
     * يتحقق من حقل واحد بناءً على قواعده ويعرض / يخفي رسالة الخطأ
     * يُرجع true إذا كان الحقل صحيحاً
     */
    function validateField(field, rules) {
        const value = field.value.trim();
        let errorMsg = '';

        for (const rule of rules) {
            if (rule.type === 'notEmpty' && !value) {
                errorMsg = rule.message; break;
            }
            if (rule.type === 'minLength' && value.length < rule.min) {
                errorMsg = rule.message; break;
            }
            if (rule.type === 'maxLength' && value.length > rule.max) {
                errorMsg = rule.message; break;
            }
            if (rule.type === 'regexp' && value && !rule.regexp.test(value)) {
                errorMsg = rule.message; break;
            }
            if (rule.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                errorMsg = rule.message; break;
            }
            if (rule.type === 'identical' && value !== rule.compare()) {
                errorMsg = rule.message; break;
            }
        }

        applyFieldState(field, errorMsg);
        return errorMsg === '';
    }

    function applyFieldState(field, errorMsg) {
        const wrapper = field.closest('div');
        if (!wrapper) return;

        // إزالة الحالات السابقة
        field.classList.remove('field-invalid', 'field-valid');
        wrapper.querySelector('.field-error')?.remove();

        if (errorMsg) {
            field.classList.add('field-invalid');

            const errorEl = document.createElement('p');
            errorEl.className = 'field-error';
            errorEl.textContent = errorMsg;
            wrapper.appendChild(errorEl);

        } else if (field.value.trim()) {
            field.classList.add('field-valid');
        }
    }

    // ═══════════════════════════════════════════════════════════
    // قواعد التحقق لكل حقل
    // ═══════════════════════════════════════════════════════════
    const fieldRules = {
        'billing_company_name': [
            { type: 'notEmpty', message: 'اسم الشركة مطلوب' },
            { type: 'minLength', min: 3, message: 'اسم الشركة يجب أن يكون 3 أحرف على الأقل' },
            { type: 'maxLength', max: 100, message: 'اسم الشركة يجب ألا يتجاوز 100 حرف' }
        ],
        'billing_commercial_number': [
            { type: 'notEmpty', message: 'رقم السجل التجاري مطلوب' },
            { type: 'regexp', regexp: /^[0-9]{10}$/, message: 'رقم السجل التجاري يجب أن يتكون من 10 أرقام' }
        ],
        'billing_name': [
            { type: 'notEmpty', message: 'اسم المستخدم مطلوب' },
            { type: 'minLength', min: 3, message: 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل' },
            { type: 'maxLength', max: 50, message: 'اسم المستخدم يجب ألا يتجاوز 50 حرفاً' }
        ],
        'billing_email': [
            { type: 'notEmpty', message: 'البريد الإلكتروني مطلوب' },
            { type: 'email', message: 'صيغة البريد الإلكتروني غير صحيحة' }
        ],
        'billing_phone': [
            { type: 'notEmpty', message: 'رقم الجوال مطلوب' },
            { type: 'regexp', regexp: /^(05|5)[0-9]{8}$/, message: 'رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام' }
        ],
        'billing_password': [
            { type: 'notEmpty', message: 'كلمة المرور مطلوبة' },
            { type: 'minLength', min: 8, message: 'كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل' }
        ],
        'billing_password_confirmation': [
            { type: 'notEmpty', message: 'يرجى تأكيد كلمة المرور' },
            {
                type: 'identical',
                compare: () => {
                    const p = document.getElementById('billing_password');
                    return p ? p.value : '';
                },
                message: 'كلمة المرور غير متطابقة'
            }
        ],
        // حقول البطاقة — تُفعَّل فقط عند اختيار طريقة البطاقة
        'billings-card-num': [
            { type: 'notEmpty', message: 'رقم البطاقة مطلوب' },
            { type: 'minLength', min: 13, message: 'رقم البطاقة غير صحيح' }
        ],
        'card_name_display': [
            { type: 'notEmpty', message: 'اسم حامل البطاقة مطلوب' },
            { type: 'minLength', min: 3, message: 'الاسم يجب أن يكون 3 أحرف على الأقل' },
            { type: 'regexp', regexp: /^[A-Za-z\s]+$/, message: 'الاسم يجب أن يكون بأحرف إنجليزية فقط' }
        ],
        'exp_date_display': [
            { type: 'notEmpty', message: 'تاريخ الانتهاء مطلوب' },
            { type: 'regexp', regexp: /^(0[1-9]|1[0-2])\/([0-9]{2})$/, message: 'تاريخ الانتهاء غير صالح (MM/YY)' }
        ],
        'cvc': [
            { type: 'notEmpty', message: 'رمز الأمان مطلوب' },
            { type: 'regexp', regexp: /^[0-9]{3,4}$/, message: 'رمز الأمان يجب أن يكون 3 أو 4 أرقام' }
        ]
    };

    // الحقول الأساسية دائماً مفعّلة
    const baseFields = [
        'billing_company_name',
        'billing_commercial_number',
        'billing_name',
        'billing_email',
        'billing_phone',
        'billing_password',
        'billing_password_confirmation'
    ];

    // حقول البطاقة — تُفعَّل فقط عند الحاجة
    const cardFields = ['billings-card-num', 'card_name_display', 'exp_date_display', 'cvc'];

    // ═══════════════════════════════════════════════════════════
    // ربط التحقق الفوري (blur + input) لكل حقل
    // ═══════════════════════════════════════════════════════════
    function attachLiveValidation(fieldId) {
        const field = document.getElementById(fieldId);
        const rules = fieldRules[fieldId];
        if (!field || !rules) return;

        // منع تكرار الربط
        if (field.dataset.validationAttached) return;
        field.dataset.validationAttached = 'true';

        // عند مغادرة الحقل → تحقق فوري دائماً
        field.addEventListener('blur', function () {
            validateField(this, rules);
        });

        // أثناء الكتابة → تحقق فوري إذا كان هناك خطأ مسبق
        field.addEventListener('input', function () {
            const wrapper = this.closest('div');
            if (wrapper && wrapper.querySelector('.field-error')) {
                validateField(this, rules);
            }
        });
    }

    // ربط الحقول الأساسية فور تحميل الصفحة
    baseFields.forEach(attachLiveValidation);

    // إعادة التحقق على تأكيد كلمة المرور عند تغيير كلمة المرور الأصلية
    const passwordField = document.getElementById('billing_password');
    const passwordConfirmField = document.getElementById('billing_password_confirmation');
    if (passwordField && passwordConfirmField) {
        passwordField.addEventListener('input', function () {
            const wrapper = passwordConfirmField.closest('div');
            const hasError = wrapper && wrapper.querySelector('.field-error');
            if (hasError || passwordConfirmField.value) {
                validateField(passwordConfirmField, fieldRules['billing_password_confirmation']);
            }
        });
    }

    // ═══════════════════════════════════════════════════════════
    // التحقق الكامل عند الإرسال
    // ═══════════════════════════════════════════════════════════
    function validateAll() {
        let valid = true;

        // حقول أساسية
        baseFields.forEach(id => {
            const field = document.getElementById(id);
            if (field && fieldRules[id]) {
                if (!validateField(field, fieldRules[id])) valid = false;
            }
        });

        // طريقة الدفع
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (!selectedMethod) {
            showError('يرجى اختيار طريقة الدفع');
            valid = false;
        } else if (selectedMethod.value === 'card') {
            cardFields.forEach(id => {
                const field = document.getElementById(id);
                if (field && fieldRules[id]) {
                    if (!validateField(field, fieldRules[id])) valid = false;
                }
            });
        } else if (selectedMethod.value === 'bank_transfer') {
            const bankIdField = document.getElementById('bank_id');
            if (!bankIdField || !bankIdField.value) {
                showError('يرجى اختيار البنك للتحويل');
                valid = false;
            }
            if (selectedFiles.length === 0) {
                showError('يرجى رفع إيصال التحويل البنكي');
                valid = false;
            }
        }

        return valid;
    }

    // ═══════════════════════════════════════════════════════════
    // Payment Method Selection
    // ═══════════════════════════════════════════════════════════
    const paymentOptions = document.querySelectorAll('.payment-option');
    const cardSection = document.getElementById('card-payment-section');
    const bankSection = document.getElementById('bank-transfer-section');
    const receiptSection = document.getElementById('receipt-upload-section');

    paymentOptions.forEach(option => {
        option.addEventListener('click', function () {
            const method = this.getAttribute('data-method');
            currentPaymentMethod = method;

            // تفعيل الاختيار البصري
            paymentOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');

            // تحديد radio button المخفي
            document.querySelectorAll('input[name="payment_method"]').forEach(r => r.checked = false);
            const methodInput = document.getElementById('payment_method_' + method);
            if (methodInput) methodInput.checked = true;

            if (method === 'card') {
                if (cardSection) cardSection.classList.remove('hidden');
                if (bankSection) bankSection.classList.add('hidden');
                if (receiptSection) receiptSection.classList.add('hidden');

                // ربط التحقق الفوري لحقول البطاقة
                cardFields.forEach(attachLiveValidation);

                // إعادة تعيين البنك والملفات
                const bankIdField = document.getElementById('bank_id');
                if (bankIdField) bankIdField.value = '';
                selectedFiles = [];
                updateFilesPreview();

            } else if (method === 'bank_transfer') {
                if (cardSection) cardSection.classList.add('hidden');
                if (bankSection) bankSection.classList.remove('hidden');

                // مسح قيم وأخطاء البطاقة
                cardFields.forEach(id => {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.value = '';
                    el.classList.remove('border-red-500', 'border-green-500');
                    const wrapper = el.closest('div');
                    const errorEl = wrapper && wrapper.querySelector('.field-error');
                    if (errorEl) errorEl.remove();
                });
            }
        });
    });

    // ═══════════════════════════════════════════════════════════
    // Bank Selection
    // ═══════════════════════════════════════════════════════════
    const bankCards = document.querySelectorAll('.bank-card');

    bankCards.forEach(card => {
        card.addEventListener('click', function () {
            bankCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');

            const bankIdField = document.getElementById('bank_id');
            if (bankIdField) bankIdField.value = this.getAttribute('data-bank-id');

            if (receiptSection) receiptSection.classList.remove('hidden');
        });
    });

    // ═══════════════════════════════════════════════════════════
    // File Upload
    // ═══════════════════════════════════════════════════════════
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('transfer_receipt');
    const filesPreview = document.getElementById('files-preview');

    if (uploadArea && fileInput) {
        uploadArea.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', function (e) {
            const files = Array.from(e.target.files);
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            const maxSize = 5 * 1024 * 1024;

            // ✅ خذ أول ملف فقط
            const file = files[0];
            if (!file) return;

            if (!allowedTypes.includes(file.type)) {
                showError(`${file.name}: نوع الملف غير مدعوم`);
                return;
            }
            if (file.size > maxSize) {
                showError(`${file.name}: حجم الملف كبير جداً (الحد الأقصى 5MB)`);
                return;
            }

            // ✅ استبدل بدل الإضافة
            selectedFiles = [file];

            updateFilesPreview();
            uploadArea.classList.add('has-files');
            if (typeof toastr !== 'undefined') toastr.success('تم إضافة الملف');
        });
    }

    function updateFilesPreview() {
        if (!filesPreview) return;
        filesPreview.innerHTML = '';

        selectedFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';

            let preview = '';
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = fileItem.querySelector('.file-item-preview');
                    if (img) img.src = e.target.result;
                };
                reader.readAsDataURL(file);
                preview = `<img src="" alt="${file.name}" class="file-item-preview">`;
            } else {
                preview = `<div class="file-item-pdf"><i class="ti ti-file-type-pdf"></i></div>`;
            }

            fileItem.innerHTML = `
                ${preview}
                <div class="file-item-info">
                    <div class="file-item-name">${file.name}</div>
                    <div class="file-item-size">${formatFileSize(file.size)}</div>
                </div>
                <button type="button" class="btn btn-danger btn-remove-file" data-index="${index}">
                    <i class="ti ti-trash"></i>
                </button>
            `;

            filesPreview.appendChild(fileItem);
        });

        filesPreview.querySelectorAll('.btn-remove-file').forEach(btn => {
            btn.addEventListener('click', function () {
                selectedFiles.splice(parseInt(this.getAttribute('data-index')), 1);
                updateFilesPreview();
                if (selectedFiles.length === 0 && uploadArea) uploadArea.classList.remove('has-files');
            });
        });

        updateFileInput();
    }

    function updateFileInput() {
        if (!fileInput) return;
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    // ═══════════════════════════════════════════════════════════
    // Image Modal
    // ═══════════════════════════════════════════════════════════
    const imageModal = document.getElementById('image-modal');
    const modalClose = document.getElementById('modal-close');

    if (modalClose) {
        modalClose.addEventListener('click', () => imageModal && imageModal.classList.remove('active'));
    }
    if (imageModal) {
        imageModal.addEventListener('click', function (e) {
            if (e.target === imageModal) imageModal.classList.remove('active');
        });
    }

    // ═══════════════════════════════════════════════════════════
    // Form Submit
    // ═══════════════════════════════════════════════════════════
    paymentForm.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!validateAll()) {
            // الانتقال للحقل الأول الذي به خطأ
            const firstError = paymentForm.querySelector('.field-error');
            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const submitBtn = paymentForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span> جاري المعالجة...';
        }

        paymentForm.submit();
    });

    // ═══════════════════════════════════════════════════════════
    // Input Filters
    // ═══════════════════════════════════════════════════════════
    const numericFields = { 'billing_phone': 10, 'billing_commercial_number': 10 };
    Object.entries(numericFields).forEach(([fieldId, maxLength]) => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, maxLength);
            });
        }
    });

    const cardNameInput = document.getElementById('card_name_display');
    if (cardNameInput) {
        cardNameInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^a-zA-Z\s]/g, '').toUpperCase();
        });
    }

    const billingNameInput = document.getElementById('billing_name');
    if (billingNameInput) {
        billingNameInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^a-zA-Z\u0600-\u06FF\s]/g, '');
        });
    }

    function showError(message) {
        if (typeof toastr !== 'undefined') {
            toastr.error(message);
        } else {
            alert(message);
        }
    }



});
