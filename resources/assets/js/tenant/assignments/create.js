"use strict";

document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("#assignment-create-form, #assignment-edit-form");
    if (!form) return;

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

    const categorySelect = form.querySelector('select[name="category"]');
    const categoryFilter = document.getElementById("assignment-category-filter");
    let categoryMapping = {};
    try {
        categoryMapping = JSON.parse(form.dataset.categoryMapping || "{}");
    } catch (_) {}

    function updateTypeFilterOptions(currentCategory) {
        const typeSelect = document.getElementById("assignment-property-type");
        if (!typeSelect) return;
        const allowed = categoryMapping[currentCategory] || [];
        Array.from(typeSelect.options).forEach((opt) => {
            if (!opt.value) return;
            const isAllowed = allowed.length === 0 || allowed.includes(opt.value);
            opt.disabled = !isAllowed;
            opt.hidden = !isAllowed;
        });
        if (typeSelect.value && allowed.length > 0 && !allowed.includes(typeSelect.value)) {
            $(typeSelect).val("").trigger("change");
        }
        if (window.jQuery && $.fn.select2 && $(typeSelect).hasClass("select2-hidden-accessible")) {
            $(typeSelect).select2({
                dir: "rtl",
                width: "100%",
            });
        }
    }

    function syncCategoryFilter() {
        if (!categorySelect) return;
        const currentCategory = categorySelect.value;
        if (categoryFilter && categoryFilter.value !== currentCategory) {
            categoryFilter.value = currentCategory;
            $(categoryFilter).trigger("change");
        }
        updateTypeFilterOptions(currentCategory);

        if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable("#assignment-properties-table")) {
            $("#assignment-properties-table").DataTable().ajax.reload();
        }
    }

    if (categorySelect) {
        $(categorySelect).on("change", syncCategoryFilter);
        setTimeout(() => updateTypeFilterOptions(categorySelect.value), 100);
    }

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

        form.dispatchEvent(new Event("change", { bubbles: true }));
    }

    function propertyFromCheckbox(checkbox) {
        return {
            id: String(checkbox.value),
            name: checkbox.dataset.name || "",
            number: checkbox.dataset.number || "",
            type: checkbox.dataset.type || "",
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

    function formatFileSize(bytes) {
        if (!bytes || bytes <= 0) return "";
        const units = ["B", "KB", "MB", "GB"];
        const index = Math.floor(Math.log(bytes) / Math.log(1024));
        return (
            Math.round((bytes / Math.pow(1024, index)) * 10) / 10 +
            " " +
            units[index]
        );
    }

    function renderFileCardHtml({
        name,
        file = null,
        url = null,
        metaLabel = "",
    }) {
        const fileName = name || file?.name || "ملف مرفق";
        const ext = (fileName.split(".").pop() || "").toLowerCase();
        const isImage =
            (file && file.type && file.type.startsWith("image/")) ||
            ["jpg", "jpeg", "png", "webp", "gif", "svg"].includes(ext) ||
            (typeof url === "string" && url.startsWith("data:image/"));

        let previewUrl = url;
        if (
            !previewUrl &&
            file &&
            isImage &&
            window.URL &&
            typeof window.URL.createObjectURL === "function"
        ) {
            try {
                previewUrl = URL.createObjectURL(file);
            } catch (e) {
                previewUrl = null;
            }
        }

        let typeCategory = "generic";
        let iconClass = "ti-file";
        let badgeColor = "secondary";
        let typeName = "مستند";

        if (isImage) {
            typeName = "صورة";
            badgeColor = "info";
        } else if (ext === "pdf") {
            typeCategory = "pdf";
            iconClass = "ti-file-type-pdf";
            badgeColor = "danger";
            typeName = "مستند PDF";
        } else if (["doc", "docx"].includes(ext)) {
            typeCategory = "word";
            iconClass = "ti-file-description";
            badgeColor = "primary";
            typeName = "مستند Word";
        } else if (["xls", "xlsx", "csv"].includes(ext)) {
            typeCategory = "excel";
            iconClass = "ti-file-spreadsheet";
            badgeColor = "success";
            typeName = "جدول بيانات";
        } else if (["zip", "rar", "7z", "tar", "gz"].includes(ext)) {
            typeCategory = "zip";
            iconClass = "ti-file-zip";
            badgeColor = "warning";
            typeName = "أرشيف مضغوط";
        }

        const actionMarkup = previewUrl
            ? `<a href="${escapeHtml(previewUrl)}" target="_blank" rel="noopener noreferrer" class="assignment-file-card__action" title="معاينة الملف" aria-label="معاينة"><i class="ti ti-eye"></i></a>`
            : "";

        const previewMarkup =
            isImage && previewUrl
                ? `<img src="${escapeHtml(previewUrl)}" alt="${escapeHtml(fileName)}" class="assignment-file-card__img" loading="lazy">
           <span class="assignment-file-card__type-pill">${escapeHtml((ext || "IMG").toUpperCase())}</span>`
                : `<div class="assignment-file-card__doc-wrap type-${typeCategory}">
             <div class="assignment-file-card__icon-circle">
               <i class="ti ${iconClass}"></i>
             </div>
             <span class="assignment-file-card__type-pill">${escapeHtml((ext || "FILE").toUpperCase())}</span>
           </div>`;

        const sizeFormatted = file?.size
            ? formatFileSize(file.size)
            : metaLabel || typeName;

        return `
      <article class="assignment-file-card" data-file-type="${escapeHtml(ext)}">
        <div class="assignment-file-card__preview">
          ${previewMarkup}
          ${actionMarkup}
        </div>
        <div class="assignment-file-card__info">
          <span class="assignment-file-card__name" title="${escapeHtml(fileName)}">${escapeHtml(fileName)}</span>
          <div class="assignment-file-card__meta">
            <span class="text-muted text-truncate me-1">${escapeHtml(sizeFormatted)}</span>
            <span class="badge bg-label-${badgeColor}">${escapeHtml((ext || "FILE").toUpperCase())}</span>
          </div>
        </div>
      </article>`;
    }

    function renderReviewPrimaryDocuments() {
        const list = document.getElementById("review-primary-documents");
        const count = document.getElementById("review-primary-documents-count");
        if (!list) return;

        const items = [];
        const imgInput = form.elements.namedItem("assignment_image");
        const letterInput = form.elements.namedItem("assignment_letter");

        if (imgInput?.files?.[0]) {
            items.push({
                name: imgInput.files[0].name,
                file: imgInput.files[0],
                metaLabel: "صورة الإسناد",
            });
        }
        if (letterInput?.files?.[0]) {
            items.push({
                name: letterInput.files[0].name,
                file: letterInput.files[0],
                metaLabel: "خطاب الإسناد",
            });
        }

        if (count) count.textContent = String(items.length);

        if (!items.length) {
            list.innerHTML =
                '<p class="text-muted mb-0" data-empty>لم يتم إرفاق مستندات أساسية بعد.</p>';
            return;
        }

        list.innerHTML = items.map(renderFileCardHtml).join("");
    }

    function renderReviewFileList(containerId, countId, inputId) {
        const list = document.getElementById(containerId);
        const count = document.getElementById(countId);
        if (!list) return;

        const input = document.getElementById(inputId);
        const files = Array.from(input?.files || []);
        const wrapper =
            input?.closest("[data-form-field]") || input?.parentElement;
        const archivedNames = Array.from(
            wrapper?.querySelectorAll("[data-archived-draft-files] span") || [],
        ).map((el) => el.textContent.trim());

        const items = [
            ...files.map((file) => ({ name: file.name, file })),
            ...archivedNames.map((name) => ({ name })),
        ];

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
            ["الجهة المسندة", value("issuing_entity")],
            ["المدينة", selectedText("city")],
            ["النطاق/الحي", value("district")],
            ["تاريخ البداية", value("start_date")],
            ["تاريخ النهاية", value("end_date")],
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
        );
        renderReviewFileList(
            "review-additional-attachments",
            "review-additional-attachments-count",
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

    // Sync category filter before entering properties step
    const asgBasicStep = document.getElementById("assignment-basic-validation");
    if (asgBasicStep) {
        const nextBtn = asgBasicStep.querySelector("[data-wizard-next], .btn-next");
        if (nextBtn) {
            nextBtn.addEventListener("click", () => {
                syncCategoryFilter();
            });
        }
    }

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

    form.addEventListener("wizardDraftRestored", async (event) => {
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
    });

    syncProperties();
    buildReview();
});
