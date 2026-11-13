import { renderFileCardHtml } from "../../shared/file-card.js";

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

            const usage = getVal("usage");
            if (usage === "other" && !getVal("usage_other")) {
                isComplete = false;
            }

            const agentName = getVal("agent_name");
            const agencyNum = getVal("agency_number");
            if ((agentName && !agencyNum) || (!agentName && agencyNum)) {
                isComplete = false;
            }

            // Check required deed attachment
            const table = document.getElementById("establishment-properties-table");
            let existingAttachments = {};
            try {
                existingAttachments = JSON.parse(table?.dataset?.existingAttachments || "{}");
            } catch (_) {}
            const propExisting = existingAttachments[propertyId] || {};
            const hasExistingDeed = Boolean(propExisting.deed && propExisting.deed.url);
            const stagedDeed = hiddenDataEl.querySelector(`[data-prop-files="${propertyId}"] input[name="attachments[deed][${propertyId}]"]`);
            if (!hasExistingDeed && !stagedDeed) {
                isComplete = false;
            }

            // Check representative authority if agent specified
            if (agentName) {
                const hasExistingAuth = Boolean(propExisting.representative_authority && propExisting.representative_authority.url);
                const stagedAuth = hiddenDataEl.querySelector(`[data-prop-files="${propertyId}"] input[name="attachments[representative_authority][${propertyId}]"]`);
                if (!hasExistingAuth && !stagedAuth) {
                    isComplete = false;
                }
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

    // Modal field helpers and error handling (matching auction closing modal pattern)
    const getModalEl = (id) => document.getElementById(id);

    const clearAllModalErrors = () => {
        if (!propModalEl) return;
        propModalEl.querySelectorAll(".is-invalid").forEach((el) => {
            el.classList.remove("is-invalid");
            el.removeAttribute("aria-invalid");
        });
        propModalEl.querySelectorAll(".select2-selection.is-invalid").forEach((el) => el.classList.remove("is-invalid"));
        propModalEl.querySelectorAll(".select2-container.is-invalid").forEach((el) => el.classList.remove("is-invalid"));
        propModalEl.querySelectorAll(".input-group.is-invalid").forEach((el) => el.classList.remove("is-invalid"));
        propModalEl.querySelectorAll("[data-form-field].is-invalid").forEach((el) => el.classList.remove("is-invalid"));
        propModalEl.querySelectorAll("[data-form-error]").forEach((el) => {
            el.textContent = "";
            el.hidden = true;
            el.setAttribute("hidden", "hidden");
            el.style.display = "none";
        });
        propModalEl.querySelectorAll(".invalid-feedback:not([data-form-error])").forEach((el) => el.remove());
    };

    const setModalFieldError = (inputEl, message) => {
        if (!inputEl) return;
        inputEl.classList.add("is-invalid");
        inputEl.setAttribute("aria-invalid", "true");

        const inputGroup = inputEl.closest(".input-group");
        if (inputGroup) {
            inputGroup.classList.add("is-invalid");
        }

        if (inputEl.tagName === "SELECT") {
            const select2Container = inputEl.nextElementSibling?.matches(".select2, .select2-container")
                ? inputEl.nextElementSibling
                : inputEl.parentElement?.querySelector(".select2-container");
            if (select2Container) {
                select2Container.classList.add("is-invalid");
                const selection = select2Container.querySelector(".select2-selection");
                if (selection) selection.classList.add("is-invalid");
            }
        }

        const fieldWrapper = inputEl.closest("[data-form-field]") || inputEl.parentElement;
        if (fieldWrapper) {
            fieldWrapper.classList.add("is-invalid");
            let errorEl = fieldWrapper.querySelector("[data-form-error]");
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.hidden = false;
                errorEl.removeAttribute("hidden");
                errorEl.classList.remove("hidden");
                errorEl.style.display = "block";
            } else {
                let feedback = fieldWrapper.querySelector(".invalid-feedback");
                if (!feedback) {
                    feedback = document.createElement("div");
                    feedback.className = "invalid-feedback d-block";
                    fieldWrapper.appendChild(feedback);
                }
                feedback.textContent = message;
                feedback.style.display = "block";
            }
        }
    };

    const clearModalFieldError = (inputEl) => {
        if (!inputEl) return;
        inputEl.classList.remove("is-invalid");
        inputEl.removeAttribute("aria-invalid");

        const inputGroup = inputEl.closest(".input-group");
        if (inputGroup) {
            inputGroup.classList.remove("is-invalid");
        }

        if (inputEl.tagName === "SELECT") {
            const select2Container = inputEl.nextElementSibling?.matches(".select2, .select2-container")
                ? inputEl.nextElementSibling
                : inputEl.parentElement?.querySelector(".select2-container");
            if (select2Container) {
                select2Container.classList.remove("is-invalid");
                const selection = select2Container.querySelector(".select2-selection");
                if (selection) selection.classList.remove("is-invalid");
            }
        }

        const fieldWrapper = inputEl.closest("[data-form-field]") || inputEl.parentElement;
        if (fieldWrapper) {
            fieldWrapper.classList.remove("is-invalid");
            const errorEl = fieldWrapper.querySelector("[data-form-error]");
            if (errorEl) {
                errorEl.textContent = "";
                errorEl.hidden = true;
                errorEl.setAttribute("hidden", "hidden");
                errorEl.style.display = "none";
            }
            const feedback = fieldWrapper.querySelector(".invalid-feedback:not([data-form-error])");
            if (feedback) {
                feedback.remove();
            }
        }
    };

    const initEstablishmentModalSelect2 = () => {
        const $select = $("#modal_prop_status");
        if ($select.length && window.jQuery && $.fn.select2) {
            if ($select.data("select2")) {
                $select.select2("destroy");
            }
            $select.select2({
                dropdownParent: $("#establishmentPropertyModal"),
                placeholder: "اختر الحالة النظامية",
                allowClear: false,
                width: "100%",
                dir: "rtl",
                minimumResultsForSearch: Infinity,
            });
        }
    };

    $(document).on("shown.bs.modal", "#establishmentPropertyModal", function () {
        initEstablishmentModalSelect2();
    });

    // Populate and open the single modal
    const openPropertyModal = (propertyId, propIndex, propName, propNumber, propType) => {
        clearAllModalErrors();

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

        // Dynamic locking for inherited contract number, agent info, and estimated value
        const isContractInherited = hiddenContainer.dataset.propInheritedContract === "1";
        const isAgentInherited = hiddenContainer.dataset.propInheritedAgent === "1";
        const isEstimatedInherited = hiddenContainer.dataset.propInheritedEstimated === "1";

        const contractInp = getModalEl("modal_prop_contract_number");
        if (contractInp) {
            contractInp.readOnly = isContractInherited;
            contractInp.classList.toggle("bg-light", isContractInherited);
            contractInp.classList.toggle("cursor-not-allowed", isContractInherited);
            contractInp.style.pointerEvents = isContractInherited ? "none" : "";
            contractInp.tabIndex = isContractInherited ? -1 : 0;
            const help = contractInp.parentElement?.querySelector(".form-text");
            if (help) {
                help.textContent = isContractInherited
                    ? "مستورد من رقم إسناد المزاد وغير قابل للتعديل"
                    : "رقم الإسناد أو عقد الوساطة للمزاد";
            }
        }

        const agentNameInp = getModalEl("modal_prop_agent_name");
        const agencyNumInp = getModalEl("modal_prop_agency_number");
        [agentNameInp, agencyNumInp].forEach((inp) => {
            if (inp) {
                inp.readOnly = isAgentInherited;
                inp.classList.toggle("bg-light", isAgentInherited);
                inp.classList.toggle("cursor-not-allowed", isAgentInherited);
                inp.style.pointerEvents = isAgentInherited ? "none" : "";
                inp.tabIndex = isAgentInherited ? -1 : 0;
                const help = inp.parentElement?.querySelector(".form-text");
                if (help && isAgentInherited) {
                    help.textContent = "مستورد من بيانات الوكيل المسجل بالعقار";
                }
            }
        });

        // Estimated value: locked only if imported from property, editable otherwise
        const estimatedInp = getModalEl("modal_prop_estimated_value");
        if (estimatedInp) {
            estimatedInp.readOnly = isEstimatedInherited;
            estimatedInp.classList.toggle("bg-light", isEstimatedInherited);
            estimatedInp.classList.toggle("cursor-not-allowed", isEstimatedInherited);
            estimatedInp.style.pointerEvents = isEstimatedInherited ? "none" : "";
            estimatedInp.tabIndex = isEstimatedInherited ? -1 : 0;
            const help = estimatedInp.parentElement?.querySelector(".form-text");
            if (help) {
                help.textContent = isEstimatedInherited ? "مستورد من تقييم العقار" : "";
            }
        }

        // Opening value: ALWAYS editable (entered in the establishment request)
        const openingInp = getModalEl("modal_prop_opening_value");
        if (openingInp) {
            openingInp.readOnly = false;
            openingInp.classList.remove("bg-light", "cursor-not-allowed");
            openingInp.style.pointerEvents = "";
            openingInp.tabIndex = 0;
        }

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

        // Setup file inputs in modal with unified controller & existing/staged files
        const table = document.getElementById("establishment-properties-table");
        let existingAttachments = {};
        try {
            existingAttachments = JSON.parse(table?.dataset?.existingAttachments || "{}");
        } catch (_) {}

        const propExisting = existingAttachments[propertyId] || {};

        ["deed", "valuation", "representative_authority"].forEach((type) => {
            const fileInp = getModalEl(`modal_file_${type}`);
            if (!fileInp) return;

            const controller = window.SharedFileUpload?.getController(fileInp);
            const existing = propExisting[type];
            const stagedFile = hiddenContainer.querySelector(
                `[data-prop-files="${propertyId}"] input[name="attachments[${type}][${propertyId}]"]`
            );

            if (controller) {
                controller.clear();
                if (existing && existing.url) {
                    controller.setExistingFile({
                        name: existing.name || "مستند سابق",
                        url: existing.url,
                        path: existing.url,
                    });
                } else {
                    controller.clearExistingFiles();
                }

                if (stagedFile && stagedFile.files && stagedFile.files[0]) {
                    controller.setFiles([stagedFile.files[0]]);
                }
            } else {
                fileInp.value = "";
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

    // Validate individual field in establishment property modal
    const validateEstablishmentModalField = (inputEl) => {
        if (!inputEl) return true;
        const id = inputEl.id;
        const val = (inputEl.value || "").trim();
        let error = null;

        if (id === "modal_prop_owner_name") {
            if (!val) {
                error = "المالك / الملاك مطلوب.";
            } else if (val.length < 3) {
                error = "اسم المالك يجب ألا يقل عن 3 أحرف.";
            }
        } else if (id === "modal_prop_deed_number") {
            if (!val) {
                error = "رقم الصك مطلوب.";
            } else if (!/^\d+$/.test(val)) {
                error = "رقم الصك يجب أن يحتوي على أرقام فقط.";
            }
        } else if (id === "modal_prop_contract_number") {
            if (!val) {
                error = "رقم عقد المزاد / الإسناد مطلوب.";
            } else if (val.length > 100) {
                error = "رقم العقد يجب ألا يتجاوز 100 حرف.";
            }
        } else if (id === "modal_prop_area") {
            if (!val) {
                error = "المساحة مطلوبة.";
            } else if (isNaN(Number(val)) || Number(val) <= 0) {
                error = "المساحة يجب أن تكون أكبر من صفر.";
            }
        } else if (id === "modal_prop_type") {
            if (!val) {
                error = "نوع العقار مطلوب.";
            }
        } else if (id === "modal_prop_usage") {
            if (!val) {
                error = "استخدام العقار مطلوب.";
            }
        } else if (id === "modal_prop_usage_other") {
            const usageVal = getModalEl("modal_prop_usage")?.value;
            if (usageVal === "other" && !val) {
                error = "تفاصيل الاستخدام الآخر مطلوبة.";
            }
        } else if (id === "modal_prop_age") {
            if (val === "") {
                error = "عمر العقار مطلوب (أدخل 0 للأرض).";
            } else if (isNaN(Number(val)) || !Number.isInteger(Number(val)) || Number(val) < 0 || Number(val) > 200) {
                error = "عمر العقار يجب أن يكون عدداً صحيحاً بين 0 و 200.";
            }
        } else if (id === "modal_prop_city_district") {
            if (!val) {
                error = "المدينة / الحي مطلوب.";
            }
        } else if (id === "modal_prop_status") {
            if (!val) {
                error = "الحالة النظامية مطلوبة.";
            }
        } else if (id === "modal_prop_status_other") {
            const statusVal = getModalEl("modal_prop_status")?.value;
            if (statusVal === "other" && !val) {
                error = "تفاصيل الحالة الأخرى مطلوبة عند اختيار أخرى.";
            }
        } else if (id === "modal_prop_ownership_share") {
            if (!val) {
                error = "نسبة الملكية المعروضة مطلوبة.";
            } else if (isNaN(Number(val)) || Number(val) <= 0 || Number(val) > 100) {
                error = "نسبة الملكية المعروضة يجب أن تكون بين 0.01% و 100%.";
            }
        } else if (id === "modal_prop_opening_value") {
            if (!val) {
                error = "قيمة افتتاح المزاد مطلوبة.";
            } else if (isNaN(Number(val)) || Number(val) < 0) {
                error = "قيمة افتتاح المزاد يجب أن تكون رقماً غير سالب.";
            }
        } else if (id === "modal_prop_entry_amount") {
            if (!val) {
                error = "مبلغ الدخول مطلوب.";
            } else if (isNaN(Number(val)) || Number(val) < 0) {
                error = "مبلغ الدخول يجب أن يكون رقماً غير سالب.";
            }
        } else if (id === "modal_prop_estimated_value") {
            if (!val) {
                error = "القيمة التقديرية مطلوبة.";
            } else if (isNaN(Number(val)) || Number(val) < 0) {
                error = "القيمة التقديرية يجب أن تكون رقماً غير سالب.";
            }
        } else if (id === "modal_prop_disclosure") {
            if (!val) {
                error = "الإفصاح الخاص بالعقار مطلوب.";
            }
        } else if (id === "modal_prop_agent_name") {
            const agencyNum = getModalEl("modal_prop_agency_number")?.value?.trim();
            if (agencyNum && !val) {
                error = "اسم الوكيل مطلوب عند كتابة رقم الوكالة.";
            }
        } else if (id === "modal_prop_agency_number") {
            const agentName = getModalEl("modal_prop_agent_name")?.value?.trim();
            if (agentName && !val) {
                error = "رقم الوكالة مطلوب عند كتابة اسم الوكيل.";
            } else if (val && !/^\d+$/.test(val)) {
                error = "رقم الوكالة يجب أن يحتوي على أرقام فقط.";
            }
        }

        if (error) {
            setModalFieldError(inputEl, error);
            return false;
        } else {
            clearModalFieldError(inputEl);
            return true;
        }
    };

    // Live validation listener for user changes inside establishment property modal (on change, blur, focusout, and live input if invalid)
    $(document).on(
        "change blur focusout input",
        "#establishmentPropertyModal input:not([type='hidden']):not([type='file']), #establishmentPropertyModal select, #establishmentPropertyModal textarea",
        function (e) {
            if (e.type === "focusout" || e.type === "blur" || e.type === "change" || $(this).hasClass("is-invalid")) {
                validateEstablishmentModalField(this);
            }
        },
    );

    // Modal conditional toggles with live validation sync
    $(document).on("change", "#modal_prop_status", function () {
        const isOther = this.value === "other";
        const col = getModalEl("modal_prop_status_other_col");
        if (col) {
            col.classList.toggle("d-none", !isOther);
            if (!isOther) {
                clearModalFieldError(getModalEl("modal_prop_status_other"));
            }
        }
        validateEstablishmentModalField(this);
    });

    $(document).on("change", "#modal_prop_usage", function () {
        const isOther = this.value === "other";
        const col = getModalEl("modal_prop_usage_other_col");
        if (col) {
            col.classList.toggle("d-none", !isOther);
            if (!isOther) {
                clearModalFieldError(getModalEl("modal_prop_usage_other"));
            }
        }
        validateEstablishmentModalField(this);
    });

    $(document).on("input change blur focusout", "#modal_prop_agent_name, #modal_prop_agency_number", function () {
        const agentName = getModalEl("modal_prop_agent_name")?.value?.trim();
        const agencyNum = getModalEl("modal_prop_agency_number")?.value?.trim();
        if (!agentName && !agencyNum) {
            clearModalFieldError(getModalEl("modal_prop_agent_name"));
            clearModalFieldError(getModalEl("modal_prop_agency_number"));
            clearModalFieldError(getModalEl("modal_file_representative_authority"));
        } else {
            if (agentName) validateEstablishmentModalField(getModalEl("modal_prop_agent_name"));
            if (agencyNum) validateEstablishmentModalField(getModalEl("modal_prop_agency_number"));
        }
    });

    $(document).on("change", "#modal_file_deed, #modal_file_representative_authority", function () {
        if (this.files && this.files.length > 0) {
            clearModalFieldError(this);
        }
    });

    // Save modal data into hidden container with strict validation (matching auction closing modal)
    const savePropertyModal = () => {
        clearAllModalErrors();

        const propertyId = getModalEl("modal_prop_id")?.value;
        if (!propertyId) return;

        const hiddenContainer = form.querySelector(`[data-prop-hidden="${propertyId}"]`);
        if (!hiddenContainer) return;

        let hasError = false;
        let firstInvalid = null;

        const validate = (el) => {
            if (!el) return;
            if (!validateEstablishmentModalField(el)) {
                hasError = true;
                if (!firstInvalid) firstInvalid = el;
            }
        };

        validate(getModalEl("modal_prop_owner_name"));
        validate(getModalEl("modal_prop_deed_number"));
        validate(getModalEl("modal_prop_contract_number"));
        validate(getModalEl("modal_prop_area"));
        validate(getModalEl("modal_prop_type"));
        validate(getModalEl("modal_prop_usage"));
        if (getModalEl("modal_prop_usage")?.value === "other") {
            validate(getModalEl("modal_prop_usage_other"));
        }
        validate(getModalEl("modal_prop_age"));
        validate(getModalEl("modal_prop_city_district"));
        validate(getModalEl("modal_prop_status"));
        if (getModalEl("modal_prop_status")?.value === "other") {
            validate(getModalEl("modal_prop_status_other"));
        }
        validate(getModalEl("modal_prop_ownership_share"));
        validate(getModalEl("modal_prop_opening_value"));
        validate(getModalEl("modal_prop_entry_amount"));
        validate(getModalEl("modal_prop_estimated_value"));
        validate(getModalEl("modal_prop_disclosure"));

        const agentName = getModalEl("modal_prop_agent_name")?.value?.trim();
        const agencyNum = getModalEl("modal_prop_agency_number")?.value?.trim();
        if (agentName || agencyNum) {
            validate(getModalEl("modal_prop_agent_name"));
            validate(getModalEl("modal_prop_agency_number"));
        }

        // Validate required deed attachment
        const table = document.getElementById("establishment-properties-table");
        let existingAttachments = {};
        try {
            existingAttachments = JSON.parse(table?.dataset?.existingAttachments || "{}");
        } catch (_) {}
        const propExisting = existingAttachments[propertyId] || {};
        const deedInput = getModalEl("modal_file_deed");
        const deedController = window.SharedFileUpload?.getController(deedInput);
        const hasExistingDeed = Boolean(propExisting.deed && propExisting.deed.url);
        const stagedDeed = hiddenContainer.querySelector(`[data-prop-files="${propertyId}"] input[name="attachments[deed][${propertyId}]"]`);
        const hasSelectedDeed = (deedInput && deedInput.files && deedInput.files.length > 0) || (deedController && deedController.getFiles().length > 0);
        const isDeedPresent = hasExistingDeed || (stagedDeed && (!deedController || deedController.getFiles().length > 0)) || hasSelectedDeed;

        if (!isDeedPresent) {
            setModalFieldError(deedInput, "وثيقة الملكية (الصك الإلكتروني) مطلوبة لكل عقار.");
            hasError = true;
            if (!firstInvalid) firstInvalid = deedInput;
        }

        // Validate agent representative authority if agent specified
        if (agentName) {
            const authInput = getModalEl("modal_file_representative_authority");
            const authController = window.SharedFileUpload?.getController(authInput);
            const hasExistingAuth = Boolean(propExisting.representative_authority && propExisting.representative_authority.url);
            const stagedAuth = hiddenContainer.querySelector(`[data-prop-files="${propertyId}"] input[name="attachments[representative_authority][${propertyId}]"]`);
            const hasSelectedAuth = (authInput && authInput.files && authInput.files.length > 0) || (authController && authController.getFiles().length > 0);
            const isAuthPresent = hasExistingAuth || (stagedAuth && (!authController || authController.getFiles().length > 0)) || hasSelectedAuth;

            if (!isAuthPresent) {
                setModalFieldError(authInput, "مستند التفويض أو الوكالة مطلوب عند وجود وكيل مسجل.");
                hasError = true;
                if (!firstInvalid) firstInvalid = authInput;
            }
        }

        // Strict halt: NEVER save and NEVER close modal if any validation error exists
        if (hasError) {
            if (firstInvalid) {
                if ($(firstInvalid).data("select2")) {
                    $(firstInvalid).select2("open");
                } else if (typeof firstInvalid.focus === "function") {
                    firstInvalid.focus();
                }
                firstInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
            }
            if (window.toastr?.warning) {
                window.toastr.warning("يرجى استكمال جميع الحقول والمستندات الإلزامية للعقار بشكل صحيح.");
            }
            return false;
        }

        // Copy modal values to hidden inputs only after 100% successful validation
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
                const existingStaged = filesContainer.querySelector(`input[name="attachments[${type}][${propertyId}]"]`);
                const controller = window.SharedFileUpload?.getController(modalFileInp);
                const hasSelectedFiles = modalFileInp && modalFileInp.files && modalFileInp.files.length > 0;

                if (hasSelectedFiles) {
                    const hiddenFileInput = document.createElement("input");
                    hiddenFileInput.type = "file";
                    hiddenFileInput.name = `attachments[${type}][${propertyId}]`;
                    hiddenFileInput.classList.add("d-none");
                    if (window.DataTransfer) {
                        const dt = new DataTransfer();
                        dt.items.add(modalFileInp.files[0]);
                        hiddenFileInput.files = dt.files;
                    }
                    if (existingStaged) existingStaged.remove();
                    filesContainer.appendChild(hiddenFileInput);
                } else if (existingStaged && (!controller || controller.getFiles().length === 0)) {
                    existingStaged.remove();
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

        // Step 5: Certificates review cards (مطابقة لأسلوب بطاقات المراجعة في إنشاء المزاد)
        const reviewCertificates = document.getElementById("review-certificates-list");
        const reviewCertificatesCount = document.getElementById("review-certificates-count");
        if (reviewCertificates) {
            const certTypes = [
                { type: "manager_certificate", label: "شهادة دورة مدير المزاد" },
                { type: "auctioneer_certificate", label: "شهادة دورة محرّج المزاد" },
            ];
            const certItems = [];

            certTypes.forEach(({ type, label }) => {
                const input = form.querySelector(`[name="attachments[${type}]"]`);
                const file = input?.files?.[0];
                if (file) {
                    certItems.push({
                        name: file.name,
                        file,
                        metaLabel: `${label} (جديد)`,
                    });
                } else {
                    const wrapper = input?.closest("[data-file-upload-wrapper]") || input?.parentElement;
                    const existingCard = wrapper?.querySelector("[data-existing-file-card]:not(.d-none)");
                    if (existingCard) {
                        const existingName = existingCard.querySelector(".file-item-name")?.textContent?.trim() || label;
                        const existingViewLink = existingCard.querySelector("a.btn-view-file")?.href;
                        const existingImg = existingCard.querySelector("img.file-item-preview")?.src;
                        const resolvedUrl = existingViewLink || existingImg || null;
                        certItems.push({
                            name: existingName,
                            url: resolvedUrl,
                            downloadUrl: resolvedUrl,
                            metaLabel: label,
                        });
                    }
                }
            });

            if (reviewCertificatesCount) {
                reviewCertificatesCount.textContent = String(certItems.length);
            }

            if (!certItems.length) {
                reviewCertificates.innerHTML = '<p class="text-muted mb-0" data-empty>لم يتم إرفاق شهادات بعد.</p>';
            } else {
                reviewCertificates.innerHTML = certItems.map(renderFileCardHtml).join("");
            }
        }
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
    form.addEventListener("file:changed", updateReviewCardValues);
    form.addEventListener("file:removed", updateReviewCardValues);
    form.addEventListener("file:removed-existing", updateReviewCardValues);
});
