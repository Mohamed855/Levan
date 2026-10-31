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
            setElemText("cd-city", comp.city ? comp.city.name : comp.city_name || "-");

            // Backward compatibility IDs
            setElemText("company-commercial", comp.commercial_number || "-");
            setElemText("company-tax", comp.tax_number || "-");
            setElemText("company-city", comp.city ? comp.city.name : comp.city_name || "-");
            setElemText("company-address", comp.address || "-");

            const lastSub = comp.last_subscription || comp.lastSubscription;
            const lastSubEl = document.getElementById("cd-last-sub");
            const lastSubText = document.getElementById("company-last-sub-text");
            if (lastSub) {
                const planName = lastSub.plan ? lastSub.plan.name || "-" : "-";
                const statusLabel = lastSub.status || "منتهي";
                const endsAt = lastSub.ends_at ? String(lastSub.ends_at).substring(0, 10) : "-";
                const htmlContent = `<span class="badge bg-danger me-1">${statusLabel}</span> باقة: ${planName} (انتهى بتاريخ: ${endsAt})`;
                if (lastSubEl) lastSubEl.innerHTML = htmlContent;
                if (lastSubText) lastSubText.innerHTML = htmlContent;
            } else {
                const emptyContent = '<span class="text-muted">لا يوجد اشتراك سابق</span>';
                if (lastSubEl) lastSubEl.innerHTML = emptyContent;
                if (lastSubText) lastSubText.innerHTML = emptyContent;
            }

            if (card) card.style.display = "block";
        } else {
            // Fallback via AJAX
            fetch(`/admin/subscriptions/companies/${companyId}/details`, {
                headers: { Accept: "application/json" },
            })
                .then((res) => res.json())
                .then((data) => {
                    setElemText("cd-name", data.company_name || "-");
                    setElemText("cd-commercial", data.commercial_number || "-");
                    setElemText("cd-tax", data.tax_number || "-");
                    setElemText("cd-city", data.city_name || "-");

                    setElemText("company-commercial", data.commercial_number || "-");
                    setElemText("company-tax", data.tax_number || "-");
                    setElemText("company-city", data.city_name || "-");
                    setElemText("company-address", data.address || "-");

                    const lastSubEl = document.getElementById("cd-last-sub");
                    const lastSubText = document.getElementById("company-last-sub-text");
                    if (data.last_subscription) {
                        const htmlContent = `<span class="badge bg-danger me-1">${data.last_subscription.status}</span> باقة: ${data.last_subscription.plan} (انتهى بتاريخ: ${data.last_subscription.ends_at})`;
                        if (lastSubEl) lastSubEl.innerHTML = htmlContent;
                        if (lastSubText) lastSubText.innerHTML = htmlContent;
                    } else {
                        const emptyContent = '<span class="text-muted">لا يوجد اشتراك سابق</span>';
                        if (lastSubEl) lastSubEl.innerHTML = emptyContent;
                        if (lastSubText) lastSubText.innerHTML = emptyContent;
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

        const priceStr = formatNumber(parseFloat(plan.price_after_discount || plan.price || 0)) + " ر.س";
        setElemText("pd-price", priceStr);
        setElemText("pd-users", (plan.included_users || 1) + " مستخدمين");
        setElemText("pd-props", plan.properties_limit !== null ? plan.properties_limit : "غير محدود (∞)");
        setElemText("pd-storage", plan.storage_quota_gb !== null ? plan.storage_quota_gb + " GB" : "غير محدود (∞)");
        setElemText("pd-auctions", plan.active_auctions_limit !== null ? plan.active_auctions_limit : "غير محدود (∞)");

        // Backward compatibility IDs
        setElemValue("sub_plan_users_display", plan.included_users || 1);
        setElemValue("sub_plan_properties_display", plan.properties_limit !== null ? plan.properties_limit : "غير محدود (∞)");
        setElemValue("sub_plan_storage_display", plan.storage_quota_gb !== null ? plan.storage_quota_gb + " GB" : "غير محدود (∞)");
        setElemValue("sub_plan_auctions_display", plan.active_auctions_limit !== null ? plan.active_auctions_limit : "غير محدود (∞)");

        const featContainer = document.getElementById("pd-features") || document.getElementById("sub_plan_features_display");
        if (featContainer) {
            if (Array.isArray(plan.features) && plan.features.length > 0) {
                featContainer.innerHTML = plan.features
                    .map((f) => `<span class="badge bg-white text-success border me-1">${f}</span>`)
                    .join(" ");
            } else {
                featContainer.innerHTML = '<span class="text-white-50 small">لا توجد مميزات إضافية مسجلة</span>';
            }
        }

        if (card) card.style.display = "block";

        recalculateDatesAndPricing();
    }

    const planSelect = document.getElementById("plan_id");
    if (planSelect) {
        if (window.jQuery) {
            $(planSelect).on("change", function () {
                updatePlanDetails(this.value);
                updateReviewSummary();
            });
        } else {
            planSelect.addEventListener("change", function () {
                updatePlanDetails(this.value);
                updateReviewSummary();
            });
        }
        if (planSelect.value) {
            updatePlanDetails(planSelect.value);
        }
    }

    // Auto-calculate end date & pricing based on start_date, period_months & selected plan
    function recalculateDatesAndPricing() {
        const planId = planSelect?.value;
        const plan = plansData.find((p) => String(p.id) === String(planId));

        const startDateInput = document.getElementById("start_date") || document.getElementById("sub_start_date");
        const endDateInput = document.getElementById("end_date") || document.getElementById("sub_end_date");
        const monthsInput = document.getElementById("period_months");

        const months = parseInt(monthsInput?.value || "12", 10) || 12;

        if (startDateInput && startDateInput.value && endDateInput) {
            const start = new Date(startDateInput.value);
            if (!isNaN(start.getTime())) {
                const end = new Date(start);
                if (plan && plan.code === "trial_7_days") {
                    end.setDate(end.getDate() + 7);
                } else {
                    end.setMonth(end.getMonth() + months);
                }
                const formattedEnd = end.toISOString().split("T")[0];
                endDateInput.value = formattedEnd;
            }
        }

        // Financial breakdown calculation
        if (plan) {
            const unitPrice = parseFloat(plan.price_after_discount || plan.price || 0);
            let calculatedBase = unitPrice;
            if (plan.subscription_billing_cycle === "monthly") {
                calculatedBase = unitPrice * months;
            } else if (months !== 12 && months > 0) {
                calculatedBase = (unitPrice / 12) * months;
            }

            const discountVal = 0.00;
            const subtotal = Math.max(0, calculatedBase - discountVal);
            const vat = subtotal * 0.15;
            const finalTotal = subtotal + vat;

            setElemText("calc-plan-price", formatNumber(calculatedBase) + " ر.س");
            setElemText("calc-discount", formatNumber(discountVal) + " ر.س");
            setElemText("calc-tax", formatNumber(vat) + " ر.س");
            setElemText("calc-final-total", formatNumber(finalTotal) + " ر.س");
            setElemText("sub_review-final-total", formatNumber(finalTotal) + " ر.س");
        }
    }

    const startDateInput = document.getElementById("start_date") || document.getElementById("sub_start_date");
    if (startDateInput) {
        startDateInput.addEventListener("change", function () {
            recalculateDatesAndPricing();
            updateReviewSummary();
        });
    }

    const monthsInput = document.getElementById("period_months");
    if (monthsInput) {
        monthsInput.addEventListener("input", function () {
            recalculateDatesAndPricing();
            updateReviewSummary();
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

        setElemText("sub_review-company-name", comp ? comp.company_name : compText);
        setElemText("sub_review-commercial-number", comp ? comp.commercial_number || "-" : "-");
        setElemText("sub_review-tax-number", comp ? comp.tax_number || "-" : "-");
        setElemText("sub_review-city", comp ? comp.city?.name || comp.city_name || "-" : "-");

        const lastSub = comp ? comp.last_subscription || comp.lastSubscription : null;
        const rLastSub = document.getElementById("sub_review-last-sub");
        if (rLastSub) {
            if (lastSub) {
                const planTitle = lastSub.plan?.name || "-";
                const endsAt = lastSub.ends_at ? String(lastSub.ends_at).substring(0, 10) : "-";
                rLastSub.textContent = `${planTitle} (انتهى: ${endsAt})`;
            } else {
                rLastSub.textContent = "لا يوجد اشتراك سابق";
            }
        }

        // Plan & Limits
        const planId = document.getElementById("plan_id")?.value;
        const plan = plansData.find((p) => String(p.id) === String(planId));

        setElemText("sub_review-plan-name", plan ? plan.name : "-");
        setElemText("sub_review-plan-users", (plan ? plan.included_users || 1 : "-") + " مستخدمين");
        setElemText("sub_review-plan-props", plan ? (plan.properties_limit !== null ? plan.properties_limit : "غير محدود (∞)") : "-");
        setElemText("sub_review-plan-storage", plan ? (plan.storage_quota_gb !== null ? plan.storage_quota_gb + " GB" : "غير محدود (∞)") : "-");
        setElemText("sub_review-plan-auctions", plan ? (plan.active_auctions_limit !== null ? plan.active_auctions_limit : "غير محدود (∞)") : "-");

        const featRev = document.getElementById("sub_review-plan-features");
        if (featRev) {
            if (plan && Array.isArray(plan.features) && plan.features.length > 0) {
                featRev.innerHTML = plan.features
                    .map((f) => `<span class="badge bg-info me-1">${f}</span>`)
                    .join(" ");
            } else {
                featRev.textContent = "-";
            }
        }

        // Dates & Payments
        const sDateVal = document.getElementById("start_date")?.value || document.getElementById("sub_start_date")?.value || "-";
        const eDateVal = document.getElementById("end_date")?.value || document.getElementById("sub_end_date")?.value || "-";
        setElemText("sub_review-start-date", sDateVal);
        setElemText("sub_review-end-date", eDateVal);

        const methodSelect = document.getElementById("payment_method") || document.getElementById("sub_payment-method-select");
        const methodText = methodSelect && methodSelect.selectedIndex >= 0 ? methodSelect.options[methodSelect.selectedIndex].text : "-";
        setElemText("sub_review-payment-method", methodText);

        const statusSelect = document.getElementById("payment_status") || document.getElementById("sub_payment-status");
        const statusText = statusSelect && statusSelect.selectedIndex >= 0 ? statusSelect.options[statusSelect.selectedIndex].text : "مكتمل";
        const rPay = document.getElementById("sub_review-payment-status");
        if (rPay) {
            rPay.textContent = statusText;
            rPay.className = "badge bg-success";
        }

        const rAct = document.getElementById("sub_review-activation-status");
        if (rAct) {
            rAct.className = "badge bg-success";
            rAct.textContent = "مفعل فوراً";
        }
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
