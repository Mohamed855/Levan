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

    // Check individual property completeness from hidden fields
    const checkPropertyCompleteness = (propertyId) => {
        const hiddenDataEl = form.querySelector(`[data-prop-hidden="${propertyId}"]`);
        if (!hiddenDataEl) return false;

        const row = form.querySelector(`[data-property-row="${propertyId}"]`);
        const checkbox = form.querySelector(`[data-property-check="${propertyId}"]`);
        const badge = form.querySelector(`[data-property-badge="${propertyId}"]`);

        const getVal = (fieldName) => {
            const input = hiddenDataEl.querySelector(`[data-field="${fieldName}"]`);
            return input ? String(input.value || "").trim() : "";
        };

        const requiredFields = [
            "owner_name",
            "deed_number",
            "contract_number",
            "area",
            "type",
            "usage",
            "age",
            "city_district",
            "status",
            "ownership_share",
            "disclosure",
            "opening_value",
            "entry_amount",
            "estimated_value",
        ];

        let isComplete = true;

        for (const f of requiredFields) {
            const val = getVal(f);
            if (!val) {
                isComplete = false;
                break;
            }
            if (f === "area" || f === "ownership_share") {
                const num = parseFloat(val);
                if (isNaN(num) || num <= 0) {
                    isComplete = false;
                    break;
                }
            }
        }

        if (isComplete) {
            const status = getVal("status");
            if (status === "other" && !getVal("status_other")) {
                isComplete = false;
            }

            const agentName = getVal("agent_name");
            const agencyNum = getVal("agency_number");
            if ((agentName && !agencyNum) || (!agentName && agencyNum)) {
                isComplete = false;
            }
        }

        if (checkbox) {
            checkbox.checked = isComplete;
        }

        if (badge) {
            if (isComplete) {
                badge.className = "badge bg-label-success property-status-badge";
                badge.innerHTML = '<i class="ti ti-check me-1"></i>مكتمل البيانات';
            } else {
                badge.className = "badge bg-label-warning property-status-badge";
                badge.innerHTML = '<i class="ti ti-alert-triangle me-1"></i>بيانات ناقصة';
            }
        }

        if (row) {
            row.classList.toggle("table-success-subtle", isComplete);
        }

        return isComplete;
    };

    // Update all properties completeness and stats
    const updateAllPropertiesCompleteness = () => {
        const rows = form.querySelectorAll(".establishment-property-row");
        let completedCount = 0;
        rows.forEach((row) => {
            const propertyId = row.dataset.propertyId;
            if (checkPropertyCompleteness(propertyId)) {
                completedCount++;
            }
        });

        const countEl = document.getElementById("completed-establishment-props-count");
        if (countEl) {
            countEl.textContent = String(completedCount);
        }
    };

    // -------------------------------------------------------------
    // Single Property Modal Logic (مطابق تماماً لمودال إقفال المزاد)
    // -------------------------------------------------------------
    const propModalEl = document.getElementById("establishmentPropertyModal");
    let propModalInstance = null;

    if (propModalEl && window.bootstrap && window.bootstrap.Modal) {
        propModalInstance = window.bootstrap.Modal.getOrCreateInstance(propModalEl, {
            backdrop: "static",
            keyboard: false,
        });
    }

    // Modal field helper
    const getModalEl = (id) => document.getElementById(id);

    const clearModalErrors = () => {
        if (!propModalEl) return;
        propModalEl.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
        propModalEl.querySelectorAll(".invalid-feedback").forEach((el) => el.remove());
    };

    const setModalFieldError = (inputEl, message) => {
        if (!inputEl) return;
        inputEl.classList.add("is-invalid");
        let feedback = inputEl.parentElement?.querySelector(".invalid-feedback");
        if (!feedback) {
            feedback = document.createElement("div");
            feedback.className = "invalid-feedback d-block";
            inputEl.parentElement?.appendChild(feedback);
        }
        feedback.textContent = message;
    };

    // Populate and open the single modal
    const openPropertyModal = (propertyId, propIndex, propName, propNumber, propType) => {
        clearModalErrors();

        const titleEl = getModalEl("establishmentPropertyModalTitle");
        const numberEl = getModalEl("establishmentPropertyModalNumber");
        const typeEl = getModalEl("establishmentPropertyModalType");
        const idInput = getModalEl("modal_prop_id");
        const indexInput = getModalEl("modal_prop_index");

        if (titleEl) titleEl.textContent = `استكمال بيانات العقار: ${propName || "عقار"}`;
        if (numberEl) numberEl.textContent = propNumber || "-";
        if (typeEl) typeEl.textContent = propType || "-";
        if (idInput) idInput.value = propertyId;
        if (indexInput) indexInput.value = propIndex;

        const hiddenContainer = form.querySelector(`[data-prop-hidden="${propertyId}"]`);
        if (!hiddenContainer) return;

        const getHiddenVal = (field) => {
            return hiddenContainer.querySelector(`[data-field="${field}"]`)?.value ?? "";
        };

        const fields = [
            "owner_name",
            "deed_number",
            "contract_number",
            "area",
            "type",
            "usage",
            "usage_other",
            "age",
            "city_district",
            "status",
            "status_other",
            "ownership_share",
            "agent_name",
            "agency_number",
            "opening_value",
            "entry_amount",
            "estimated_value",
            "disclosure",
        ];

        fields.forEach((field) => {
            const input = getModalEl(`modal_prop_${field}`);
            if (input) {
                input.value = getHiddenVal(field);
                if (window.jQuery && window.jQuery(input).data("select2")) {
                    window.jQuery(input).val(getHiddenVal(field)).trigger("change.select2");
                }
            }
        });

        // Toggle status_other & usage_other visibility in modal
        const statusVal = getHiddenVal("status");
        const statusOtherCol = getModalEl("modal_prop_status_other_col");
        if (statusOtherCol) {
            statusOtherCol.classList.toggle("d-none", statusVal !== "other");
        }

        const usageVal = getHiddenVal("usage");
        const usageOtherCol = getModalEl("modal_prop_usage_other_col");
        if (usageOtherCol) {
            usageOtherCol.classList.toggle("d-none", usageVal !== "other");
        }

        // Reset file inputs in modal
        ["deed", "valuation", "representative_authority"].forEach((type) => {
            const fileInp = getModalEl(`modal_file_${type}`);
            if (fileInp) fileInp.value = "";
        });

        // Show existing files
        const table = document.getElementById("establishment-properties-table");
        let existingAttachments = {};
        try {
            existingAttachments = JSON.parse(table?.dataset?.existingAttachments || "{}");
        } catch (_) {}

        const propExisting = existingAttachments[propertyId] || {};

        ["deed", "valuation", "representative_authority"].forEach((type) => {
            const container = getModalEl(`modal_existing_${type}`);
            if (!container) return;

            const existing = propExisting[type];
            const stagedFile = hiddenContainer.querySelector(`[data-prop-files="${propertyId}"] input[name="attachments[${type}][${propertyId}]"]`);

            if (stagedFile && stagedFile.files && stagedFile.files[0]) {
                container.innerHTML = `
                    <div class="p-2 border rounded bg-light-subtle d-flex align-items-center justify-content-between small">
                        <span class="text-truncate"><i class="ti ti-file me-1 text-primary"></i>ملف محدد جديد: <strong>${escapeHtml(stagedFile.files[0].name)}</strong></span>
                        <span class="badge bg-label-info">محدد</span>
                    </div>
                `;
            } else if (existing && existing.url) {
                container.innerHTML = `
                    <div class="p-2 border rounded bg-light-subtle d-flex align-items-center justify-content-between small">
                        <a href="${escapeHtml(existing.url)}" target="_blank" class="text-primary text-truncate">
                            <i class="ti ti-paperclip me-1"></i>${escapeHtml(existing.name || "مستند سابق")}
                        </a>
                        <span class="badge bg-label-success">مرفوع مسبقاً</span>
                    </div>
                `;
            } else {
                container.innerHTML = `<span class="text-muted small">لا يوجد مرفق سابق</span>`;
            }
        });

        if (propModalInstance) {
            propModalInstance.show();
        }
    };

    // Row click and edit button click listeners
    $(document).on("click", ".establishment-property-row, .edit-establishment-prop-btn", function (e) {
        if ($(e.target).closest("input[type='checkbox'], a").length) return;
        const $target = $(this).closest(".establishment-property-row");
        const propId = $target.data("property-id");
        const propIndex = $target.data("property-index");
        const propName = $target.data("property-name");
        const propNumber = $target.data("property-number");
        const propType = $target.data("property-type");

        if (propId !== undefined) {
            openPropertyModal(propId, propIndex, propName, propNumber, propType);
        }
    });

    // Modal conditional toggles
    $(document).on("change", "#modal_prop_status", function () {
        const isOther = this.value === "other";
        const col = getModalEl("modal_prop_status_other_col");
        if (col) col.classList.toggle("d-none", !isOther);
    });

    $(document).on("change", "#modal_prop_usage", function () {
        const isOther = this.value === "other";
        const col = getModalEl("modal_prop_usage_other_col");
        if (col) col.classList.toggle("d-none", !isOther);
    });

    // Save modal data into hidden container
    const savePropertyModal = () => {
        clearModalErrors();

        const propertyId = getModalEl("modal_prop_id")?.value;
        if (!propertyId) return;

        const hiddenContainer = form.querySelector(`[data-prop-hidden="${propertyId}"]`);
        if (!hiddenContainer) return;

        let hasError = false;
        let firstInvalid = null;

        const requireField = (id, message, validateFn) => {
            const input = getModalEl(id);
            if (!input) return;
            const val = (input.value || "").trim();
            let invalid = !val;
            if (!invalid && validateFn) {
                invalid = !validateFn(val);
            }
            if (invalid) {
                setModalFieldError(input, message);
                hasError = true;
                if (!firstInvalid) firstInvalid = input;
            }
        };

        requireField("modal_prop_owner_name", "المالك / الملاك مطلوب");
        requireField("modal_prop_deed_number", "رقم الصك مطلوب");
        requireField("modal_prop_contract_number", "رقم عقد المزاد مطلوب");
        requireField("modal_prop_area", "المساحة يجب أن تكون أكبر من صفر", (v) => parseFloat(v) > 0);
        requireField("modal_prop_type", "نوع العقار مطلوب");
        requireField("modal_prop_usage", "استخدام العقار مطلوب");
        requireField("modal_prop_age", "عمر العقار مطلوب", (v) => parseInt(v, 10) >= 0);
        requireField("modal_prop_city_district", "المدينة / الحي مطلوب");
        requireField("modal_prop_status", "الحالة النظامية مطلوبة");
        requireField("modal_prop_ownership_share", "نسبة الملكية مطلوبة (بين 0.01 و 100)", (v) => {
            const n = parseFloat(v);
            return n > 0 && n <= 100;
        });
        requireField("modal_prop_opening_value", "قيمة افتتاح المزاد مطلوبة", (v) => parseFloat(v) >= 0);
        requireField("modal_prop_entry_amount", "مبلغ الدخول مطلوب", (v) => parseFloat(v) >= 0);
        requireField("modal_prop_estimated_value", "القيمة التقديرية مطلوبة", (v) => parseFloat(v) >= 0);
        requireField("modal_prop_disclosure", "الإفصاح الخاص بالعقار مطلوب");

        const statusVal = getModalEl("modal_prop_status")?.value;
        if (statusVal === "other") {
            requireField("modal_prop_status_other", "تفاصيل الحالة الأخرى مطلوبة عند اختيار أخرى");
        }

        const agentName = getModalEl("modal_prop_agent_name")?.value?.trim();
        const agencyNum = getModalEl("modal_prop_agency_number")?.value?.trim();
        if ((agentName && !agencyNum) || (!agentName && agencyNum)) {
            if (!agentName) setModalFieldError(getModalEl("modal_prop_agent_name"), "اسم الوكيل مطلوب عند كتابة رقم الوكالة");
            if (!agencyNum) setModalFieldError(getModalEl("modal_prop_agency_number"), "رقم الوكالة مطلوب عند كتابة اسم الوكيل");
            hasError = true;
            if (!firstInvalid) firstInvalid = !agentName ? getModalEl("modal_prop_agent_name") : getModalEl("modal_prop_agency_number");
        }

        if (hasError) {
            if (firstInvalid) {
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
            }
            if (window.toastr?.warning) {
                window.toastr.warning("يرجى استكمال الحقول الإلزامية للعقار.");
            }
            return;
        }

        // Copy modal values to hidden inputs
        const fields = [
            "owner_name",
            "deed_number",
            "contract_number",
            "area",
            "type",
            "usage",
            "usage_other",
            "age",
            "city_district",
            "status",
            "status_other",
            "ownership_share",
            "agent_name",
            "agency_number",
            "opening_value",
            "entry_amount",
            "estimated_value",
            "disclosure",
        ];

        fields.forEach((field) => {
            const input = getModalEl(`modal_prop_${field}`);
            const hidden = hiddenContainer.querySelector(`[data-field="${field}"]`);
            if (input && hidden) {
                hidden.value = (input.value || "").trim();
            }
        });

        // Stage files with DataTransfer into data-prop-files container
        const filesContainer = hiddenContainer.querySelector(`[data-prop-files="${propertyId}"]`);
        if (filesContainer) {
            ["deed", "valuation", "representative_authority"].forEach((type) => {
                const modalFileInp = getModalEl(`modal_file_${type}`);
                if (modalFileInp && modalFileInp.files && modalFileInp.files.length > 0) {
                    const hiddenFileInput = document.createElement("input");
                    hiddenFileInput.type = "file";
                    hiddenFileInput.name = `attachments[${type}][${propertyId}]`;
                    hiddenFileInput.classList.add("d-none");
                    if (window.DataTransfer) {
                        const dt = new DataTransfer();
                        dt.items.add(modalFileInp.files[0]);
                        hiddenFileInput.files = dt.files;
                    }
                    const existing = filesContainer.querySelector(`input[name="attachments[${type}][${propertyId}]"]`);
                    if (existing) existing.remove();
                    filesContainer.appendChild(hiddenFileInput);
                }
            });
        }

        // Update row display
        checkPropertyCompleteness(propertyId);
        updateAllPropertiesCompleteness();
        updateReviewCardValues();

        if (propModalInstance) {
            propModalInstance.hide();
        }

        if (window.toastr?.success) {
            window.toastr.success("تم حفظ بيانات ومرفقات العقار بنجاح.");
        }
    };

    $(document).on("click", "#btn-save-establishment-prop-modal", function (e) {
        e.preventDefault();
        savePropertyModal();
    });

    // -------------------------------------------------------------
    // Conditional field visibility handlers (تغيير طريقة الاستلام وغيرها)
    // -------------------------------------------------------------
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

        // Funds receipt & refund other methods (يدعم Select2 والأحداث الفورية)
        const checkFundsMethods = () => {
            let receipt = form.querySelector('[name="data[funds][receipt_method]"]')?.value;
            if (window.jQuery) {
                const $r = window.jQuery(form.querySelector('[name="data[funds][receipt_method]"]'));
                if ($r.data("select2")) receipt = $r.val();
            }
            toggleField('[name="data[funds][receipt_other]"]', receipt === "other");

            let refund = form.querySelector('[name="data[funds][refund_method]"]')?.value;
            if (window.jQuery) {
                const $rf = window.jQuery(form.querySelector('[name="data[funds][refund_method]"]'));
                if ($rf.data("select2")) refund = $rf.val();
            }
            toggleField('[name="data[funds][refund_other]"]', refund === "other");
        };

        // Auction method platform vs venue
        const checkAuctionMethod = () => {
            let method = form.querySelector('[name="data[auction][method]"]')?.value;
            if (window.jQuery) {
                const $m = window.jQuery(form.querySelector('[name="data[auction][method]"]'));
                if ($m.data("select2")) method = $m.val();
            }
            toggleField('[name="data[auction][platform]"]', method === "electronic" || method === "hybrid");
            toggleField('[name="data[auction][venue]"]', method === "in_person" || method === "hybrid");
        };

        // Disclosures & disposition rights
        const checkDisclosures = () => {
            const rightsSelect = form.querySelector('[name="data[disclosures][disposition_rights][]"], [name="data[disclosures][disposition_rights]"]');
            let hasOther = false;
            if (rightsSelect) {
                let vals = [];
                if (window.jQuery && window.jQuery(rightsSelect).data("select2")) {
                    vals = window.jQuery(rightsSelect).val() || [];
                } else if (rightsSelect.selectedOptions) {
                    vals = Array.from(rightsSelect.selectedOptions).map((opt) => opt.value);
                }

                if (!Array.isArray(vals)) vals = vals ? [vals] : [];

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

        const syncAuctionDates = () => {
            const startDate = form.querySelector('[name="data[auction][start_date]"]')?.value;
            const endDateInput = form.querySelector('[name="data[auction][end_date]"]');
            if (startDate && endDateInput && !endDateInput.value) {
                endDateInput.value = startDate;
            }
        };

        // Event listener for native input changes
        form.addEventListener("change", (e) => {
            const name = e.target?.name || "";
            if (name.includes("[funds]")) checkFundsMethods();
            if (name.includes("[auction][method]")) checkAuctionMethod();
            if (name.includes("[auction][start_date]")) syncAuctionDates();
            if (name.includes("[disclosures]")) checkDisclosures();
            updateReviewCardValues();
        });

        // Event listener for jQuery Select2 changes
        if (window.jQuery) {
            window.jQuery(form).on("select2:select select2:unselect select2:clear change", () => {
                checkFundsMethods();
                checkAuctionMethod();
                checkDisclosures();
                updateReviewCardValues();
            });
        }

        // Run initial checks
        checkFundsMethods();
        checkAuctionMethod();
        syncAuctionDates();
        checkDisclosures();
    };

    // -------------------------------------------------------------
    // Live update of review card values
    // -------------------------------------------------------------
    const updateReviewCardValues = () => {
        const getVal = (name) => {
            const input = form.querySelector(`[name="${name}"]`);
            return input ? String(input.value || "").trim() : "—";
        };

        const getText = (selector) => {
            const sel = form.querySelector(selector);
            if (!sel) return "—";
            if (sel.tagName === "SELECT") {
                const opt = sel.options[sel.selectedIndex];
                return opt ? opt.text.trim() : "—";
            }
            return (sel.value || "").trim() || "—";
        };

        const setReview = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val || "—";
        };

        // Step 1: Organization & Officials
        setReview("review-org-name", getVal("data[organization][name]"));
        setReview("review-org-cr", getVal("data[organization][commercial_registration]"));
        setReview("review-org-unified", getVal("data[organization][unified_number]"));
        setReview("review-org-fal", getVal("data[organization][fal_license]"));

        // Manager
        setReview("review-mgr-name", getVal("data[manager][name]"));
        setReview("review-mgr-id", getVal("data[manager][identity_number]"));
        setReview("review-mgr-mobile", getVal("data[manager][mobile]"));

        // Auctioneer
        setReview("review-auc-name", getVal("data[auctioneer][name]"));
        setReview("review-auc-id", getVal("data[auctioneer][identity_number]"));
        setReview("review-auc-mobile", getVal("data[auctioneer][mobile]"));

        // Step 2: Auction Details & Funds
        setReview("review-auction-name", getVal("data[auction][name]"));
        setReview("review-auction-method", getText('[name="data[auction][method]"]'));
        setReview("review-auction-city", getVal("data[auction][city]"));
        const formatDisplay = (val) =>
            typeof window.formatSystemDate === "function"
                ? window.formatSystemDate(val, "")
                : val || "";
        setReview(
            "review-auction-start",
            (formatDisplay(getVal("data[auction][start_date]")) + " " + getVal("data[auction][start_time]")).trim()
        );
        setReview("review-auction-end", formatDisplay(getVal("data[auction][end_date]")));
        const awardMin = getVal("data[auction][award_period]");
        setReview("review-auction-award-period", awardMin && awardMin !== "—" ? `${awardMin} دقيقة` : "—");
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

        const textareas = ["real_rights", "dispute", "value_information", "off_plan", "contribution"];
        const reviewMap = {
            real_rights: "review-real-rights",
            dispute: "review-dispute",
            value_information: "review-value-info",
            off_plan: "review-off-plan",
            contribution: "review-contribution",
        };
        textareas.forEach((k) => {
            const val = getVal(`data[disclosures][${k}_details]`);
            setReview(reviewMap[k], val && val !== "—" ? val : "لا يوجد");
        });

        // Step 5: Certificates review badges
        ["manager_certificate", "auctioneer_certificate"].forEach((type) => {
            const fileInput = form.querySelector(`[name="attachments[${type}]"]`);
            const fileBadge = form.querySelector(`[data-review-file-badge="${type}"]`);
            if (fileBadge) {
                if (fileInput?.files?.[0]) {
                    fileBadge.className = "badge bg-label-success";
                    fileBadge.textContent = "محدد للرفع: " + fileInput.files[0].name;
                }
            }
        });
    };

    // Step 3 Next button guard (إلزام اكتمال جميع العقارات)
    const propertiesStep = document.getElementById("step-establishment-properties");
    if (propertiesStep) {
        const nextBtn = propertiesStep.querySelector(".btn-next, [data-form-next]");
        if (nextBtn) {
            nextBtn.addEventListener(
                "click",
                (e) => {
                    const rows = form.querySelectorAll(".establishment-property-row");
                    const totalProperties = rows.length;
                    let completedCount = 0;
                    let firstIncomplete = null;

                    rows.forEach((row) => {
                        const propertyId = row.dataset.propertyId;
                        if (checkPropertyCompleteness(propertyId)) {
                            completedCount++;
                        } else if (!firstIncomplete) {
                            firstIncomplete = row;
                        }
                    });

                    if (completedCount < totalProperties) {
                        e.preventDefault();
                        e.stopImmediatePropagation();

                        const message = `يرجى استكمال بيانات ومرفقات جميع عقارات المزاد قبل المتابعة (${completedCount} من ${totalProperties} عقار مكتمل).`;
                        if (window.toastr?.warning) {
                            window.toastr.warning(message);
                        } else if (window.toastr?.error) {
                            window.toastr.error(message);
                        }

                        if (firstIncomplete) {
                            firstIncomplete.scrollIntoView({ behavior: "smooth", block: "center" });
                            firstIncomplete.classList.add("table-warning");
                            setTimeout(() => firstIncomplete.classList.remove("table-warning"), 2000);

                            const pId = firstIncomplete.dataset.propertyId;
                            const pIdx = firstIncomplete.dataset.propertyIndex;
                            const pName = firstIncomplete.dataset.propertyName;
                            const pNum = firstIncomplete.dataset.propertyNumber;
                            const pType = firstIncomplete.dataset.propertyType;
                            openPropertyModal(pId, pIdx, pName, pNum, pType);
                        }
                        return false;
                    }
                },
                true
            );
        }
    }

    setupConditionalFields();
    updateAllPropertiesCompleteness();
    updateReviewCardValues();

    // Listen to stepper step change
    form.addEventListener("stepChanged", () => {
        updateAllPropertiesCompleteness();
        updateReviewCardValues();
    });

    // Listen to wizard draft restored event
    form.addEventListener("wizardDraftRestored", () => {
        setupConditionalFields();
        updateAllPropertiesCompleteness();
        updateReviewCardValues();
    });

    form.addEventListener("input", updateReviewCardValues);
    form.addEventListener("change", (e) => {
        if (e.target?.type === "file") {
            updateReviewCardValues();
        }
    });
});
