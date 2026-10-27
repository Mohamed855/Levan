'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const otpTimer = document.querySelector('#checkout-otp-timer');
    if (otpTimer) {
        let expiresAt = Number(otpTimer.dataset.expiresAt || 0) * 1000;
        let resendCount = Number(otpTimer.dataset.resendCount || 0);
        const maxResends = Number(otpTimer.dataset.maxResends || 3);
        const resendContainer = document.querySelector('#otp-resend-container');
        const editContainer = document.querySelector('#otp-edit-container');
        const resendBtn = document.querySelector('#btn-resend-otp');
        const resendForm = document.querySelector('#otp-resend-form');
        const remainingAttemptsSpan = document.querySelector('#otp-remaining-attempts');
        let timer = null;

        const showExpiredActions = () => {
            otpTimer.textContent = '00:00';
            otpTimer.classList.add('text-danger');
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }

            if (resendCount < maxResends) {
                if (resendContainer) resendContainer.classList.remove('d-none');
                if (editContainer) editContainer.classList.add('d-none');
            } else {
                if (resendContainer) resendContainer.classList.add('d-none');
                if (editContainer) editContainer.classList.remove('d-none');
            }
        };

        const updateOtpTimer = () => {
            const now = Date.now();
            const remaining = expiresAt - now;

            if (remaining <= 0) {
                showExpiredActions();
                return;
            }

            otpTimer.classList.remove('text-danger');
            if (resendContainer) resendContainer.classList.add('d-none');
            if (editContainer) editContainer.classList.add('d-none');

            const totalSeconds = Math.ceil(remaining / 1000);
            const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
            const seconds = String(totalSeconds % 60).padStart(2, '0');
            otpTimer.textContent = `${minutes}:${seconds}`;
        };

        const startTimer = () => {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
            updateOtpTimer();
            if (expiresAt > Date.now()) {
                timer = window.setInterval(updateOtpTimer, 500);
            }
        };

        startTimer();

        if (resendBtn) {
            resendBtn.addEventListener('click', async function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (resendBtn.disabled) return;

                const resendUrl = otpTimer.dataset.resendUrl;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || resendForm?.querySelector('input[name="_token"]')?.value;

                resendBtn.disabled = true;
                const originalHtml = resendBtn.innerHTML;
                resendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> جارٍ الإرسال...';

                if (resendUrl && csrfToken) {
                    try {
                        const response = await fetch(resendUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({})
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            if (window.toastr) {
                                window.toastr.success(data.message || 'تم إعادة إرسال رمز التحقق بنجاح.');
                            }

                            if (data.expires_at) {
                                expiresAt = Number(data.expires_at) * 1000;
                            } else {
                                expiresAt = Date.now() + 120 * 1000;
                            }

                            if (typeof data.resend_count !== 'undefined') {
                                resendCount = Number(data.resend_count);
                            } else {
                                resendCount += 1;
                            }

                            otpTimer.dataset.expiresAt = Math.floor(expiresAt / 1000);
                            otpTimer.dataset.resendCount = resendCount;

                            if (remainingAttemptsSpan) {
                                const remainingCount = Math.max(0, maxResends - resendCount);
                                remainingAttemptsSpan.textContent = `(${remainingCount} متبقية)`;
                            }

                            if (resendContainer) resendContainer.classList.add('d-none');
                            if (editContainer) editContainer.classList.add('d-none');

                            resendBtn.innerHTML = originalHtml;
                            resendBtn.disabled = false;

                            startTimer();
                            return;
                        } else {
                            if (window.toastr) {
                                window.toastr.error(data.message || 'حدث خطأ أثناء إعادة إرسال الرمز.');
                            }
                            if (typeof data.resend_count !== 'undefined') {
                                resendCount = Number(data.resend_count);
                                otpTimer.dataset.resendCount = resendCount;
                            }
                            showExpiredActions();
                            resendBtn.innerHTML = originalHtml;
                            resendBtn.disabled = false;
                            return;
                        }
                    } catch (err) {
                        console.error('AJAX resend failed, falling back to form submit:', err);
                    }
                }

                if (resendForm) {
                    resendForm.submit();
                } else {
                    resendBtn.innerHTML = originalHtml;
                    resendBtn.disabled = false;
                }
            });
        }
    }

    const otpForm = document.querySelector('#otp-form');
    if (otpForm && window.history && window.history.replaceState) {
        window.history.replaceState({ checkoutStep: 'otp' }, document.title, window.location.href);
    }

    // Force refresh if restored from Back-Forward cache (bfcache)
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });

    const paymentForm = document.querySelector('#payment-form');
    if (!paymentForm) return;

    if (paymentForm.dataset.checkoutStep === 'payment' && window.history && window.history.replaceState) {
        window.history.replaceState({ checkoutStep: 'payment' }, document.title, window.location.href);
    }

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

    const paymentOptions = document.querySelectorAll('.payment-option');
    const cardSection = document.getElementById('card-payment-section');
    const bankSection = document.getElementById('bank-transfer-section');
    const receiptSection = document.getElementById('receipt-upload-section');
    const bankCards = document.querySelectorAll('.bank-card');
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
                const transferReceiptInput = document.getElementById('transfer_receipt');
                if (transferReceiptInput) {
                    transferReceiptInput.value = '';
                    transferReceiptInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
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
