"use strict";

document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("subscription-wizard-form");
    if (!form) {
        return;
    }

    const companiesData = window.companiesData || [];
    const plansData = window.plansData || [];

    function formatNumber(number) {
        return new Intl.NumberFormat("en-US", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(number);
    }

    function setElemText(id, text) {
        const el = document.getElementById(id);
        if (el) el.textContent = text;
    }

    function setElemValue(id, val) {
        const el = document.getElementById(id);
        if (el) el.value = val;
    }

    function renderLastSubscription(targetEl, statusLabel, planName, endsAt) {
        if (!targetEl) return;
        targetEl.textContent = "";

        if (!planName && !statusLabel && !endsAt) {
            const span = document.createElement("span");
            span.textContent = "لا يوجد اشتراك سابق";
            targetEl.appendChild(span);
            return;
        }

        const badge = document.createElement("span");
        badge.className = "badge bg-danger me-1";
        badge.textContent = String(statusLabel || "منتهي");
        targetEl.appendChild(badge);
        targetEl.appendChild(
            document.createTextNode(
                ` باقة: ${String(planName || "-")} (انتهى بتاريخ: ${String(endsAt || "-")})`,
            ),
        );
    }

    function renderFeatureBadges(container, features, badgeClass, emptyNodeFactory) {
        if (!container) return;
        container.textContent = "";

        if (Array.isArray(features) && features.length > 0) {
            features.forEach((f) => {
                const badge = document.createElement("span");
                badge.className = badgeClass;
                badge.textContent = String(f);
                container.appendChild(badge);
            });
            return;
        }

        if (typeof emptyNodeFactory === "function") {
            emptyNodeFactory(container);
        }
    }

    function formatLocalYmd(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }

    function parseYmdLocal(value) {
        if (!value || typeof value !== "string") return null;
        const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(value.trim());
        if (!match) {
            const parsed = new Date(value);
            return isNaN(parsed.getTime()) ? null : parsed;
        }
        return new Date(
            parseInt(match[1], 10),
            parseInt(match[2], 10) - 1,
            parseInt(match[3], 10),
        );
    }

    function addMonthsNoOverflow(startDate, months) {
        const targetYear = startDate.getFullYear();
        const targetMonth = startDate.getMonth() + months;
        const day = startDate.getDate();
        const result = new Date(targetYear, targetMonth, 1);
        const daysInTargetMonth = new Date(
            result.getFullYear(),
            result.getMonth() + 1,
            0,
        ).getDate();
        result.setDate(Math.min(day, daysInTargetMonth));
        return result;
    }

    let appliedCoupon = null;

    // Company selection handler
    function updateCompanyCard(companyId) {
        const card = document.getElementById("company-details-card");
        if (!companyId) {
            if (card) card.style.display = "none";
            return;
        }

        const comp = companiesData.find(
            (c) => String(c.id) === String(companyId),
        );

        if (comp) {
            setElemText("cd-name", comp.company_name || "-");
            setElemText("cd-commercial", comp.commercial_number || "-");
            setElemText("cd-tax", comp.tax_number || "-");
            setElemText(
                "cd-city",
                comp.city ? comp.city.name : comp.city_name || "-",
            );

            // Backward compatibility IDs
            setElemText("company-commercial", comp.commercial_number || "-");
            setElemText("company-tax", comp.tax_number || "-");
            setElemText(
                "company-city",
                comp.city ? comp.city.name : comp.city_name || "-",
            );
            setElemText("company-address", comp.address || "-");

            const lastSub = comp.last_subscription || comp.lastSubscription;
            const lastSubEl = document.getElementById("cd-last-sub");
            const lastSubText = document.getElementById(
                "company-last-sub-text",
            );
            if (lastSub) {
                const planName = lastSub.plan ? lastSub.plan.name || "-" : "-";
                const statusLabel = lastSub.status || "منتهي";
                const endsAt = lastSub.ends_at
                    ? String(lastSub.ends_at).substring(0, 10)
                    : "-";
                renderLastSubscription(lastSubEl, statusLabel, planName, endsAt);
                renderLastSubscription(lastSubText, statusLabel, planName, endsAt);
            } else {
                renderLastSubscription(lastSubEl, null, null, null);
                renderLastSubscription(lastSubText, null, null, null);
            }

            if (card) card.style.display = "block";
        } else {
            // Fallback via AJAX
            const detailsUrlTemplate = window.routes?.companyDetails || "/superadmin/subscriptions/companies/__ID__/details";
            const detailsUrl = detailsUrlTemplate.replace("__ID__", encodeURIComponent(companyId));
            fetch(detailsUrl, {
                headers: { Accept: "application/json" },
            })
                .then((res) => res.json())
                .then((data) => {
                    setElemText("cd-name", data.company_name || "-");
                    setElemText("cd-commercial", data.commercial_number || "-");
                    setElemText("cd-tax", data.tax_number || "-");
                    setElemText("cd-city", data.city_name || "-");

                    setElemText(
                        "company-commercial",
                        data.commercial_number || "-",
                    );
                    setElemText("company-tax", data.tax_number || "-");
                    setElemText("company-city", data.city_name || "-");
                    setElemText("company-address", data.address || "-");

                    const lastSubEl = document.getElementById("cd-last-sub");
                    const lastSubText = document.getElementById(
                        "company-last-sub-text",
                    );
                    if (data.last_subscription) {
                        renderLastSubscription(
                            lastSubEl,
                            data.last_subscription.status,
                            data.last_subscription.plan,
                            data.last_subscription.ends_at,
                        );
                        renderLastSubscription(
                            lastSubText,
                            data.last_subscription.status,
                            data.last_subscription.plan,
                            data.last_subscription.ends_at,
                        );
                    } else {
                        renderLastSubscription(lastSubEl, null, null, null);
                        renderLastSubscription(lastSubText, null, null, null);
                    }
                    if (card) card.style.display = "block";
                })
                .catch(() => {
                    if (card) card.style.display = "none";
                });
        }
    }

    const companySelect = document.getElementById("company_id");
    if (companySelect) {
        if (window.jQuery) {
            $(companySelect).on("change", function () {
                updateCompanyCard(this.value);
                updateReviewSummary();
            });
        } else {
            companySelect.addEventListener("change", function () {
                updateCompanyCard(this.value);
                updateReviewSummary();
            });
        }
        if (companySelect.value) {
            updateCompanyCard(companySelect.value);
        }
    }

    // Plan selection handler
    function updatePlanDetails(planId) {
        const card = document.getElementById("plan-details-card");
        const plan = plansData.find((p) => String(p.id) === String(planId));
        if (!plan) {
            if (card) card.style.display = "none";
            setElemValue("sub_plan_users_display", "-");
            setElemValue("sub_plan_properties_display", "-");
            setElemValue("sub_plan_storage_display", "-");
            setElemValue("sub_plan_auctions_display", "-");
            return;
        }

        const priceStr =
            formatNumber(
                parseFloat(plan.price_after_discount || plan.price || 0),
            ) + " ر.س";
        setElemText("pd-price", priceStr);
        setElemText("pd-users", (plan.included_users || 1) + " مستخدمين");
        setElemText(
            "pd-props",
            plan.properties_limit !== null
                ? plan.properties_limit
                : "غير محدود (∞)",
        );
        setElemText(
            "pd-storage",
            plan.storage_quota_gb !== null
                ? plan.storage_quota_gb + " GB"
                : "غير محدود (∞)",
        );
        setElemText(
            "pd-auctions",
            plan.active_auctions_limit !== null
                ? plan.active_auctions_limit
                : "غير محدود (∞)",
        );

        // Backward compatibility IDs
        setElemValue("sub_plan_users_display", plan.included_users || 1);
        setElemValue(
            "sub_plan_properties_display",
            plan.properties_limit !== null
                ? plan.properties_limit
                : "غير محدود (∞)",
        );
        setElemValue(
            "sub_plan_storage_display",
            plan.storage_quota_gb !== null
                ? plan.storage_quota_gb + " GB"
                : "غير محدود (∞)",
        );
        setElemValue(
            "sub_plan_auctions_display",
            plan.active_auctions_limit !== null
                ? plan.active_auctions_limit
                : "غير محدود (∞)",
        );

        const featContainer =
            document.getElementById("pd-features") ||
            document.getElementById("sub_plan_features_display");
        renderFeatureBadges(
            featContainer,
            plan.features,
            "badge bg-white text-success border me-1",
            (container) => {
                const empty = document.createElement("span");
                empty.className = "small";
                empty.textContent = "لا توجد مميزات إضافية مسجلة";
                container.appendChild(empty);
            },
        );

        const monthsField = document.getElementById("period_months");
        if (monthsField) {
            if (plan.is_trial || plan.code === "trial_7_days") {
                monthsField.value = "1";
            } else {
                const defaultCycleMonths =
                    plan.subscription_billing_cycle === "yearly"
                        ? 12
                        : plan.subscription_billing_cycle === "quarterly"
                          ? 3
                          : 1;
                monthsField.value = String(defaultCycleMonths);
            }
        }

        if (card) card.style.display = "block";

        recalculateDatesAndPricing();
    }

    function setCouponFeedback(message, className) {
        const feedback = document.getElementById("coupon-feedback");
        if (!feedback) return;
        feedback.textContent = message || "";
        feedback.className = className
            ? `form-text mt-1 ${className}`
            : "form-text mt-1";
    }

    function clearCouponState(clearFeedback = true) {
        appliedCoupon = null;
        if (clearFeedback) {
            setCouponFeedback("", "");
        }
    }

    const planSelect = document.getElementById("plan_id");
    if (planSelect) {
        const onPlanChange = function () {
            clearCouponState();
            updatePlanDetails(this.value);
            updateReviewSummary();
        };
        if (window.jQuery) {
            $(planSelect).on("change", onPlanChange);
        } else {
            planSelect.addEventListener("change", onPlanChange);
        }
        if (planSelect.value) {
            updatePlanDetails(planSelect.value);
        }
    }

    // Auto-calculate end date & pricing based on start_date, period_months & selected plan
    function recalculateDatesAndPricing() {
        const planId = planSelect?.value;
        const plan = plansData.find((p) => String(p.id) === String(planId));

        const startDateInput =
            document.getElementById("start_date") ||
            document.getElementById("sub_start_date");
        const endDateInput =
            document.getElementById("end_date") ||
            document.getElementById("sub_end_date");
        const monthsInput = document.getElementById("period_months");

        const months = Math.max(
            1,
            parseInt(monthsInput?.value || "12", 10) || 12,
        );

        if (startDateInput && startDateInput.value && endDateInput) {
            const start = parseYmdLocal(startDateInput.value);
            if (start) {
                let end;
                if (plan && (plan.is_trial || plan.code === "trial_7_days")) {
                    end = new Date(
                        start.getFullYear(),
                        start.getMonth(),
                        start.getDate() + 7,
                    );
                } else {
                    end = addMonthsNoOverflow(start, months);
                }
                endDateInput.value = formatLocalYmd(end);
            }
        }

        // Financial breakdown calculation (plan prices are VAT-inclusive platform-wide)
        if (plan) {
            const isTrial = Boolean(plan.is_trial || plan.code === "trial_7_days");
            let calculatedBase = 0;

            if (!isTrial) {
                const unitPrice = parseFloat(
                    plan.price_after_discount || plan.price || 0,
                );
                const cycleMonths =
                    plan.subscription_billing_cycle === "yearly"
                        ? 12
                        : plan.subscription_billing_cycle === "quarterly"
                          ? 3
                          : 1;
                calculatedBase = +(unitPrice * (months / cycleMonths)).toFixed(
                    2,
                );
            }

            const discountVal = isTrial
                ? 0
                : Math.min(
                      calculatedBase,
                      appliedCoupon
                          ? parseFloat(appliedCoupon.discountAmount || 0)
                          : 0,
                  );
            const finalTotal = Math.max(
                0,
                +(calculatedBase - discountVal).toFixed(2),
            );
            const includedVat = +(finalTotal * 0.15).toFixed(2);

            setElemText(
                "calc-plan-price",
                formatNumber(calculatedBase) + " ر.س",
            );
            setElemText("calc-discount", formatNumber(discountVal) + " ر.س");
            setElemText("calc-tax", formatNumber(includedVat) + " ر.س");
            setElemText("calc-final-total", formatNumber(finalTotal) + " ر.س");
            setElemText(
                "sub_review-final-total",
                formatNumber(finalTotal) + " ر.س",
            );
        }
    }

    const startDateInput =
        document.getElementById("start_date") ||
        document.getElementById("sub_start_date");
    if (startDateInput) {
        startDateInput.addEventListener("change", function () {
            recalculateDatesAndPricing();
            updateReviewSummary();
        });
    }

    const monthsInput = document.getElementById("period_months");
    if (monthsInput) {
        monthsInput.addEventListener("input", function () {
            if (appliedCoupon) {
                clearCouponState(false);
                setCouponFeedback(
                    "تم تغيير المدة؛ يرجى إعادة فحص الكوبون لتحديث الخصم.",
                    "text-warning",
                );
            }
            recalculateDatesAndPricing();
            updateReviewSummary();
        });
    }

    const couponInput = document.getElementById("coupon_code");
    const validateCouponBtn = document.getElementById("btn-validate-coupon");
    if (couponInput) {
        couponInput.addEventListener("input", function () {
            if (appliedCoupon) {
                clearCouponState();
                recalculateDatesAndPricing();
                updateReviewSummary();
            } else {
                setCouponFeedback("", "");
            }
        });
    }

    if (validateCouponBtn) {
        validateCouponBtn.addEventListener("click", async function () {
            const planId = planSelect?.value;
            const code = couponInput?.value?.trim() || "";
            const months = parseInt(monthsInput?.value || "1", 10) || 1;

            if (!planId) {
                setCouponFeedback("يرجى اختيار الباقة أولاً.", "text-danger");
                return;
            }
            if (!code) {
                clearCouponState(false);
                setCouponFeedback("يرجى إدخال كود الكوبون أولاً.", "text-danger");
                recalculateDatesAndPricing();
                updateReviewSummary();
                return;
            }

            const originalBtnHtml = validateCouponBtn.innerHTML;
            validateCouponBtn.disabled = true;
            setCouponFeedback("جاري التحقق من الكوبون...", "text-muted");

            try {
                const validateUrl =
                    window.routes?.validateCoupon ||
                    "/superadmin/subscriptions/validate-coupon";
                const csrfToken =
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute("content") || "";

                const response = await fetch(validateUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify({
                        plan_id: planId,
                        period_months: months,
                        coupon_code: code,
                    }),
                });

                const payload = await response.json();
                if (response.ok && payload.success) {
                    appliedCoupon = {
                        code: payload.coupon_code || code,
                        discountAmount: parseFloat(payload.discount_amount || 0),
                    };
                    setCouponFeedback(
                        `${payload.message || "تم تطبيق الكوبون"} (خصم ${formatNumber(appliedCoupon.discountAmount)} ر.س)`,
                        "text-success fw-semibold",
                    );
                } else {
                    clearCouponState(false);
                    setCouponFeedback(
                        payload.message || "كود الخصم غير صالح.",
                        "text-danger",
                    );
                }
            } catch (_err) {
                clearCouponState(false);
                setCouponFeedback(
                    "تعذر التحقق من الكوبون حالياً. حاول مرة أخرى.",
                    "text-danger",
                );
            } finally {
                validateCouponBtn.disabled = false;
                validateCouponBtn.innerHTML = originalBtnHtml;
                recalculateDatesAndPricing();
                updateReviewSummary();
            }
        });
    }

    // Step 3 Review screen dynamic synchronization
    function updateReviewSummary() {
        // Step 1: Company
        const compId = document.getElementById("company_id")?.value;
        const comp = companiesData.find((c) => String(c.id) === String(compId));
        const compSelect = document.getElementById("company_id");
        const compText =
            compSelect && compSelect.selectedIndex >= 0
                ? compSelect.options[compSelect.selectedIndex].text
                : "-";

        setElemText(
            "sub_review-company-name",
            comp ? comp.company_name : compText,
        );
        setElemText(
            "sub_review-commercial-number",
            comp ? comp.commercial_number || "-" : "-",
        );
        setElemText(
            "sub_review-tax-number",
            comp ? comp.tax_number || "-" : "-",
        );
        setElemText(
            "sub_review-city",
            comp ? comp.city?.name || comp.city_name || "-" : "-",
        );

        const lastSub = comp
            ? comp.last_subscription || comp.lastSubscription
            : null;
        const rLastSub = document.getElementById("sub_review-last-sub");
        if (rLastSub) {
            if (lastSub) {
                const planTitle = lastSub.plan?.name || "-";
                const endsAt = lastSub.ends_at
                    ? String(lastSub.ends_at).substring(0, 10)
                    : "-";
                rLastSub.textContent = `${planTitle} (انتهى: ${endsAt})`;
            } else {
                rLastSub.textContent = "لا يوجد اشتراك سابق";
            }
        }

        // Plan & Limits
        const planId = document.getElementById("plan_id")?.value;
        const plan = plansData.find((p) => String(p.id) === String(planId));

        setElemText("sub_review-plan-name", plan ? plan.name : "-");
        setElemText(
            "sub_review-plan-users",
            (plan ? plan.included_users || 1 : "-") + " مستخدمين",
        );
        setElemText(
            "sub_review-plan-props",
            plan
                ? plan.properties_limit !== null
                    ? plan.properties_limit
                    : "غير محدود (∞)"
                : "-",
        );
        setElemText(
            "sub_review-plan-storage",
            plan
                ? plan.storage_quota_gb !== null
                    ? plan.storage_quota_gb + " GB"
                    : "غير محدود (∞)"
                : "-",
        );
        setElemText(
            "sub_review-plan-auctions",
            plan
                ? plan.active_auctions_limit !== null
                    ? plan.active_auctions_limit
                    : "غير محدود (∞)"
                : "-",
        );

        const featRev = document.getElementById("sub_review-plan-features");
        renderFeatureBadges(
            featRev,
            plan ? plan.features : [],
            "badge bg-info me-1",
            (container) => {
                container.textContent = "-";
            },
        );

        // Dates & Payments
        const formatDisplay = (val) =>
            val && typeof window.formatSystemDate === "function"
                ? window.formatSystemDate(val)
                : val || "-";
        const sDateVal = formatDisplay(
            document.getElementById("start_date")?.value ||
            document.getElementById("sub_start_date")?.value
        );
        const eDateVal = formatDisplay(
            document.getElementById("end_date")?.value ||
            document.getElementById("sub_end_date")?.value
        );
        setElemText("sub_review-start-date", sDateVal);
        setElemText("sub_review-end-date", eDateVal);

        const methodSelect =
            document.getElementById("payment_method") ||
            document.getElementById("sub_payment-method-select");
        const methodText =
            methodSelect && methodSelect.selectedIndex >= 0
                ? methodSelect.options[methodSelect.selectedIndex].text
                : "-";
        setElemText("sub_review-payment-method", methodText);

        const statusSelect =
            document.getElementById("payment_status") ||
            document.getElementById("sub_payment-status");
        const statusValue = statusSelect?.value || "completed";
        const isPendingPayment = ["pending", "unpaid"].includes(statusValue);
        const isTrialSub =
            Boolean(plan && (plan.is_trial || plan.code === "trial_7_days")) ||
            statusValue === "trial";
        const statusText =
            statusSelect && statusSelect.selectedIndex >= 0
                ? statusSelect.options[statusSelect.selectedIndex].text
                : "مكتمل";
        const rPay = document.getElementById("sub_review-payment-status");
        if (rPay) {
            rPay.textContent = statusText;
            rPay.className = isPendingPayment
                ? "badge bg-warning"
                : "badge bg-success";
        }

        const rAct = document.getElementById("sub_review-activation-status");
        if (rAct) {
            if (isTrialSub) {
                rAct.className = "badge bg-info";
                rAct.textContent = "تجريبي مفعل فوراً";
            } else if (isPendingPayment) {
                rAct.className = "badge bg-warning";
                rAct.textContent = "معلق لحين تأكيد الدفع";
            } else {
                rAct.className = "badge bg-success";
                rAct.textContent = "مفعل فوراً";
            }
        }
    }

    const paymentStatusSelect =
        document.getElementById("payment_status") ||
        document.getElementById("sub_payment-status");
    if (paymentStatusSelect) {
        paymentStatusSelect.addEventListener("change", updateReviewSummary);
    }

    const paymentMethodSelect =
        document.getElementById("payment_method") ||
        document.getElementById("sub_payment-method-select");
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener("change", updateReviewSummary);
    }

    form.addEventListener("stepChanged", function () {
        recalculateDatesAndPricing();
        updateReviewSummary();
    });

    form.addEventListener("wizardDraftRestored", function (event) {
        const data = event.detail?.data || {};
        if (data.company_id) {
            updateCompanyCard(data.company_id);
        }
        if (data.plan_id) {
            updatePlanDetails(data.plan_id);
        }
        recalculateDatesAndPricing();
        updateReviewSummary();
    });

    // Initial sync
    recalculateDatesAndPricing();
    updateReviewSummary();
});
