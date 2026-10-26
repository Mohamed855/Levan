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
            document.getElementById("company-commercial").textContent =
                comp.commercial_number || "-";
            document.getElementById("company-tax").textContent =
                comp.tax_number || "-";
            document.getElementById("company-city").textContent = comp.city
                ? comp.city.name
                : comp.city_name || "-";
            document.getElementById("company-address").textContent =
                comp.address || "-";

            const lastSub = comp.last_subscription || comp.lastSubscription;
            const lastSubText = document.getElementById(
                "company-last-sub-text",
            );
            if (lastSub) {
                const planName = lastSub.plan ? lastSub.plan.name || "-" : "-";
                const statusLabel = lastSub.status || "منتهي";
                const endsAt = lastSub.ends_at
                    ? String(lastSub.ends_at).substring(0, 10)
                    : "-";
                lastSubText.innerHTML = `<span class="badge bg-danger me-1">${statusLabel}</span> باقة: ${planName} (انتهى بتاريخ: ${endsAt})`;
            } else {
                lastSubText.innerHTML =
                    '<span class="text-muted">لا يوجد اشتراك سابق</span>';
            }

            if (card) card.style.display = "block";
        } else {
            // Fallback via AJAX
            fetch(`/admin/subscriptions/companies/${companyId}/details`, {
                headers: { Accept: "application/json" },
            })
                .then((res) => res.json())
                .then((data) => {
                    document.getElementById("company-commercial").textContent =
                        data.commercial_number || "-";
                    document.getElementById("company-tax").textContent =
                        data.tax_number || "-";
                    document.getElementById("company-city").textContent =
                        data.city_name || "-";
                    document.getElementById("company-address").textContent =
                        data.address || "-";

                    const lastSubText = document.getElementById(
                        "company-last-sub-text",
                    );
                    if (data.last_subscription) {
                        lastSubText.innerHTML = `<span class="badge bg-danger me-1">${data.last_subscription.status}</span> باقة: ${data.last_subscription.plan} (انتهى بتاريخ: ${data.last_subscription.ends_at})`;
                    } else {
                        lastSubText.innerHTML =
                            '<span class="text-muted">لا يوجد اشتراك سابق</span>';
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
            });
        } else {
            companySelect.addEventListener("change", function () {
                updateCompanyCard(this.value);
            });
        }
        if (companySelect.value) {
            updateCompanyCard(companySelect.value);
        }
    }

    // Plan selection handler
    function updatePlanDetails(planId) {
        const plan = plansData.find((p) => String(p.id) === String(planId));
        if (!plan) {
            document.getElementById("sub_plan_users_display").value = "-";
            document.getElementById("sub_plan_properties_display").value = "-";
            document.getElementById("sub_plan_storage_display").value = "-";
            document.getElementById("sub_plan_auctions_display").value = "-";
            document.getElementById("sub_plan_features_display").innerHTML =
                '<span class="text-muted">اختر الباقة لعرض مميزاتها</span>';
            return;
        }

        document.getElementById("sub_plan_users_display").value =
            plan.included_users || 1;
        document.getElementById("sub_plan_properties_display").value =
            plan.properties_limit !== null
                ? plan.properties_limit
                : "غير محدود (∞)";
        document.getElementById("sub_plan_storage_display").value =
            plan.storage_quota_gb !== null
                ? plan.storage_quota_gb + " GB"
                : "غير محدود (∞)";
        document.getElementById("sub_plan_auctions_display").value =
            plan.active_auctions_limit !== null
                ? plan.active_auctions_limit
                : "غير محدود (∞)";

        const featContainer = document.getElementById(
            "sub_plan_features_display",
        );
        if (Array.isArray(plan.features) && plan.features.length > 0) {
            featContainer.innerHTML = plan.features
                .map((f) => `<span class="badge bg-info">${f}</span>`)
                .join(" ");
        } else {
            featContainer.innerHTML =
                '<span class="text-muted">لا توجد مميزات إضافية مسجلة</span>';
        }

        // Auto calculate end date based on plan duration
        const startDateVal = document.getElementById("sub_start_date")?.value;
        if (startDateVal) {
            const start = new Date(startDateVal);
            if (!isNaN(start.getTime())) {
                const end = new Date(start);
                if (
                    plan.subscription_duration_type === "limited" &&
                    plan.subscription_billing_cycle === "monthly"
                ) {
                    end.setMonth(end.getMonth() + 1);
                } else {
                    end.setFullYear(end.getFullYear() + 1);
                }
                const formattedEnd = end.toISOString().split("T")[0];
                const endDateInput = document.getElementById("sub_end_date");
                if (endDateInput) {
                    endDateInput.value = formattedEnd;
                }
            }
        }
    }

    const planSelect = document.getElementById("plan_id");
    if (planSelect) {
        if (window.jQuery) {
            $(planSelect).on("change", function () {
                updatePlanDetails(this.value);
            });
        } else {
            planSelect.addEventListener("change", function () {
                updatePlanDetails(this.value);
            });
        }
        if (planSelect.value) {
            updatePlanDetails(planSelect.value);
        }
    }

    const startDateInput = document.getElementById("sub_start_date");
    if (startDateInput) {
        startDateInput.addEventListener("change", function () {
            if (planSelect && planSelect.value) {
                updatePlanDetails(planSelect.value);
            }
        });
    }

    // Payment status change handlers
    const paymentStatus = document.getElementById("sub_payment-status");
    const paymentMethodWrapper = document.getElementById(
        "sub_payment-method-wrapper",
    );
    const receiptUploadWrapper = document.getElementById(
        "sub_receipt-upload-wrapper",
    );
    const trialDateWrapper = document.getElementById("sub_trial-date-wrapper");
    const paymentMethodSelect = document.getElementById(
        "sub_payment-method-select",
    );

    function handlePaymentStatusChange(status) {
        if (status === "paid") {
            if (paymentMethodWrapper)
                paymentMethodWrapper.style.display = "block";
            if (trialDateWrapper) trialDateWrapper.style.display = "none";
            if (
                paymentMethodSelect &&
                paymentMethodSelect.value === "bank_transfer"
            ) {
                if (receiptUploadWrapper)
                    receiptUploadWrapper.style.display = "block";
            } else {
                if (receiptUploadWrapper)
                    receiptUploadWrapper.style.display = "none";
            }
        } else if (status === "trial") {
            if (paymentMethodWrapper)
                paymentMethodWrapper.style.display = "none";
            if (receiptUploadWrapper)
                receiptUploadWrapper.style.display = "none";
            if (trialDateWrapper) trialDateWrapper.style.display = "block";
        } else {
            if (paymentMethodWrapper)
                paymentMethodWrapper.style.display = "none";
            if (receiptUploadWrapper)
                receiptUploadWrapper.style.display = "none";
            if (trialDateWrapper) trialDateWrapper.style.display = "none";
        }
    }

    if (paymentStatus) {
        paymentStatus.addEventListener("change", function () {
            handlePaymentStatusChange(this.value);
        });
        handlePaymentStatusChange(paymentStatus.value);
    }

    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener("change", function () {
            if (
                this.value === "bank_transfer" &&
                paymentStatus?.value === "paid"
            ) {
                if (receiptUploadWrapper)
                    receiptUploadWrapper.style.display = "block";
            } else {
                if (receiptUploadWrapper)
                    receiptUploadWrapper.style.display = "none";
            }
        });
    }

    // Discount toggle
    const discountType = document.getElementById("sub_discount-type");
    const discountWrapper = document.getElementById("sub_discount-wrapper");
    if (discountType && discountWrapper) {
        discountType.addEventListener("change", function () {
            discountWrapper.style.display =
                this.value === "yes" ? "block" : "none";
        });
        discountWrapper.style.display =
            discountType.value === "yes" ? "block" : "none";
    }

    // Review Summary Screen Update (Step 3)
    function updateReviewSummary() {
        // Step 1: Company
        const compId = document.getElementById("company_id")?.value;
        const comp = companiesData.find((c) => String(c.id) === String(compId));
        const compSelect = document.getElementById("company_id");
        const compText =
            compSelect && compSelect.selectedIndex >= 0
                ? compSelect.options[compSelect.selectedIndex].text
                : "-";

        const rCompName = document.getElementById("sub_review-company-name");
        if (rCompName)
            rCompName.textContent = comp ? comp.company_name : compText;

        const rComm = document.getElementById("sub_review-commercial-number");
        if (rComm)
            rComm.textContent = comp ? comp.commercial_number || "-" : "-";

        const rTax = document.getElementById("sub_review-tax-number");
        if (rTax) rTax.textContent = comp ? comp.tax_number || "-" : "-";

        const rCity = document.getElementById("sub_review-city");
        if (rCity) rCity.textContent = comp ? comp.city?.name || "-" : "-";

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

        const rPlanName = document.getElementById("sub_review-plan-name");
        if (rPlanName) rPlanName.textContent = plan ? plan.name : "-";

        const rUsers = document.getElementById("sub_review-plan-users");
        if (rUsers)
            rUsers.textContent =
                (plan ? plan.included_users || 1 : "-") + " مستخدم";

        const rProps = document.getElementById("sub_review-plan-props");
        if (rProps)
            rProps.textContent = plan
                ? plan.properties_limit !== null
                    ? plan.properties_limit
                    : "غير محدود (∞)"
                : "-";

        const rStorage = document.getElementById("sub_review-plan-storage");
        if (rStorage)
            rStorage.textContent = plan
                ? plan.storage_quota_gb !== null
                    ? plan.storage_quota_gb + " GB"
                    : "غير محدود (∞)"
                : "-";

        const rAuctions = document.getElementById("sub_review-plan-auctions");
        if (rAuctions)
            rAuctions.textContent = plan
                ? plan.active_auctions_limit !== null
                    ? plan.active_auctions_limit
                    : "غير محدود (∞)"
                : "-";

        const featRev = document.getElementById("sub_review-plan-features");
        if (featRev) {
            if (
                plan &&
                Array.isArray(plan.features) &&
                plan.features.length > 0
            ) {
                featRev.innerHTML = plan.features
                    .map((f) => `<span class="badge bg-info">${f}</span>`)
                    .join(" ");
            } else {
                featRev.textContent = "-";
            }
        }

        // Dates & Payments
        const rStartDate = document.getElementById("sub_review-start-date");
        if (rStartDate)
            rStartDate.textContent =
                document.getElementById("sub_start_date")?.value || "-";

        const rEndDate = document.getElementById("sub_review-end-date");
        if (rEndDate)
            rEndDate.textContent =
                document.getElementById("sub_end_date")?.value || "-";

        const isImmediate = document.getElementById(
            "sub_activate_immediately",
        )?.checked;
        const rAct = document.getElementById("sub_review-activation-status");
        if (rAct) {
            rAct.className = isImmediate
                ? "badge bg-success"
                : "badge bg-warning";
            rAct.textContent = isImmediate ? "مفعل فوراً" : "قيد المراجعة";
        }

        const payType =
            document.getElementById("sub_payment-status")?.value || "";
        const rPay = document.getElementById("sub_review-payment-status");
        if (rPay) {
            const payMap = {
                paid: ["تم الدفع", "bg-success"],
                unpaid: ["لم يتم الدفع", "bg-danger"],
                trial: ["فترة تجربة", "bg-info"],
            };
            const item = payMap[payType] || ["-", "bg-secondary"];
            rPay.className = "badge " + item[1];
            rPay.textContent = item[0];
        }

        const rMethod = document.getElementById("sub_review-payment-method");
        if (rMethod) {
            rMethod.textContent =
                payType === "paid" &&
                paymentMethodSelect &&
                paymentMethodSelect.selectedIndex >= 0
                    ? paymentMethodSelect.options[
                          paymentMethodSelect.selectedIndex
                      ].text
                    : "-";
        }

        // Calculate price
        const basePrice = plan
            ? parseFloat(plan.price_after_discount || plan.price || 0)
            : 0;
        const discountVal =
            discountType?.value === "yes"
                ? parseFloat(
                      document.getElementById("sub_discount_value")?.value || 0,
                  )
                : 0;
        const finalPrice =
            payType === "trial" ? 0 : Math.max(0, basePrice - discountVal);
        const rTotal = document.getElementById("sub_review-final-total");
        if (rTotal) rTotal.textContent = formatNumber(finalPrice) + " ر.س";
    }

    form.addEventListener("stepChanged", function () {
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
        if (data.payment_type) {
            handlePaymentStatusChange(data.payment_type);
        }
        if (data.has_discount && discountWrapper) {
            discountWrapper.style.display =
                data.has_discount === "yes" ? "block" : "none";
        }
        updateReviewSummary();
    });

    // Initial review sync
    updateReviewSummary();
});
