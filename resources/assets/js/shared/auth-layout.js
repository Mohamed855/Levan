function initAuthLayout() {
    // 1. Single 2FA OTP Code Input Handling
    const codeInput = document.getElementById("code");
    const recoveryInput = document.getElementById("recovery_code");

    if (codeInput) {
        // Enforce numeric only and max 6 digits
        codeInput.addEventListener("input", function (e) {
            const clean = e.target.value.replace(/\D/g, "").slice(0, 6);
            if (e.target.value !== clean) {
                e.target.value = clean;
            }
        });

        codeInput.addEventListener("paste", function (e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData)
                .getData("text")
                .trim();
            const clean = pasted.replace(/\D/g, "").slice(0, 6);
            codeInput.value = clean;
            codeInput.dispatchEvent(new Event("input", { bubbles: true }));
            codeInput.dispatchEvent(new Event("change", { bubbles: true }));
        });

        // Ensure Enter key triggers form submission smoothly
        codeInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                const form = codeInput.closest("form");
                if (form) {
                    const submitBtn = form.querySelector(
                        'button[type="submit"]',
                    );
                    if (submitBtn) {
                        submitBtn.click();
                    } else if (typeof form.requestSubmit === "function") {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            }
        });
    }

    if (recoveryInput) {
        recoveryInput.addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                const form = recoveryInput.closest("form");
                if (form) {
                    const submitBtn = form.querySelector(
                        'button[type="submit"]',
                    );
                    if (submitBtn) {
                        submitBtn.click();
                    } else if (typeof form.requestSubmit === "function") {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            }
        });
    }

    // 2. Toggle between OTP and Recovery Code in 2FA Challenge
    const toggleRecoveryLink = document.getElementById("toggleRecoveryLink");
    const codeWrapper = document.getElementById("codeInputsWrapper");
    const recoveryWrapper = document.getElementById("recoveryWrapper");
    const toggleRecoveryText = document.getElementById("toggleRecoveryText");
    const toggleRecoveryIcon = document.getElementById("toggleRecoveryIcon");

    if (toggleRecoveryLink && codeWrapper && recoveryWrapper) {
        let isRecoveryMode = !recoveryWrapper.classList.contains("d-none");

        toggleRecoveryLink.addEventListener("click", function (e) {
            e.preventDefault();
            isRecoveryMode = !isRecoveryMode;

            if (isRecoveryMode) {
                // Switch to Recovery mode
                if (codeInput) {
                    codeInput.value = "";
                    codeInput.disabled = true;
                    codeInput.dispatchEvent(
                        new Event("input", { bubbles: true }),
                    );
                }
                codeWrapper.classList.add("d-none");
                recoveryWrapper.classList.remove("d-none");
                if (recoveryInput) {
                    recoveryInput.disabled = false;
                    recoveryInput.focus();
                }
                if (toggleRecoveryText) {
                    toggleRecoveryText.textContent = "استخدام رمز المصادقة";
                } else {
                    toggleRecoveryLink.textContent = "استخدام رمز المصادقة";
                }
                if (toggleRecoveryIcon) {
                    toggleRecoveryIcon.className = "ti ti-shield-check me-1";
                }
            } else {
                // Switch to OTP mode
                if (recoveryInput) {
                    recoveryInput.value = "";
                    recoveryInput.disabled = true;
                    recoveryInput.dispatchEvent(
                        new Event("input", { bubbles: true }),
                    );
                }
                recoveryWrapper.classList.add("d-none");
                codeWrapper.classList.remove("d-none");
                if (codeInput) {
                    codeInput.disabled = false;
                    codeInput.focus();
                }
                if (toggleRecoveryText) {
                    toggleRecoveryText.textContent = "استخدام كود استرجاع";
                } else {
                    toggleRecoveryLink.textContent = "استخدام كود استرجاع";
                }
                if (toggleRecoveryIcon) {
                    toggleRecoveryIcon.className = "ti ti-key me-1";
                }
            }
        });
    }

    // 3. OTP Countdown Timer & Resend Handling
    const otpTimer = document.querySelector(
        "#checkout-otp-timer, .auth-otp-timer",
    );
    if (otpTimer) {
        let expiresAt = Number(otpTimer.dataset.expiresAt || 0) * 1000;
        let resendCount = Number(otpTimer.dataset.resendCount || 0);
        const maxResends = Number(otpTimer.dataset.maxResends || 3);
        const resendBtn = document.querySelector("#btn-resend-otp");
        const resendForm = document.querySelector("#otp-resend-form");
        let timer = null;

        const renderResendButton = (isLoading = false) => {
            if (!resendBtn) return;
            if (isLoading) {
                resendBtn.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> جارٍ الإرسال...';
                return;
            }

            const remainingCount = Math.max(0, maxResends - resendCount);
            resendBtn.innerHTML = `
                <i class="ti ti-rotate-clockwise"></i>
                <span>إعادة إرسال الرمز</span>
                <span class="text-xs text-muted" id="otp-remaining-attempts">(${remainingCount} محاولة متبقية)</span>
            `.trim();
        };

        const showExpiredActions = () => {
            otpTimer.textContent = "00:00";
            otpTimer.classList.add("text-danger");
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }

            if (resendCount < maxResends) {
                if (resendBtn) resendBtn.disabled = false;
            } else {
                if (resendBtn) resendBtn.disabled = true;
            }
        };

        const updateOtpTimer = () => {
            const now = Date.now();
            const remaining = expiresAt - now;

            if (remaining <= 0) {
                showExpiredActions();
                return;
            }

            otpTimer.classList.remove("text-danger");
            if (resendBtn) resendBtn.disabled = true;

            const totalSeconds = Math.ceil(remaining / 1000);
            const minutes = String(Math.floor(totalSeconds / 60)).padStart(
                2,
                "0",
            );
            const seconds = String(totalSeconds % 60).padStart(2, "0");
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

        renderResendButton(false);
        startTimer();

        if (resendBtn) {
            resendBtn.addEventListener("click", async function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (resendBtn.disabled || resendCount >= maxResends) return;

                const resendUrl = otpTimer.dataset.resendUrl;
                const csrfToken =
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") ||
                    resendForm?.querySelector('input[name="_token"]')?.value ||
                    document.querySelector('input[name="_token"]')?.value;

                resendBtn.disabled = true;
                renderResendButton(true);

                if (resendUrl && csrfToken) {
                    try {
                        const response = await fetch(resendUrl, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                Accept: "application/json",
                                "X-CSRF-TOKEN": csrfToken,
                                "X-Requested-With": "XMLHttpRequest",
                            },
                            body: JSON.stringify({}),
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            if (window.toastr) {
                                window.toastr.success(
                                    data.message ||
                                        "تم إعادة إرسال رمز التحقق بنجاح.",
                                );
                            }

                            if (data.expires_at) {
                                expiresAt = Number(data.expires_at) * 1000;
                            } else {
                                expiresAt = Date.now() + 120 * 1000;
                            }

                            if (typeof data.resend_count !== "undefined") {
                                resendCount = Number(data.resend_count);
                            } else {
                                resendCount += 1;
                            }

                            otpTimer.dataset.expiresAt = Math.floor(
                                expiresAt / 1000,
                            );
                            otpTimer.dataset.resendCount = resendCount;

                            renderResendButton(false);
                            resendBtn.disabled = true;

                            startTimer();
                            return;
                        } else {
                            if (window.toastr) {
                                window.toastr.error(
                                    data.message ||
                                        "حدث خطأ أثناء إعادة إرسال الرمز.",
                                );
                            }
                            if (typeof data.resend_count !== "undefined") {
                                resendCount = Number(data.resend_count);
                                otpTimer.dataset.resendCount = resendCount;
                            }
                            renderResendButton(false);
                            showExpiredActions();
                            return;
                        }
                    } catch (err) {
                        console.error(
                            "AJAX resend failed, falling back to form submit:",
                            err,
                        );
                    }
                }

                if (resendForm && resendCount < maxResends) {
                    resendForm.submit();
                } else {
                    renderResendButton(false);
                    if (expiresAt <= Date.now() && resendCount < maxResends) {
                        resendBtn.disabled = false;
                    } else {
                        resendBtn.disabled = true;
                    }
                }
            });
        }
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAuthLayout);
} else {
    initAuthLayout();
}
