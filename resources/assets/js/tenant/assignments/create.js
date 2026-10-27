"use strict";

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("assignment-create-form");
    if (!form) return;

    const selectedProperties = new Map();
    const propertyInputs = document.getElementById("selected-property-inputs");
    const propertyCards = document.getElementById("selected-properties-cards");
    const propertyCount = document.getElementById("selected-properties-count");
    const propertyImageFallback =
        form.dataset.propertyImageFallback || "/static/brand/logos/logo.svg";
    const fileInputs = [
        {
            input: document.getElementById("general_documents"),
            list: document.getElementById("general-documents-cards"),
            count: document.getElementById("general-documents-count"),
            reviewList: document.getElementById("review-general-documents"),
            reviewCount: document.getElementById(
                "review-general-documents-count",
            ),
        },
        {
            input: document.getElementById("additional_attachments"),
            list: document.getElementById("additional-attachments-cards"),
            count: document.getElementById("additional-attachments-count"),
            reviewList: document.getElementById(
                "review-additional-attachments",
            ),
            reviewCount: document.getElementById(
                "review-additional-attachments-count",
            ),
        },
    ];
    const accumulatedFiles = new Map();
    const restoredDraftFiles = new Map();

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
        ${removable ? `<button type="button" class="btn btn-sm btn-icon btn-danger" data-remove-property="${property.id}" aria-label="إزالة ${escapeHtml(property.name)}"><i class="ti ti-x"></i></button>` : ""}
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

    function fileKey(file) {
        return `${file.name}:${file.size}:${file.lastModified}`;
    }

    function renderFileCards(target, entries, removable = false) {
        if (!target) return;
        if (!entries.length) {
            target.innerHTML =
                '<p class="text-muted mb-0" data-empty>لم يتم إرفاق ملفات بعد.</p>';
            return;
        }

        target.innerHTML = entries
            .map(
                (file) => `
      <article class="assignment-file-card">
        <i class="${file.restored ? "ti ti-file-check text-success" : "ti ti-file text-primary"} fs-4"></i>
        <span class="assignment-file-card__name" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</span>
        ${removable ? `<button type="button" class="btn btn-sm btn-icon btn-danger" ${file.restored ? `data-remove-draft-file="${escapeHtml(file.key)}"` : `data-remove-file="${escapeHtml(file.key)}"`} data-input-id="${file.inputId}" aria-label="إزالة ${escapeHtml(file.name)}"><i class="ti ti-x"></i></button>` : ""}
      </article>`,
            )
            .join("");
    }

    function currentFiles(config) {
        const files = accumulatedFiles.get(config.input.id) || new Map();
        const restored = restoredDraftFiles.get(config.input.id) || new Map();
        return [
            ...[...files.entries()].map(([key, file]) => ({
                key,
                name: file.name,
                inputId: config.input.id,
                restored: false,
            })),
            ...[...restored.values()].map((file) => ({
                key: file.key,
                name: file.name,
                inputId: config.input.id,
                restored: true,
            })),
        ];
    }

    function syncFileInput(config) {
        const files = accumulatedFiles.get(config.input.id) || new Map();
        const transfer = new DataTransfer();
        files.forEach((file) => transfer.items.add(file));
        config.input.files = transfer.files;
        const filesForDisplay = currentFiles(config);
        config.count.textContent = String(filesForDisplay.length);
        config.reviewCount.textContent = String(filesForDisplay.length);
        renderFileCards(config.list, filesForDisplay, true);
        renderFileCards(config.reviewList, filesForDisplay);
        config.input.dispatchEvent(new Event("input", { bubbles: true }));
        buildReview();
    }

    fileInputs.forEach((config) => {
        if (!config.input) return;
        accumulatedFiles.set(config.input.id, new Map());
        config.input.addEventListener("change", () => {
            const files = accumulatedFiles.get(config.input.id);
            [...config.input.files].forEach((file) =>
                files.set(fileKey(file), file),
            );
            syncFileInput(config);
        });
    });

    document.addEventListener("click", (event) => {
        const button = event.target.closest("[data-remove-file]");
        if (!button) return;
        const config = fileInputs.find(
            (item) => item.input?.id === button.dataset.inputId,
        );
        if (!config) return;
        accumulatedFiles
            .get(config.input.id)
            ?.delete(button.dataset.removeFile);
        syncFileInput(config);
    });

    document.addEventListener("click", async (event) => {
        const button = event.target.closest("[data-remove-draft-file]");
        if (!button) return;
        const config = fileInputs.find(
            (item) => item.input?.id === button.dataset.inputId,
        );
        if (!config || !form.dataset.draftFileUrl) return;
        button.disabled = true;
        const response = await fetch(form.dataset.draftFileUrl, {
            method: "DELETE",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content || "",
            },
            body: JSON.stringify({
                resource_key: form.dataset.draftResourceKey,
                file_key: button.dataset.removeDraftFile,
            }),
        });
        if (!response.ok) {
            button.disabled = false;
            window.toastr?.error?.("تعذر حذف الملف من المسودة.");
            return;
        }
        restoredDraftFiles
            .get(config.input.id)
            ?.delete(button.dataset.removeDraftFile);
        syncFileInput(config);
    });

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

        fileInputs.forEach((config) => {
            const files = currentFiles(config);
            config.reviewCount.textContent = String(files.length);
            renderFileCards(config.reviewList, files);
        });
        const reviewName = document.getElementById("review-name");
        if (reviewName) reviewName.textContent = value("name");
        const reviewDesc = document.getElementById("review-description");
        if (reviewDesc) reviewDesc.textContent = value("notes");
    }

    form.addEventListener("change", buildReview);
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
        if (file && reviewImage)
            reviewImage.src = URL.createObjectURL(file);
    });

    form.addEventListener("wizardDraftRestored", async (event) => {
        const ids = event.detail?.data?.property_ids || [];
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
        const draftFiles = event.detail?.files || {};
        ["general_documents", "additional_attachments"].forEach((inputId) => {
            const entries = Object.values(draftFiles[inputId] || {}).filter(
                (file) => file?.key && file?.name,
            );
            restoredDraftFiles.set(
                inputId,
                new Map(entries.map((file) => [file.key, file])),
            );
            const config = fileInputs.find(
                (item) => item.input?.id === inputId,
            );
            if (config) syncFileInput(config);
        });
        const singleDraftFiles = [
            draftFiles.assignment_image,
            draftFiles.assignment_letter,
        ].filter((file) => file?.name);
        singleDraftFiles.forEach((file) => {
            const field =
                file.key === "assignment_image"
                    ? form.elements.namedItem("assignment_image")
                    : form.elements.namedItem("assignment_letter");
            const wrapper = field?.closest("[data-form-field]");
            if (wrapper && !wrapper.querySelector("[data-restored-file]")) {
                const note = document.createElement("div");
                note.dataset.restoredFile = "true";
                note.className = "small text-success mt-2";
                note.textContent = `الملف المحفوظ في المسودة: ${file.name}`;
                wrapper.append(note);
            }
        });
        syncProperties();
        buildReview();
    });

    buildReview();
});
