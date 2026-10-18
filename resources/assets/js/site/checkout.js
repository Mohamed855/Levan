'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const otpTimer = document.querySelector('#checkout-otp-timer');
    if (otpTimer) {
        const expiresAt = Number(otpTimer.dataset.expiresAt || 0) * 1000;
        const updateOtpTimer = () => {
            const remaining = Math.max(0, expiresAt - Date.now());
            const totalSeconds = Math.ceil(remaining / 1000);
            const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
            const seconds = String(totalSeconds % 60).padStart(2, '0');
            otpTimer.textContent = `${minutes}:${seconds}`;

            if (remaining <= 0) {
                otpTimer.classList.add('text-danger');
                window.clearInterval(timer);
            }
        };

        const timer = window.setInterval(updateOtpTimer, 1000);
        updateOtpTimer();
    }

    const paymentForm = document.querySelector('#payment-form');
    if (!paymentForm) return;

    // Initialize Select2 on City select
    const initCitySelect2 = () => {
        if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
            const $city = window.jQuery('#billing_city_id');
            if ($city.length && !$city.hasClass('select2-hidden-accessible')) {
                $city.select2({
                    placeholder: $city.data('placeholder') || 'اختر المدينة (اختياري)',
                    allowClear: true,
                    width: '100%',
                    dir: 'rtl'
                });
            }
        }
    };
    initCitySelect2();

    // Auto-save and restore Step 1 inputs across reloads
    const storageKey = 'checkout_step1_draft_' + (window.location.pathname);
    const draftInputs = paymentForm.querySelectorAll(
        'input[name^="billing_"]:not([type="password"]), select[name^="billing_"], textarea[name^="billing_"]'
    );

    try {
        const savedDraft = JSON.parse(sessionStorage.getItem(storageKey) || '{}');
        draftInputs.forEach((input) => {
            if (!input.value && savedDraft[input.name]) {
                input.value = savedDraft[input.name];
                if (input.tagName === 'SELECT' && window.jQuery) {
                    window.jQuery(input).val(savedDraft[input.name]).trigger('change');
                }
            }
        });
    } catch (_) {}

    paymentForm.addEventListener('input', function (e) {
        if (!e.target.name || !e.target.name.startsWith('billing_') || e.target.type === 'password') return;
        try {
            const currentDraft = JSON.parse(sessionStorage.getItem(storageKey) || '{}');
            currentDraft[e.target.name] = e.target.value;
            sessionStorage.setItem(storageKey, JSON.stringify(currentDraft));
        } catch (_) {}
    });

    if (window.jQuery) {
        window.jQuery('#billing_city_id').on('change.select2', function () {
            try {
                const currentDraft = JSON.parse(sessionStorage.getItem(storageKey) || '{}');
                currentDraft['billing_city_id'] = this.value;
                sessionStorage.setItem(storageKey, JSON.stringify(currentDraft));
            } catch (_) {}
        });
    }

    let selectedFiles = [];
    const paymentOptions = document.querySelectorAll('.payment-option');
    const cardSection = document.getElementById('card-payment-section');
    const bankSection = document.getElementById('bank-transfer-section');
    const receiptSection = document.getElementById('receipt-upload-section');
    const bankCards = document.querySelectorAll('.bank-card');
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('transfer_receipt');
    const filesPreview = document.getElementById('files-preview');
    const imageModal = document.getElementById('image-modal');
    const modalClose = document.getElementById('modal-close');

    paymentOptions.forEach((option) => {
        option.addEventListener('click', function () {
            const method = this.dataset.method;
            paymentOptions.forEach((item) => item.classList.remove('active'));
            this.classList.add('active');

            document.querySelectorAll('input[name="payment_method"]').forEach((input) => {
                input.checked = false;
            });
            const methodInput = document.getElementById('payment_method_' + method);
            if (methodInput) {
                methodInput.checked = true;
                methodInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (method === 'card') {
                cardSection?.classList.remove('hidden');
                bankSection?.classList.add('hidden');
                receiptSection?.classList.add('hidden');
                const bankId = document.getElementById('bank_id');
                if (bankId) {
                    bankId.value = '';
                    bankId.dispatchEvent(new Event('change', { bubbles: true }));
                }
                selectedFiles = [];
                updateFilesPreview();
                return;
            }

            cardSection?.classList.add('hidden');
            bankSection?.classList.remove('hidden');
        });
    });

    bankCards.forEach((card) => {
        card.addEventListener('click', function () {
            bankCards.forEach((item) => item.classList.remove('selected'));
            this.classList.add('selected');

            const bankId = document.getElementById('bank_id');
            if (bankId) {
                bankId.value = this.dataset.bankId || '';
                bankId.dispatchEvent(new Event('change', { bubbles: true }));
            }
            receiptSection?.classList.remove('hidden');
        });
    });

    if (uploadArea && fileInput) {
        uploadArea.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => {
            const file = fileInput.files?.[0];
            if (!file) return;

            selectedFiles = [file];
            updateFilesPreview();
            uploadArea.classList.add('has-files');
        });
    }

    function updateFilesPreview() {
        if (!filesPreview || !fileInput) return;

        filesPreview.replaceChildren();
        selectedFiles.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'file-item';

            if (file.type.startsWith('image/')) {
                const image = document.createElement('img');
                image.alt = file.name;
                image.className = 'file-item-preview';
                const reader = new FileReader();
                reader.addEventListener('load', () => {
                    image.src = String(reader.result || '');
                });
                reader.readAsDataURL(file);
                item.append(image);
            } else {
                const icon = document.createElement('div');
                icon.className = 'file-item-pdf';
                icon.innerHTML = '<i class="ti ti-file-type-pdf"></i>';
                item.append(icon);
            }

            const info = document.createElement('div');
            info.className = 'file-item-info';
            const name = document.createElement('div');
            name.className = 'file-item-name';
            name.textContent = file.name;
            const size = document.createElement('div');
            size.className = 'file-item-size';
            size.textContent = formatFileSize(file.size);
            info.append(name, size);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn btn-danger btn-remove-file';
            remove.setAttribute('aria-label', 'حذف الملف');
            remove.innerHTML = '<i class="ti ti-trash"></i>';
            remove.addEventListener('click', () => {
                selectedFiles.splice(index, 1);
                syncFileInput();
                updateFilesPreview();
                if (selectedFiles.length === 0) uploadArea?.classList.remove('has-files');
            });

            item.append(info, remove);
            filesPreview.append(item);
        });

        syncFileInput();
    }

    function syncFileInput() {
        if (!fileInput) return;
        const transfer = new DataTransfer();
        selectedFiles.forEach((file) => transfer.items.add(file));
        fileInput.files = transfer.files;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const units = ['Bytes', 'KB', 'MB'];
        const index = Math.floor(Math.log(bytes) / Math.log(1024));
        return Math.round((bytes / Math.pow(1024, index)) * 100) / 100 + ' ' + units[index];
    }

    modalClose?.addEventListener('click', () => imageModal?.classList.remove('active'));
    imageModal?.addEventListener('click', (event) => {
        if (event.target === imageModal) imageModal.classList.remove('active');
    });

    const notifyError = (message) => {
        if (window.toastr && typeof window.toastr.error === 'function') {
            window.toastr.error(message);
        } else if (typeof toastr !== 'undefined' && typeof toastr.error === 'function') {
            toastr.error(message);
        } else {
            alert(message);
        }
    };

    const isPaymentStep = () => {
        if (paymentForm.dataset.checkoutStep === 'payment') {
            return true;
        }
        if (document.getElementById('submit-payment-btn')) {
            return true;
        }
        const paymentGrid = document.querySelector('.payment-options-grid');
        return Boolean(paymentGrid && !paymentGrid.closest('.d-none'));
    };

    paymentForm.addEventListener('submit', function (event) {
        if (!isPaymentStep()) {
            return;
        }

        const checkedMethod = paymentForm.querySelector('input[name="payment_method"]:checked')?.value;

        if (!checkedMethod) {
            event.preventDefault();
            event.stopImmediatePropagation();
            notifyError('يرجى اختيار طريقة الدفع للمتابعة');
            return false;
        }

        if (checkedMethod === 'bank_transfer') {
            const bankId = document.getElementById('bank_id')?.value;
            if (!bankId) {
                event.preventDefault();
                event.stopImmediatePropagation();
                notifyError('يرجى اختيار الحساب البنكي المراد التحويل إليه');
                return false;
            }

            const receiptInput = document.getElementById('transfer_receipt');
            const receiptFile = receiptInput?.files?.[0] || selectedFiles?.[0];
            if (!receiptFile) {
                event.preventDefault();
                event.stopImmediatePropagation();
                notifyError('يرجى إرفاق إيصال التحويل البنكي');
                return false;
            }

            if (receiptFile.size > 5 * 1024 * 1024) {
                event.preventDefault();
                event.stopImmediatePropagation();
                notifyError('حجم إيصال التحويل يجب ألا يتجاوز 5 ميجابايت');
                return false;
            }

            const allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];
            const fileExt = receiptFile.name.split('.').pop()?.toLowerCase();
            if (!allowedExts.includes(fileExt)) {
                event.preventDefault();
                event.stopImmediatePropagation();
                notifyError('صيغة الملف غير مدعومة، يرجى رفع ملف بصيغة JPG أو PNG أو PDF');
                return false;
            }
        }

        const submitBtn = paymentForm.querySelector('button[type="submit"]') || document.getElementById('submit-payment-btn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> جاري المعالجة...';
        }
    }, true);

    paymentForm.addEventListener('formValidationPassed', () => {
        const submitButton = paymentForm.querySelector('button[type="submit"]');
        if (!submitButton) return;

        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> جاري المعالجة...';
    });
});
