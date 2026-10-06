document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form');
    const submitBtn = document.getElementById('submit-form');

    if (!form || !submitBtn) return;

    const btnText = submitBtn.querySelector('.btn-text');
    const btnSpinner = submitBtn.querySelector('.spinner-border');
    const recaptchaInput = document.getElementById('g-recaptcha-response');

    let isSubmitting = false;

    if (btnSpinner) btnSpinner.classList.add('hidden');

    // reCAPTCHA is an integration concern only. Field rules come exclusively
    // from StoreTrialRequest through SharedFormValidation.
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        if (isSubmitting) return;

        const valid = await window.SharedFormValidation?.validateFields(form, [
            'customer_name', 'customer_email', 'customer_phone'
        ]);
        if (!valid) {
            return;
        }

        if (typeof grecaptcha === 'undefined') {
            if (typeof toastr !== 'undefined') {
                toastr.error('reCAPTCHA لم يتم تحميلها بعد، يرجى تحديث الصفحة');
            }
            return;
        }

        setLoadingState(true);

        grecaptcha.ready(function () {
            grecaptcha.execute(window.recaptchaSiteKey, { action: 'trial_form' })
                .then(function (token) {
                    if (!token) throw new Error('No token');
                    if (recaptchaInput) recaptchaInput.value = token;
                    HTMLFormElement.prototype.submit.call(form);
                })
                .catch(function (error) {
                    console.error('reCAPTCHA error:', error);
                    setLoadingState(false);
                    if (typeof toastr !== 'undefined') {
                        toastr.error('فشل التحقق من reCAPTCHA، يرجى المحاولة مجدداً');
                    }
                });
        });
    }, true);

    // ═══════════════════════════════════════════════════════════
    // حالة زر الإرسال
    // ═══════════════════════════════════════════════════════════
    function setLoadingState(loading) {
        isSubmitting = loading;
        submitBtn.disabled = loading;

        if (loading) {
            if (btnText) btnText.textContent = 'جاري الإرسال...';
            if (btnSpinner) btnSpinner.classList.remove('hidden');
        } else {
            if (btnText) btnText.textContent = 'طلب تجربتك المجانية';
            if (btnSpinner) btnSpinner.classList.add('hidden');
        }
    }
});
