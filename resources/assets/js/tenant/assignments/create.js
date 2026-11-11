"use strict";

import { initCityDistrictSelector } from "../../shared/city-district-selector.js";
import { renderFileCardHtml } from "../../shared/file-card.js";

document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("#assignment-create-form, #assignment-edit-form");
    if (!form) return;

    initCityDistrictSelector({
        citySelector: "#assignment-city",
        districtSelector: "#assignment-district",
    });

    // ==================== ASSIGNMENT NUMBER GENERATOR ====================
    window.generateAssignmentNumber = function () {
        const year = new Date().getFullYear();
        const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
        let random = "";
        for (let i = 0; i < 4; i++) {
            random += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        const code = `ES-${year}-${random}`;

        const input = document.getElementById("assignment_number") || form.querySelector('input[name="assignment_number"]');
        if (input) {
            input.value = code;
            input.dispatchEvent(new Event("input", { bubbles: true }));
            input.dispatchEvent(new Event("change", { bubbles: true }));
        }

        const btn = document.getElementById("btn-generate-code") || form.querySelector(".btn-generate-code");
        const icon = btn ? btn.querySelector("i") : null;
        if (icon) {
            icon.classList.add("ti-spin");
            setTimeout(() => icon.classList.remove("ti-spin"), 500);
        }

        return code;
    };
    window.generateCode = window.generateCode || window.generateAssignmentNumber;

    const assignmentNumberInput = document.getElementById("assignment_number") || form.querySelector('input[name="assignment_number"]');
    if (assignmentNumberInput) {
        assignmentNumberInput.addEventListener("input", function () {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9_-]/g, "");
            if (start !== null && end !== null) {
                this.setSelectionRange(start, end);
            }
        });
    }

    const generateBtn = document.getElementById("btn-generate-code") || form.querySelector(".btn-generate-code");
    if (generateBtn) {
        generateBtn.addEventListener("click", function (e) {
            e.preventDefault();
            window.generateAssignmentNumber();
        });
    }

    const selectedProperties = new Map();
    const propertyInputs = document.getElementById("selected-property-inputs");
    const propertyCards = document.getElementById("selected-properties-cards");
    const propertyCount = document.getElementById("selected-properties-count");
    const propertyImageFallback =
        form.dataset.propertyImageFallback || "/static/brand/logos/logo.svg";

    propertyCards?.querySelectorAll("[data-property-card]").forEach((card) => {
        const id = String(card.dataset.propertyCard);
        selectedProperties.set(id, {
            id,
            name: card.querySelector("h6, .text-truncate")?.textContent.trim() || card.querySelector(".fw-semibold")?.textContent.trim() || "",
            number: card.querySelector("code")?.textContent.trim() || "",
            type: card.querySelector(".badge")?.textContent.trim() || "",
            city: card.querySelector(".text-muted")?.textContent.replace(/[\n\r]+/g, " ").trim() || "",
            image: card.querySelector("img")?.src || propertyImageFallback,
        });
    });



    function escapeHtml(value) {
        const element = document.createElement("div");
        element.textContent = String(value ?? "");
        return element.innerHTML;
    }

    function renderPropertyCards(target = propertyCards, removable = true) {
        if (!selectedProperties.size) {
            target.innerHTML =
                '<p class="text-muted mb-0" data-empty>لم يتم اختيار عقارات بعد.</p>';
            return;
        }

        target.innerHTML = [...selectedProperties.values()]
            .map(
                (property) => `
      <article class="assignment-property-card">
        <img class="assignment-property-card__image" src="${escapeHtml(property.image || propertyImageFallback)}" alt="" onerror="this.onerror=null;this.src='${escapeHtml(propertyImageFallback)}';">
        <div class="assignment-property-card__content">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <code class="copyable-code">${escapeHtml(property.number || "-")}</code>
            <span class="badge bg-info">${escapeHtml(property.type || "-")}</span>
          </div>
          <h6 class="mb-1 text-body text-truncate" title="${escapeHtml(property.name)}">${escapeHtml(property.name)}</h6>
          <p class="mb-0 text-muted small"><i class="ti ti-map-pin me-1"></i>${escapeHtml(property.city || "-")}</p>
        </div>
        ${removable ? `<button type="button" class="btn btn-sm" data-remove-property="${property.id}" aria-label="إزالة ${escapeHtml(property.name)}"><i class="ti ti-x"></i></button>` : ""}
      </article>`,
            )
            .join("");
    }

    function syncProperties() {
        propertyInputs.innerHTML = [...selectedProperties.keys()]
            .map(
                (id) =>
                    `<input type="hidden" name="property_ids[]" value="${id}" data-form-control>`,
            )
            .join("");
        propertyCount.textContent = String(selectedProperties.size);
        renderPropertyCards();
        document.querySelectorAll(".property-selector").forEach((checkbox) => {
            checkbox.checked = selectedProperties.has(String(checkbox.value));
        });

        if (selectedProperties.size > 0) {
            const errorEl = document.getElementById("property_ids-error");
            if (errorEl) {
                errorEl.textContent = "";
                errorEl.setAttribute("hidden", "true");
                errorEl.classList.add("hidden");
                errorEl.style.display = "none";
            }
            const wrapper = form.querySelector(
                '[data-form-field][data-field-name="property_ids"]',
            );
            if (wrapper) {
                wrapper.classList.remove("is-invalid");
            }
        }

        const asgPropStep = document.getElementById("assignment-properties-validation");
        const propNextBtn = asgPropStep?.querySelector("[data-wizard-next], .btn-next:not(.btn-submit)");
        if (propNextBtn) {
            const propertyInputsList = Array.from(
                form.querySelectorAll('input[name="property_ids[]"], input[name="property_ids"]')
            ).filter(
                (input) => input.value && String(input.value).trim() !== "" && String(input.value) !== "0"
            );
            const isEmpty = propertyInputsList.length === 0;

            if (isEmpty) {
                propNextBtn.disabled = true;
                propNextBtn.setAttribute("disabled", "disabled");
                propNextBtn.classList.add("disabled");
                propNextBtn.style.pointerEvents = "none";
            } else {
                propNextBtn.disabled = false;
                propNextBtn.removeAttribute("disabled");
                propNextBtn.classList.remove("disabled");
                propNextBtn.style.pointerEvents = "";
            }
        }

        window.SharedFormValidation?.syncAllPropertyStepButtons?.(form);
        form.dispatchEvent(new Event("change", { bubbles: true }));
    }

    function propertyFromCheckbox(checkbox) {
        return {
            id: String(checkbox.value),
            name: checkbox.dataset.name || "",
            number: checkbox.dataset.number || "",
            type: checkbox.dataset.type || "",
            classification: checkbox.dataset.classification || "",
            city: checkbox.dataset.city || "",
            image: checkbox.dataset.image || propertyImageFallback,
        };
    }

    document.addEventListener("change", (event) => {
        const checkbox = event.target.closest(".property-selector");
        if (!checkbox) return;
        const property = propertyFromCheckbox(checkbox);
        if (checkbox.checked) selectedProperties.set(property.id, property);
        else selectedProperties.delete(property.id);
        syncProperties();
    });

    // Clicking on a row toggles its property-selector checkbox
    document.addEventListener("click", (event) => {
        const tr = event.target.closest(
            "#assignment-properties-table tbody tr",
        );
        if (!tr) return;
        if (event.target.closest("input, button, a, label")) return;
        const checkbox = tr.querySelector(".property-selector");
        if (!checkbox) return;
        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event("change", { bubbles: true }));
    });

    document.addEventListener("change", (event) => {
        if (event.target.id !== "select-all-properties") return;
        document
            .querySelectorAll("#assignment-properties-table .property-selector")
            .forEach((checkbox) => {
                checkbox.checked = event.target.checked;
                const property = propertyFromCheckbox(checkbox);
                if (checkbox.checked)
                    selectedProperties.set(property.id, property);
                else selectedProperties.delete(property.id);
            });
        syncProperties();
    });

    propertyCards.addEventListener("click", (event) => {
        const button = event.target.closest("[data-remove-property]");
        if (!button) return;
        selectedProperties.delete(String(button.dataset.removeProperty));
        syncProperties();
    });

    if (window.jQuery) {
        const cleanTableControls = () => {
            const tableWrapper = document.getElementById(
                "assignment-properties-table_wrapper",
            );
            tableWrapper
                ?.querySelectorAll("input, select, textarea")
                .forEach((control) => {
                    if (
                        !control.classList.contains("property-selector") &&
                        control.id !== "select-all-properties"
                    ) {
                        control.removeAttribute("name");
                        control.dataset.validationSkip = "true";
                        control.classList.remove("is-valid", "is-invalid");
                        control.removeAttribute("aria-invalid");
                    }
                });
        };

        cleanTableControls();

        window.jQuery("#assignment-properties-table").on("draw.dt", () => {
            cleanTableControls();
            const selectAll = document.getElementById("select-all-properties");
            if (selectAll) selectAll.checked = false;
            document
                .querySelectorAll(".property-selector")
                .forEach((checkbox) => {
                    checkbox.checked = selectedProperties.has(
                        String(checkbox.value),
                    );
                });
        });
    }

    // Card rendering imported from shared/file-card.js

    function renderReviewPrimaryDocuments() {
        const list = document.getElementById("review-primary-documents");
        const count = document.getElementById("review-primary-documents-count");
        if (!list) return;

        const items = [];
        const imgInput = form.elements.namedItem("assignment_image");
        const letterInput = form.elements.namedItem("assignment_letter");

        // 1. Assignment Image: newly selected file, or existing file if unchanged
        if (imgInput?.files?.[0]) {
            items.push({
                name: imgInput.files[0].name,
                file: imgInput.files[0],
                metaLabel: "صورة الإسناد (جديدة)",
            });
        } else {
            const existingImgCard = document.querySelector('[data-existing-card-field="assignment_image"] .assignment-file-card');
            if (existingImgCard) {
                const name = existingImgCard.querySelector(".assignment-file-card__name")?.textContent?.trim() || "صورة الإسناد";
                const url = existingImgCard.querySelector("a.assignment-file-card__action")?.href || existingImgCard.querySelector("img.assignment-file-card__img")?.src;
                items.push({
                    name,
                    url: url || null,
                    metaLabel: "صورة الإسناد (الحالية)",
                });
            }
        }

        // 2. Assignment Letter: newly selected file, or existing file if unchanged
        if (letterInput?.files?.[0]) {
            items.push({
                name: letterInput.files[0].name,
                file: letterInput.files[0],
                metaLabel: "خطاب الإسناد (جديد)",
            });
        } else {
            const existingLetterCard = document.querySelector('[data-existing-card-field="assignment_letter"] .assignment-file-card');
            if (existingLetterCard) {
                const name = existingLetterCard.querySelector(".assignment-file-card__name")?.textContent?.trim() || "خطاب الإسناد";
                const url = existingLetterCard.querySelector("a.assignment-file-card__action")?.href;
                items.push({
                    name,
                    url: url || null,
                    metaLabel: "خطاب الإسناد (الحالي)",
                });
            }
        }

        if (count) count.textContent = String(items.length);

        if (!items.length) {
            list.innerHTML =
                '<p class="text-muted mb-0" data-empty>لم يتم إرفاق مستندات أساسية بعد.</p>';
            return;
        }

        list.innerHTML = items.map(renderFileCardHtml).join("");
    }

    function renderReviewFileList(containerId, countId, inputId, existingFieldKey) {
        const list = document.getElementById(containerId);
        const count = document.getElementById(countId);
        if (!list) return;

        const items = [];

        // 1. Existing cards preserved in edit mode
        const fieldKey = existingFieldKey || inputId;
        document.querySelectorAll(`[data-existing-card-field="${fieldKey}"] .assignment-file-card`).forEach((card) => {
            const name = card.querySelector(".assignment-file-card__name")?.textContent?.trim();
            const url = card.querySelector("a.assignment-file-card__action")?.href || card.querySelector("img.assignment-file-card__img")?.src;
            const meta = card.querySelector(".assignment-file-card__meta span")?.textContent?.trim() || "ملف محفوظ";
            if (name) {
                items.push({
                    name,
                    url: url || null,
                    metaLabel: meta,
                });
            }
        });

        // 2. Newly selected files from file input
        const input = document.getElementById(inputId);
        const files = Array.from(input?.files || []);
        files.forEach((file) => {
            items.push({
                name: file.name,
                file,
                metaLabel: "ملف جديد",
            });
        });

        // 3. Draft archived files
        const wrapper =
            input?.closest("[data-form-field]") || input?.parentElement;
        const archivedNames = Array.from(
            wrapper?.querySelectorAll("[data-archived-draft-files] span") || [],
        ).map((el) => el.textContent.trim());

        archivedNames.forEach((name) => {
            if (name && !items.some((i) => i.name === name)) {
                items.push({ name, metaLabel: "مسودة محفوظة" });
            }
        });

        if (count) count.textContent = String(items.length);

        if (!items.length) {
            list.innerHTML =
                '<p class="text-muted mb-0" data-empty>لم يتم إرفاق ملفات بعد.</p>';
            return;
        }

        list.innerHTML = items.map(renderFileCardHtml).join("");
    }

    function selectedText(name) {
        const select = form.elements.namedItem(name);
        return select?.selectedOptions?.[0]?.textContent?.trim() || "-";
    }

    function buildReview() {
        const value = (name) => form.elements.namedItem(name)?.value || "-";
        const fields = [
            ["رقم الإسناد", value("assignment_number")],
            ["اسم الإسناد", value("name")],
            ["تصنيف الإسناد", selectedText("category")],
            ["الجهة المسندة", selectedText("issuing_entity_id") !== "-" ? selectedText("issuing_entity_id") : value("issuing_entity")],
            ["المدينة", selectedText("city")],
            ["النطاق/الحي", selectedText("district") !== "-" ? selectedText("district") : value("district")],
            ["تاريخ البداية", typeof window.formatSystemDate === "function" ? window.formatSystemDate(value("start_date")) : value("start_date")],
            ["تاريخ النهاية", typeof window.formatSystemDate === "function" ? window.formatSystemDate(value("end_date")) : value("end_date")],
            ["وصف الإسناد", value("notes")],
        ];
        document.getElementById("review-basic").innerHTML =
            `<dl class="row mb-0">${fields.map(([label, content]) => `<dt class="col-md-3 mb-2">${label}</dt><dd class="col-md-9 mb-2">${escapeHtml(content)}</dd>`).join("")}</dl>`;
        renderPropertyCards(
            document.getElementById("review-properties"),
            false,
        );

        renderReviewPrimaryDocuments();
        renderReviewFileList(
            "review-general-documents",
            "review-general-documents-count",
            "general_documents",
            "general_documents",
        );
        renderReviewFileList(
            "review-additional-attachments",
            "review-additional-attachments-count",
            "additional_attachments",
            "additional_attachments",
        );
        const reviewName = document.getElementById("review-name");
        if (reviewName) reviewName.textContent = value("name");
        const reviewDesc = document.getElementById("review-description");
        if (reviewDesc) reviewDesc.textContent = value("notes");
    }

    form.addEventListener("change", buildReview);
    form.addEventListener("input", buildReview);
    form.addEventListener("file:changed", buildReview);
    form.addEventListener("file:removed", buildReview);
    form.addEventListener("file:removed-existing", buildReview);
    document.querySelectorAll("[data-edit-step]").forEach((button) =>
        button.addEventListener("click", () => {
            const wizard = document.getElementById("wizard-validation");
            wizard?.bsStepper?.to(Number(button.dataset.editStep));
        }),
    );

    const imageInput = form.elements.namedItem("assignment_image");
    imageInput?.addEventListener("change", () => {
        const file = imageInput.files?.[0];
        const reviewImage = document.getElementById("review-image");
        if (file && reviewImage) reviewImage.src = URL.createObjectURL(file);
    });



    // Guard against moving to next step without selecting at least 1 property
    const asgPropertiesStep = document.getElementById("assignment-properties-validation");
    if (asgPropertiesStep) {
        const nextBtn = asgPropertiesStep.querySelector("[data-wizard-next], .btn-next");
        if (nextBtn) {
            nextBtn.addEventListener("click", (e) => {
                if (selectedProperties.size === 0) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    const errorEl = document.getElementById("property_ids-error");
                    if (errorEl) {
                        errorEl.textContent = "يجب اختيار عقار واحد على الأقل للإسناد.";
                        errorEl.removeAttribute("hidden");
                        errorEl.classList.remove("hidden");
                        errorEl.style.display = "block";
                    }
                    const wrapper = form.querySelector('[data-form-field][data-field-name="property_ids"]');
                    if (wrapper) wrapper.classList.add("is-invalid");
                    if (window.toastr) {
                        toastr.error("يجب اختيار عقار واحد على الأقل للإسناد.");
                    }
                    return false;
                }
            }, true);
        }
    }

    form.addEventListener("wizardDraftRestored", (event) => {
        const restoreSelectedProperties = async () => {
            let ids = event.detail?.data?.property_ids || [];
            if (!Array.isArray(ids)) {
                ids = ids ? [ids] : [];
            }
            ids = ids.map(String).filter(Boolean);
            if (ids.length && form.dataset.selectedPropertiesUrl) {
                const url = new URL(
                    form.dataset.selectedPropertiesUrl,
                    window.location.origin,
                );
                ids.forEach((id) => url.searchParams.append("ids[]", id));
                const response = await fetch(url, {
                    headers: { Accept: "application/json" },
                    credentials: "same-origin",
                });
                if (response.ok) {
                    const payload = await response.json();
                    (payload.data || []).forEach((property) =>
                        selectedProperties.set(String(property.id), property),
                    );
                }
            }
            syncProperties();
            buildReview();
        };

        event.detail?.waitUntil?.(restoreSelectedProperties());
    });

    form.addEventListener("stepChanged", (e) => {
        if (e.detail?.stepIndex === 1) {
            syncProperties();
        }
    });

    // Guard against moving to next step without selecting at least 1 property
    const asgPropStepEl = document.getElementById("assignment-properties-validation");
    if (asgPropStepEl) {
        const nextBtn = asgPropStepEl.querySelector("[data-wizard-next], .btn-next");
        if (nextBtn) {
            nextBtn.addEventListener(
                "click",
                function (e) {
                    const inputs = form.querySelectorAll('input[name="property_ids[]"], input[name="property_ids"]');
                    const hasValid = Array.from(inputs).some(
                        (input) => input.value && String(input.value).trim() !== "" && String(input.value) !== "0"
                    );
                    if (!hasValid) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        const errorEl = document.getElementById("property_ids-error");
                        if (errorEl) {
                            errorEl.textContent = "يجب اختيار عقار واحد على الأقل للإسناد.";
                            errorEl.removeAttribute("hidden");
                            errorEl.classList.remove("hidden");
                            errorEl.style.display = "block";
                        }
                        return false;
                    }
                },
                true,
            );
        }
    }

    // Filter properties in table and clear mismatched selections when category changes
    const categorySelect = document.getElementById("assignment_category");
    if (categorySelect) {
        const handleCategoryChange = function () {
            const newCat = categorySelect.value;
            if (newCat) {
                let removedAny = false;
                selectedProperties.forEach((property, id) => {
                    if (property.classification && property.classification !== newCat && property.classification !== "other") {
                        selectedProperties.delete(id);
                        removedAny = true;
                    }
                });
                if (removedAny) {
                    syncProperties();
                }
            }
            if (window.jQuery && window.jQuery.fn.DataTable.isDataTable("#assignment-properties-table")) {
                window.jQuery("#assignment-properties-table").DataTable().ajax.reload(null, false);
            }
        };

        categorySelect.addEventListener("change", handleCategoryChange);
        if (window.jQuery) {
            window.jQuery(categorySelect).on("select2:select", handleCategoryChange);
        }
    }

    syncProperties();
    buildReview();
});
