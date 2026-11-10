document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("#establishment-request-form");
    if (!form) return;

    // Helper: Escape HTML
    const escapeHtml = (str) => {
        if (str === null || str === undefined) return "";
        const div = document.createElement("div");
        div.textContent = String(str);
        return div.innerHTML;
    };

    // Helper: Get Stepper instance
    const getStepper = () => {
        const stepperEl = form.closest("[data-form-stepper]");
        return stepperEl?.bsStepper || window.stepper || null;
    };

    // Quick jump to step from review cards
    document.querySelectorAll("[data-edit-step]").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            const stepNum = Number(btn.dataset.editStep);
            if (!stepNum) return;
            const stepper = getStepper();
            if (stepper && typeof stepper.to === "function") {
                stepper.to(stepNum);
            }
        });
    });

    // Conditional field visibility handlers
    const setupConditionalFields = () => {
        // Toggle helper
        const toggleField = (fieldSelector, show) => {
            const el = form.querySelector(fieldSelector);
            if (!el) return;
            const col = el.closest(".col-md-6, .col-xl-4, .col-12, [data-form-field]") || el.parentElement;
            if (col) {
                col.style.display = show ? "" : "none";
            }
        };

        // Funds receipt & refund other methods
        const checkFundsMethods = () => {
            const receipt = form.querySelector('[name="data[funds][receipt_method]"]')?.value;
            toggleField('[name="data[funds][receipt_other]"]', receipt === "other");

            const refund = form.querySelector('[name="data[funds][refund_method]"]')?.value;
            toggleField('[name="data[funds][refund_other]"]', refund === "other");
        };

        // Auction method platform vs venue
        const checkAuctionMethod = () => {
            const method = form.querySelector('[name="data[auction][method]"]')?.value;
            toggleField('[name="data[auction][platform]"]', method === "electronic" || method === "hybrid");
            toggleField('[name="data[auction][venue]"]', method === "in_person" || method === "hybrid");
        };

        // Disclosures details
        const disclosureKeys = ["real_rights", "dispute", "value_information", "off_plan", "contribution"];
        const checkDisclosures = () => {
            disclosureKeys.forEach((key) => {
                const val = form.querySelector(`[name="data[disclosures][${key}]"]`)?.value;
                toggleField(`[name="data[disclosures][${key}_details]"]`, val === "yes");
            });

            // Disposition rights other & mutual exclusivity for 'none'
            const rightsSelect = form.querySelector('[name="data[disclosures][disposition_rights][]"], [name="data[disclosures][disposition_rights]"]');
            let hasOther = false;
            if (rightsSelect) {
                let vals = [];
                if (window.jQuery && window.jQuery(rightsSelect).data("select2")) {
                    vals = window.jQuery(rightsSelect).val() || [];
                } else if (rightsSelect.selectedOptions) {
                    vals = Array.from(rightsSelect.selectedOptions).map((opt) => opt.value);
                }

                if (vals.includes("none") && vals.length > 1) {
                    const lastVal = vals[vals.length - 1];
                    const newVals = lastVal === "none" ? ["none"] : vals.filter((v) => v !== "none");
                    if (window.jQuery && window.jQuery(rightsSelect).data("select2")) {
                        window.jQuery(rightsSelect).val(newVals).trigger("change.select2");
                    } else {
                        Array.from(rightsSelect.options).forEach((opt) => {
                            opt.selected = newVals.includes(opt.value);
                        });
                    }
                    vals = newVals;
                }

                hasOther = vals.includes("other");
            }
            toggleField('[name="data[disclosures][disposition_right_other]"]', hasOther);
        };

        // Date sync
        const syncAuctionDates = () => {
            const startInput = form.querySelector('[name="data[auction][start_date]"]');
            const endInput = form.querySelector('[name="data[auction][end_date]"]');
            if (startInput && endInput && startInput.value) {
                endInput.min = startInput.value;
            }
        };

        // Property status other
        const checkPropertyStatuses = () => {
            form.querySelectorAll('[name$="[status]"]').forEach((statusSelect) => {
                const match = statusSelect.name.match(/data\[properties\]\[(\d+)\]\[status\]/);
                if (!match) return;
                const idx = match[1];
                toggleField(`[name="data[properties][${idx}][status_other]"]`, statusSelect.value === "other");
            });
        };

        form.addEventListener("change", (e) => {
            const name = e.target?.name || "";
            if (name.includes("[funds]")) checkFundsMethods();
            if (name.includes("[auction][method]")) checkAuctionMethod();
            if (name.includes("[auction][start_date]")) syncAuctionDates();
            if (name.includes("[disclosures]")) checkDisclosures();
            if (name.includes("[status]")) checkPropertyStatuses();
            updateReviewCardValues();
        });

        if (window.jQuery) {
            window.jQuery(form).on("select2:select select2:unselect", () => {
                checkDisclosures();
                updateReviewCardValues();
            });
        }

        // Run initial checks
        checkFundsMethods();
        checkAuctionMethod();
        syncAuctionDates();
        checkDisclosures();
        checkPropertyStatuses();
    };

    // Live update review cards in Step 6
    const updateReviewCardValues = () => {
        // Step 1: Organization & Management
        const getVal = (name) => form.querySelector(`[name="${name}"]`)?.value || "—";
        const getText = (selector) => {
            const el = form.querySelector(selector);
            if (!el) return "—";
            if (el.tagName === "SELECT") {
                return el.options[el.selectedIndex]?.text || "—";
            }
            return el.value || "—";
        };

        // Organization
        const setReview = (id, val) => {
            const target = document.getElementById(id);
            if (target) target.textContent = val || "—";
        };

        setReview("review-org-name", getVal("data[organization][name]"));
        setReview("review-org-cr", getVal("data[organization][commercial_registration]"));
        setReview("review-org-unified", getVal("data[organization][unified_number]"));
        setReview("review-org-fal", getVal("data[organization][fal_license]"));

        // Manager
        setReview("review-mgr-name", getVal("data[manager][name]"));
        setReview("review-mgr-id", getVal("data[manager][identity_number]"));
        setReview("review-mgr-mobile", getVal("data[manager][mobile]"));
        setReview("review-mgr-email", getVal("data[manager][email]"));

        // Auctioneer
        setReview("review-auc-name", getVal("data[auctioneer][name]"));
        setReview("review-auc-id", getVal("data[auctioneer][identity_number]"));
        setReview("review-auc-mobile", getVal("data[auctioneer][mobile]"));
        setReview("review-auc-email", getVal("data[auctioneer][email]"));

        // Step 2: Auction Details & Funds
        setReview("review-auction-name", getVal("data[auction][name]"));
        setReview("review-auction-method", getText('[name="data[auction][method]"]'));
        setReview("review-auction-city", getVal("data[auction][city]"));
        const formatDisplay = (val) =>
            typeof window.formatSystemDate === "function"
                ? window.formatSystemDate(val, "")
                : val || "";
        setReview("review-auction-start", (formatDisplay(getVal("data[auction][start_date]")) + " " + getVal("data[auction][start_time]")).trim());
        setReview("review-auction-end", formatDisplay(getVal("data[auction][end_date]")));
        setReview("review-auction-disposition", getText('[name="data[auction][disposition]"]'));
        setReview("review-funds-receipt", getText('[name="data[funds][receipt_method]"]'));
        setReview("review-funds-safekeeping", getVal("data[funds][safekeeping]"));
        setReview("review-funds-refund", getText('[name="data[funds][refund_method]"]'));
        setReview("review-funds-entry", getText('[name="data[funds][entry_type]"]'));

        // Step 4: Disclosures
        const rightsSelect = form.querySelector('[name="data[disclosures][disposition_rights][]"], [name="data[disclosures][disposition_rights]"]');
        let rightsLabels = [];
        if (rightsSelect) {
            if (window.jQuery && window.jQuery(rightsSelect).data("select2")) {
                const selectedData = window.jQuery(rightsSelect).select2("data") || [];
                rightsLabels = selectedData.map((item) => item.text);
            } else if (rightsSelect.selectedOptions) {
                rightsLabels = Array.from(rightsSelect.selectedOptions).map((opt) => opt.text);
            }
        }
        setReview("review-disclosures-rights", rightsLabels.join("، ") || "—");
        setReview("review-declaration-rep", getVal("data[declaration][representative_name]"));

        // Step 5: Attachments count
        const filesCount = form.querySelectorAll('input[type="file"]').length;
        let selectedFilesCount = 0;
        form.querySelectorAll('input[type="file"]').forEach((f) => {
            if (f.files && f.files.length > 0) selectedFilesCount += f.files.length;
        });
        const existingLinks = form.querySelectorAll('a[href*="attachments"]').length;
        setReview("review-attachments-summary", `${existingLinks} مرفق مسجل سابقاً${selectedFilesCount > 0 ? ` + ${selectedFilesCount} ملف جديد محدد للرفع` : ""}`);
    };

    setupConditionalFields();
    updateReviewCardValues();

    // Listen to stepper step change
    form.addEventListener("stepChanged", (e) => {
        updateReviewCardValues();
    });

    form.addEventListener("input", updateReviewCardValues);
});
