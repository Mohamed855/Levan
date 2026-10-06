document.addEventListener("DOMContentLoaded", function () {
    const contactButton = document.getElementById("navbar-contact-btn");
    const contactSection = document.getElementById("contact");

    contactButton?.addEventListener("click", function (event) {
        event.preventDefault();
        contactSection?.scrollIntoView({ behavior: "smooth", block: "start" });
    });

    const form = document.getElementById("form");
    const submitBtn = document.getElementById("submit-form");

    if (!form || !submitBtn) return;

    const btnText = submitBtn.querySelector(".btn-text");
    const btnSpinner = submitBtn.querySelector(".spinner-border");
    const recaptchaInput = document.getElementById("g-recaptcha-response");

    let isSubmitting = false;

    if (btnSpinner) btnSpinner.classList.add("hidden");

    // Business validation is owned by StoreContactRequest and is requested
    // through SharedFormValidation before reCAPTCHA runs.
    form.addEventListener("submit", function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        if (isSubmitting) return;

        window.SharedFormValidation
            ?.validateFields(form, ["customer_name", "customer_email", "message"])
            .then((valid) => {
                if (!valid) return;

                if (typeof grecaptcha === "undefined") {
                    toastr?.error?.("reCAPTCHA لم يتم تحميلها بعد، يرجى تحديث الصفحة");
                    return;
                }

                setLoadingState(true);

                grecaptcha.ready(function () {
                    grecaptcha
                        .execute(window.recaptchaSiteKey, { action: "contact_form" })
                        .then(function (token) {
                            if (!token) throw new Error("No token");
                            if (recaptchaInput) recaptchaInput.value = token;
                            HTMLFormElement.prototype.submit.call(form);
                        })
                        .catch(function (error) {
                            console.error("reCAPTCHA error:", error);
                            setLoadingState(false);
                            toastr?.error?.("فشل التحقق من reCAPTCHA، يرجى المحاولة مجدداً");
                        });
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
            if (btnText) btnText.textContent = "جاري الإرسال...";
            if (btnSpinner) btnSpinner.classList.remove("hidden");
        } else {
            if (btnText) btnText.textContent = "إرسال الرسالة";
            if (btnSpinner) btnSpinner.classList.add("hidden");
        }
    }
});
