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

    // إخفاء رسائل invalid-feedback الأصلية عند البداية
    form.querySelectorAll(".invalid-feedback").forEach((el) =>
        el.classList.add("hidden"),
    );
    if (btnSpinner) btnSpinner.classList.add("hidden");

    // ═══════════════════════════════════════════════════════════
    // قواعد التحقق
    // ═══════════════════════════════════════════════════════════
    const fieldRules = {
        customer_name: [
            { type: "notEmpty", message: "الاسم مطلوب" },
            {
                type: "minLength",
                min: 3,
                message: "الاسم يجب أن يكون 3 أحرف على الأقل",
            },
        ],
        customer_email: [
            { type: "notEmpty", message: "البريد الإلكتروني مطلوب" },
            { type: "email", message: "صيغة البريد الإلكتروني غير صحيحة" },
        ],
        message: [
            { type: "notEmpty", message: "الرسالة مطلوبة" },
            {
                type: "minLength",
                min: 10,
                message: "الرسالة يجب أن تكون 10 أحرف على الأقل",
            },
        ],
    };

    // ═══════════════════════════════════════════════════════════
    // engine التحقق
    // ═══════════════════════════════════════════════════════════
    function validateField(field, rules) {
        const value = field.value.trim();
        let errorMsg = "";

        for (const rule of rules) {
            if (rule.type === "notEmpty" && !value) {
                errorMsg = rule.message;
                break;
            }
            if (rule.type === "minLength" && value.length < rule.min) {
                errorMsg = rule.message;
                break;
            }
            if (
                rule.type === "email" &&
                value &&
                !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
            ) {
                errorMsg = rule.message;
                break;
            }
        }

        applyFieldState(field, errorMsg);
        return errorMsg === "";
    }

    function applyFieldState(field, errorMsg) {
        const wrapper = field.closest("div");
        if (!wrapper) return;

        // إزالة الحالات السابقة
        field.classList.remove("field-invalid", "field-valid");
        wrapper.querySelector(".field-error")?.remove();

        // إخفاء invalid-feedback الأصلي دائماً
        const feedback = wrapper.querySelector(".invalid-feedback");
        if (feedback) feedback.classList.add("hidden");

        if (errorMsg) {
            field.classList.add("field-invalid");

            const errorEl = document.createElement("p");
            errorEl.className = "field-error";
            errorEl.textContent = errorMsg;
            wrapper.appendChild(errorEl);
        } else if (field.value.trim()) {
            field.classList.add("field-valid");
        }
    }

    // ═══════════════════════════════════════════════════════════
    // ربط التحقق الفوري (blur + input)
    // ═══════════════════════════════════════════════════════════
    Object.entries(fieldRules).forEach(([name, rules]) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (!field) return;

        // عند مغادرة الحقل → تحقق فوري
        field.addEventListener("blur", () => validateField(field, rules));

        // أثناء الكتابة → فقط إذا كان هناك خطأ مسبق
        field.addEventListener("input", () => {
            if (field.classList.contains("field-invalid")) {
                validateField(field, rules);
            }
        });
    });

    // ═══════════════════════════════════════════════════════════
    // إرسال الفورم
    // ═══════════════════════════════════════════════════════════
    form.addEventListener("submit", function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (isSubmitting) return;

        // التحقق من كل الحقول
        let valid = true;
        Object.entries(fieldRules).forEach(([name, rules]) => {
            const field = form.querySelector(`[name="${name}"]`);
            if (field && !validateField(field, rules)) valid = false;
        });

        if (!valid) {
            const firstError = form.querySelector(".field-error");
            if (firstError)
                firstError.scrollIntoView({
                    behavior: "smooth",
                    block: "center",
                });

            if (typeof toastr !== "undefined") {
                toastr.error("الرجاء تعبئة جميع الحقول المطلوبة بشكل صحيح");
            }
            return;
        }

        // التحقق من تحميل reCAPTCHA
        if (typeof grecaptcha === "undefined") {
            if (typeof toastr !== "undefined") {
                toastr.error("reCAPTCHA لم يتم تحميلها بعد، يرجى تحديث الصفحة");
            }
            return;
        }

        setLoadingState(true);

        grecaptcha.ready(function () {
            grecaptcha
                .execute(window.recaptchaSiteKey, { action: "contact_form" })
                .then(function (token) {
                    if (!token) throw new Error("No token");
                    if (recaptchaInput) recaptchaInput.value = token;
                    form.submit();
                })
                .catch(function (error) {
                    console.error("reCAPTCHA error:", error);
                    setLoadingState(false);
                    if (typeof toastr !== "undefined") {
                        toastr.error(
                            "فشل التحقق من reCAPTCHA، يرجى المحاولة مجدداً",
                        );
                    }
                });
        });
    });

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
