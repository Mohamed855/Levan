"use strict";

document.addEventListener("DOMContentLoaded", () => {
    const form =
        document.getElementById("client-wizard-form") ||
        document.getElementById("client-edit-form") ||
        document.getElementById("company-wizard-form") ||
        document.getElementById("company-edit-form");
    if (!form) {
        return;
    }

    const plans = window.companyPlans || [];
    const planId = form.querySelector('[name="plan_id"]');
    const startDate = form.querySelector('[name="start_date"]');

    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");

        return year + "-" + month + "-" + day;
    };

    const addMonths = (date, months) => {
        const d = new Date(date.getTime());
        const targetMonth = d.getMonth() + months;
        d.setMonth(targetMonth);
        if (d.getMonth() !== ((targetMonth % 12) + 12) % 12) {
            d.setDate(0);
        }
        return d;
    };

    const addYears = (date, years) => {
        const d = new Date(date.getTime());
        const targetYear = d.getFullYear() + years;
        d.setFullYear(targetYear);
        if (d.getFullYear() !== targetYear) {
            d.setDate(0);
        }
        return d;
    };

    const syncEndDate = () => {
        const endDate = form.querySelector('[name="end_date"]');
        if (!endDate) {
            return;
        }

        if (startDate && !startDate.value) {
            startDate.value = formatDate(new Date());
        }

        const selectedPlan = plans.find(
            (plan) => String(plan.id) === String(planId?.value),
        );
        if (!selectedPlan || !startDate?.value) {
            return;
        }

        if (selectedPlan.code === "trial_7_days") {
            const start = new Date(startDate.value + "T00:00:00");
            if (!Number.isNaN(start.getTime())) {
                const end = new Date(start);
                end.setDate(end.getDate() + 7);
                endDate.value = formatDate(end);
                endDate.dataset.autoCalculated = "true";
                endDate.dispatchEvent(new Event("input", { bubbles: true }));
                endDate.dispatchEvent(new Event("change", { bubbles: true }));
                return;
            }
        }

        const cycle = (
            selectedPlan.subscription_billing_cycle || ""
        ).toLowerCase();
        const isMonthly = cycle === "monthly";
        const isYearly = cycle === "yearly";
        const isQuarterly = cycle === "quarterly";

        if (!isMonthly && !isYearly && !isQuarterly) {
            if (endDate.dataset.autoCalculated === "true") {
                endDate.value = "";
                delete endDate.dataset.autoCalculated;
                endDate.dispatchEvent(new Event("input", { bubbles: true }));
                endDate.dispatchEvent(new Event("change", { bubbles: true }));
            }
            return;
        }

        const start = new Date(startDate.value + "T00:00:00");
        if (Number.isNaN(start.getTime())) {
            return;
        }

        let end;
        if (isMonthly) {
            end = addMonths(start, 1);
        } else if (isQuarterly) {
            end = addMonths(start, 3);
        } else if (isYearly) {
            end = addYears(start, 1);
        }

        if (end) {
            endDate.value = formatDate(end);
            endDate.dataset.autoCalculated = "true";
            endDate.dispatchEvent(new Event("input", { bubbles: true }));
            endDate.dispatchEvent(new Event("change", { bubbles: true }));
        }
    };

    const setReviewValue = (id, value) => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value || "-";
        }
    };

    const selectedText = (name) => {
        const select = form.querySelector('[name="' + name + '"]');
        const option = select?.options[select.selectedIndex];

        return option?.textContent?.trim() || "-";
    };

    const updateReview = () => {
        const selectedPlan = plans.find(
            (plan) => String(plan.id) === String(planId?.value),
        );

        setReviewValue(
            "review-client-name",
            form.querySelector('[name="company_name"]')?.value,
        );
        setReviewValue(
            "review-company-name",
            form.querySelector('[name="company_name"]')?.value,
        );
        setReviewValue(
            "review-commercial-number",
            form.querySelector('[name="commercial_number"]')?.value,
        );
        setReviewValue(
            "review-tax-number",
            form.querySelector('[name="tax_number"]')?.value,
        );
        setReviewValue("review-city", selectedText("city_id"));
        setReviewValue(
            "review-address",
            form.querySelector('[name="address"]')?.value,
        );
        setReviewValue(
            "review-admin-name",
            form.querySelector('[name="name"]')?.value,
        );
        setReviewValue(
            "review-admin-phone",
            form.querySelector('[name="phone"]')?.value,
        );
        setReviewValue(
            "review-admin-email",
            form.querySelector('[name="email"]')?.value,
        );
        setReviewValue("review-plan-name", selectedPlan?.name);
        setReviewValue("review-start-date", startDate?.value);
        setReviewValue(
            "review-end-date",
            form.querySelector('[name="end_date"]')?.value,
        );

        const reviewActivation = document.getElementById(
            "review-activation-status",
        );
        if (reviewActivation) {
            const isImmediate = form.querySelector(
                '[name="activate_immediately"]',
            )?.checked;
            const badgeClass =
                "badge " + (isImmediate ? "bg-success" : "bg-warning");
            const badgeText = isImmediate ? "نشط ومفعل" : "معلق";

            if (reviewActivation.tagName === "DD") {
                reviewActivation.innerHTML = `<span class="${badgeClass}">${badgeText}</span>`;
            } else {
                reviewActivation.className = badgeClass;
                reviewActivation.textContent = badgeText;
            }
        }

        setReviewValue(
            "preview-client-company",
            form.querySelector('[name="company_name"]')?.value || "اسم الشركة",
        );
        setReviewValue("preview-client-city", selectedText("city_id"));
        setReviewValue(
            "preview-client-admin",
            form.querySelector('[name="name"]')?.value || "-",
        );
        setReviewValue("preview-client-plan", selectedPlan?.name || "-");

        const previewStatus = document.getElementById("preview-client-status");
        if (previewStatus) {
            const isImmediate = form.querySelector(
                '[name="activate_immediately"]',
            )?.checked;
            previewStatus.className =
                "badge " + (isImmediate ? "bg-success" : "bg-warning");
            previewStatus.textContent = isImmediate
                ? "نشط ومفعل"
                : "معلق";
        }
    };

    if (window.jQuery?.fn.select2) {
        window
            .jQuery(form)
            .find(".select2")
            .select2({
                placeholder: (index, element) =>
                    window.jQuery(element).data("placeholder") || "اختر",
                allowClear: true,
                width: "100%",
                language: "ar",
                dir: "rtl",
            });
    }

    planId?.addEventListener("change", syncEndDate);
    if (window.jQuery && planId) {
        window.jQuery(planId).on("change", syncEndDate);
    }
    startDate?.addEventListener("change", syncEndDate);
    startDate?.addEventListener("input", syncEndDate);

    const endDate = form.querySelector('[name="end_date"]');
    endDate?.addEventListener("input", () => {
        delete endDate.dataset.autoCalculated;
    });

    form.addEventListener("input", updateReview);
    form.addEventListener("change", updateReview);
    form.addEventListener("stepChanged", updateReview);
    form.closest("[data-form-stepper]")?.addEventListener(
        "shown.bs-stepper",
        updateReview,
    );

    syncEndDate();
    updateReview();
});
