import { mountReportInputs } from "./report-inputs.js";
import { renderFileCardHtml } from "../../shared/file-card.js";

("use strict");

document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(
        "#wizard-validation-form, #auction-edit-form",
    );
    if (!form) return;
    const reportInputs = mountReportInputs(form);

    let restoredPropertyIds = [];
    const selectedProperties = new Map();
    const propertyImageFallback =
        form.dataset.propertyImageFallback || "/static/brand/logos/logo.svg";

    const propertyInputs = document.getElementById("selected-property-inputs");
    const propertyCards = document.getElementById("selected-properties-cards");
    const propertyCount = document.getElementById("selected-properties-count");

    // Initialize selected properties from pre-rendered DOM cards (e.g. in Edit mode)
    if (propertyCards) {
        propertyCards
            .querySelectorAll("[data-property-card]")
            .forEach((card) => {
                const id = String(card.dataset.propertyCard);
                selectedProperties.set(id, {
                    id,
                    name:
                        card
                            .querySelector("h6, .text-truncate")
                            ?.textContent.trim() || "",
                    number:
                        card.querySelector("code")?.textContent.trim() || "",
                    type:
                        card.querySelector(".badge")?.textContent.trim() || "",
                    city:
                        card
                            .querySelector(".text-muted")
                            ?.textContent.replace(/[\n\r]+/g, " ")
                            .trim() || "",
                    image:
                        card.querySelector("img")?.src || propertyImageFallback,
                    area: 0,
                    val: 0,
                });
            });
    }

    // Also pick up any pre-rendered hidden inputs in Edit mode
    if (propertyInputs) {
        propertyInputs
            .querySelectorAll('input[name="property_ids[]"]')
            .forEach((input) => {
                const val = String(input.value);
                if (val && !selectedProperties.has(val)) {
                    selectedProperties.set(val, {
                        id: val,
                        name: "",
                        number: "",
                        type: "",
                        city: "",
                        image: propertyImageFallback,
                        area: 0,
                        val: 0,
                    });
                }
            });
    }

    function escapeHtml(value) {
        if (value === null || value === undefined) return "";
        const element = document.createElement("div");
        element.textContent = String(value);
        return element.innerHTML;
    }

    function attachmentInput() {
        return form.elements.namedItem("attachments[]");
    }

    // Card rendering imported from shared/file-card.js

    function renderReviewFiles() {
        // 1. Covers (Report image & Auction cover)
        const reviewCovers = document.getElementById("review-covers-documents");
        const reviewCoversCount = document.getElementById(
            "review-covers-documents-count",
        );
        if (reviewCovers) {
            const coverItems = [];
            const coverFields = [
                { name: "report_image", label: "غلاف التقرير" },
                { name: "cover_image", label: "غلاف المزاد" },
            ];

            coverFields.forEach(({ name, label }) => {
                const input = form.elements.namedItem(name);
                const file = input?.files?.[0];
                if (file) {
                    coverItems.push({
                        name: file.name,
                        file,
                        metaLabel: `${label} (جديد)`,
                    });
                } else {
                    const wrapper =
                        input?.closest("[data-file-upload-wrapper]") ||
                        input?.parentElement;
                    const existingCard = wrapper?.querySelector(
                        "[data-existing-file-card]",
                    );
                    const existingName =
                        existingCard
                            ?.querySelector(".file-item-name")
                            ?.textContent?.trim() ||
                        wrapper
                            ?.querySelector(".file-item-name")
                            ?.textContent?.trim();
                    const existingImg =
                        existingCard?.querySelector("img.file-item-preview")
                            ?.src ||
                        existingCard?.querySelector("a.btn-view-file")?.href ||
                        wrapper?.querySelector("img.file-item-preview")?.src;
                    if (existingName || existingImg) {
                        coverItems.push({
                            name: existingName || label,
                            url: existingImg || null,
                            metaLabel: label,
                        });
                    }
                }
            });

            if (reviewCoversCount) {
                reviewCoversCount.textContent = String(coverItems.length);
            }
            if (!coverItems.length) {
                reviewCovers.innerHTML =
                    '<p class="text-muted mb-0" data-empty>لم يتم إرفاق أغلفة بعد.</p>';
            } else {
                reviewCovers.innerHTML = coverItems
                    .map(renderFileCardHtml)
                    .join("");
            }
        }

        // 2. Additional Attachments
        const reviewAddDocs = document.getElementById(
            "review-additional-attachments",
        );
        const reviewAddCount = document.getElementById(
            "review-additional-attachments-count",
        );
        if (reviewAddDocs) {
            const addItems = [];
            const addDocsInput = form.elements.namedItem("attachments[]");

            // Existing attachments rendered inside the unified file-upload component in step 4
            addDocsInput
                ?.closest("[data-file-upload-wrapper]")
                ?.querySelectorAll("[data-existing-file-card]:not(.d-none)")
                .forEach((card) => {
                    const name = card
                        .querySelector(".file-item-name")
                        ?.textContent?.trim();
                    const url =
                        card.querySelector("a.btn-view-file")?.href ||
                        card.querySelector("img.file-item-preview")?.src;
                    const meta =
                        card
                            .querySelector(".file-item-size")
                            ?.textContent?.trim() || "مرفق محفوظ";
                    if (name) {
                        addItems.push({
                            name,
                            url: url || null,
                            metaLabel: meta,
                        });
                    }
                });

            // Newly selected files in attachments[]
            if (addDocsInput?.files?.length) {
                Array.from(addDocsInput.files).forEach((file) => {
                    addItems.push({
                        name: file.name,
                        file,
                        metaLabel: "مرفق إضافي",
                    });
                });
            }

            // Draft archived files
            const wrapper =
                addDocsInput?.closest("[data-form-field]") ||
                addDocsInput?.parentElement;
            const archivedNames = Array.from(
                wrapper?.querySelectorAll("[data-archived-draft-files] span") ||
                    [],
            ).map((el) => el.textContent.trim());
            archivedNames.forEach((name) => {
                if (name && !addItems.some((i) => i.name === name)) {
                    addItems.push({
                        name,
                        metaLabel: "مسودة محفوظة",
                    });
                }
            });

            if (reviewAddCount) {
                reviewAddCount.textContent = String(addItems.length);
            }
            const countText = `${addItems.length} ${addItems.length === 1 ? "ملف" : "ملفات"}`;
            $("#review-attachments-count").text(countText);

            if (!addItems.length) {
                reviewAddDocs.innerHTML =
                    '<p class="text-muted mb-0" data-empty>لم يتم إرفاق ملفات إضافية بعد.</p>';
            } else {
                reviewAddDocs.innerHTML = addItems
                    .map(renderFileCardHtml)
                    .join("");
            }
        }

        // 3. Optional: Billboard images
        const reviewBoard = document.getElementById("review-board-documents");
        const reviewBoardCount = document.getElementById(
            "review-board-documents-count",
        );
        const reviewBoardWrap = document.getElementById(
            "review-board-documents-wrapper",
        );
        if (reviewBoard) {
            const boardItems = [];
            form.querySelectorAll(
                'input[name^="board_images_before"], input[name^="board_images_after"]',
            ).forEach((input) => {
                if (input.files?.length) {
                    Array.from(input.files).forEach((file) => {
                        boardItems.push({
                            name: file.name,
                            file,
                            metaLabel: "لوحة ميدانية",
                        });
                    });
                }
            });
            document
                .querySelectorAll(
                    "#step-results-boards .assignment-file-card, [data-board-upload] [data-existing-file-card]",
                )
                .forEach((card) => {
                    const name = card
                        .querySelector(
                            ".assignment-file-card__name, .file-item-name",
                        )
                        ?.textContent?.trim();
                    const url =
                        card.querySelector("a.assignment-file-card__action")
                            ?.href || card.querySelector("img")?.src;
                    if (name) {
                        boardItems.push({
                            name,
                            url: url || null,
                            metaLabel: "لوحة محفوظة",
                        });
                    }
                });

            if (reviewBoardWrap) {
                reviewBoardWrap.classList.toggle(
                    "d-none",
                    boardItems.length === 0,
                );
            }
            if (reviewBoardCount) {
                reviewBoardCount.textContent = String(boardItems.length);
            }
            if (!boardItems.length) {
                reviewBoard.innerHTML =
                    '<p class="text-muted mb-0" data-empty>لم يتم إرفاق صور لوحات بعد.</p>';
            } else {
                reviewBoard.innerHTML = boardItems
                    .map(renderFileCardHtml)
                    .join("");
            }
        }

        // 4. Optional: Marketing Evidence
        const reviewEvidence = document.getElementById(
            "review-marketing-evidence",
        );
        const reviewEvidenceCount = document.getElementById(
            "review-marketing-evidence-count",
        );
        const reviewEvidenceWrap = document.getElementById(
            "review-marketing-evidence-wrapper",
        );
        if (reviewEvidence) {
            const evidenceItems = [];
            const evInput = form.elements.namedItem("marketing_evidence[]");
            if (evInput?.files?.length) {
                Array.from(evInput.files).forEach((file) => {
                    evidenceItems.push({
                        name: file.name,
                        file,
                        metaLabel: "دليل تسويقي",
                    });
                });
            }
            document
                .querySelectorAll(
                    "#step-campaigns .assignment-file-card, #step-campaigns [data-existing-file-card]",
                )
                .forEach((card) => {
                    const name = card
                        .querySelector(
                            ".assignment-file-card__name, .file-item-name",
                        )
                        ?.textContent?.trim();
                    const url =
                        card.querySelector("a.assignment-file-card__action")
                            ?.href || card.querySelector("img")?.src;
                    if (name) {
                        evidenceItems.push({
                            name,
                            url: url || null,
                            metaLabel: "دليل محفوظ",
                        });
                    }
                });

            if (reviewEvidenceWrap) {
                reviewEvidenceWrap.classList.toggle(
                    "d-none",
                    evidenceItems.length === 0,
                );
            }
            if (reviewEvidenceCount) {
                reviewEvidenceCount.textContent = String(evidenceItems.length);
            }
            if (!evidenceItems.length) {
                reviewEvidence.innerHTML =
                    '<p class="text-muted mb-0" data-empty>لم يتم إرفاق أدلة تسويقية بعد.</p>';
            } else {
                reviewEvidence.innerHTML = evidenceItems
                    .map(renderFileCardHtml)
                    .join("");
            }
        }
    }

    function updateAttachmentReviewCount() {
        const input = attachmentInput();
        const files = Array.from(input?.files || []);
        const countText = `${files.length} ${files.length === 1 ? "ملف" : "ملفات"}`;
        $("#review-attachments-count").text(countText);
        renderReviewFiles();
    }

    function checkPropertyIsComplete(prop) {
        if (!prop) return false;
        const propId = String(prop.id);
        const hiddenEl = form.querySelector(`[data-prop-hidden="${propId}"]`);
        const opening =
            prop.opening_value ??
            hiddenEl?.querySelector('[data-field="opening_value"]')?.value;
        const entry =
            prop.entry_amount ??
            hiddenEl?.querySelector('[data-field="entry_amount"]')?.value;
        const share =
            prop.ownership_share ??
            hiddenEl?.querySelector('[data-field="ownership_share"]')?.value;
        const disclosure =
            prop.disclosure ??
            hiddenEl?.querySelector('[data-field="disclosure"]')?.value;
        const dimensions =
            prop.board_dimensions ??
            hiddenEl?.querySelector('[data-field="board_dimensions"]')?.value;

        const hasValuation =
            Boolean(prop.valuation_file) ||
            Boolean(prop._valFiles && prop._valFiles.length > 0) ||
            Boolean(
                hiddenEl?.querySelector(
                    '[data-prop-files] input[name*="valuation"]',
                )?.files?.length > 0,
            );
        const hasBoard =
            (prop.board_before_files && prop.board_before_files.length > 0) ||
            Boolean(prop._boardFiles && prop._boardFiles.length > 0) ||
            Boolean(
                hiddenEl?.querySelector(
                    '[data-prop-files] input[name*="board_before"]',
                )?.files?.length > 0,
            );

        return Boolean(
            String(opening || "").trim() !== "" &&
                String(entry || "").trim() !== "" &&
                String(share || "").trim() !== "" &&
                String(disclosure || "").trim() !== "" &&
                String(dimensions || "").trim() !== "" &&
                hasValuation &&
                hasBoard,
        );
    }

    function updatePropertiesCompletionAlert() {
        const alertEl = document.getElementById("properties-completion-alert");
        const count = selectedProperties.size;
        if (count === 0) {
            alertEl?.classList.remove("d-flex");
            alertEl?.classList.add("d-none");
            return true;
        }

        const incomplete = [...selectedProperties.values()].filter(
            (p) => !checkPropertyIsComplete(p),
        );
        if (incomplete.length > 0) {
            alertEl?.classList.remove("d-none");
            alertEl?.classList.add("d-flex");
            return false;
        } else {
            alertEl?.classList.remove("d-flex");
            alertEl?.classList.add("d-none");
            return true;
        }
    }

    function renderSelectedPropertyCards() {
        const cards = document.getElementById("selected-properties-cards");
        const count = document.getElementById("selected-properties-count");
        if (cards && count) {
            const properties = [...selectedProperties.values()];
            count.textContent = String(properties.length);
            cards.innerHTML = properties.length
                ? properties
                      .map((property) => {
                          const isComplete = checkPropertyIsComplete(property);
                          property.is_complete = isComplete;
                          return `
                    <article class="assignment-property-card cursor-pointer border ${isComplete ? "border-success-subtle" : "border-warning-subtle"}" data-property-card="${escapeHtml(property.id)}" style="cursor: pointer;" title="انقر لتعديل واستكمال بيانات العقار للمزاد">
                        <img class="assignment-property-card__image" src="${escapeHtml(property.image || propertyImageFallback)}" alt="" onerror="this.onerror=null;this.src='${escapeHtml(propertyImageFallback)}';">
                        <div class="assignment-property-card__content" style="padding-left: 48px;">
                            <h6 class="mb-0 text-body text-truncate fw-bold" title="${escapeHtml(property.name)}">${escapeHtml(property.name)}</h6>
                            <div class="d-flex align-items-center gap-1 my-1">
                                <code class="copyable-code d-inline-flex align-items-center justify-content-center px-2 py-0 m-0" style="height: 22px; line-height: 1; font-size: 0.75rem; vertical-align: middle;">${escapeHtml(property.number || "-")}</code>
                                ${property.type ? `<span class="badge bg-info d-inline-flex align-items-center justify-content-center px-2 py-0 m-0" style="height: 22px; line-height: 1; font-size: 0.75rem; vertical-align: middle;">${escapeHtml(property.type)}</span>` : ""}
                            </div>
                            <p class="mb-0 text-muted small"><i class="ti ti-map-pin me-1"></i>${escapeHtml(property.city || "-")}</p>
                        </div>
                        <div class="assignment-property-card__actions" style="position: absolute; top: 6px; left: 6px; display: flex; align-items: center; gap: 4px; z-index: 2;">
                            ${
                                isComplete
                                    ? `<span class="badge bg-label-success p-0 d-inline-flex align-items-center justify-content-center rounded-circle" style="width:20px;height:20px;" title="البيانات مكتملة"><i class="ti ti-check" style="font-size: 11px;"></i></span>`
                                    : `<span class="badge bg-label-danger p-0 d-inline-flex align-items-center justify-content-center rounded-circle" style="width:20px;height:20px;" title="البيانات غير مكتملة (اضغط للإكمال)"><i class="ti ti-alert-triangle" style="font-size: 11px;"></i></span>`
                            }
                            <button type="button" class="btn btn-sm remove-property-card-btn position-static" data-remove-auction-property="${escapeHtml(property.id)}" aria-label="إزالة ${escapeHtml(property.name)}" title="إلغاء التحديد"><i class="ti ti-x" aria-hidden="true"></i></button>
                        </div>
                    </article>`;
                      })
                      .join("")
                : '<p class="text-muted mb-0" data-empty>لم يتم اختيار عقارات بعد.</p>';
        }
        updatePropertiesCompletionAlert();
        renderReviewPropertyCards();
    }

    function renderReviewPropertyCards() {
        const reviewCards =
            document.getElementById("review-properties") ||
            document.getElementById("review-selected-properties");
        if (!reviewCards) return;
        const properties = [...selectedProperties.values()];
        reviewCards.innerHTML = properties.length
            ? properties
                  .map(
                      (property) => `
                <article class="assignment-property-card" data-property-card="${escapeHtml(property.id)}">
                    <img class="assignment-property-card__image" src="${escapeHtml(property.image || propertyImageFallback)}" alt="" onerror="this.onerror=null;this.src='${escapeHtml(propertyImageFallback)}';">
                    <div class="assignment-property-card__content">
                        <h6 class="mb-0 text-body text-truncate fw-bold" title="${escapeHtml(property.name)}">${escapeHtml(property.name)}</h6>
                        <div class="d-flex align-items-center gap-1 my-1">
                            <code class="copyable-code d-inline-flex align-items-center justify-content-center px-2 py-0 m-0" style="height: 22px; line-height: 1; font-size: 0.75rem; vertical-align: middle;">${escapeHtml(property.number || "-")}</code>
                            ${property.type ? `<span class="badge bg-info d-inline-flex align-items-center justify-content-center px-2 py-0 m-0" style="height: 22px; line-height: 1; font-size: 0.75rem; vertical-align: middle;">${escapeHtml(property.type)}</span>` : ""}
                        </div>
                        <p class="mb-0 text-muted small"><i class="ti ti-map-pin me-1"></i>${escapeHtml(property.city || "-")}</p>
                    </div>
                </article>`,
                  )
                  .join("")
            : '<p class="text-muted mb-0" data-empty>لم يتم اختيار عقارات بعد.</p>';
    }

    function boardUpload(property, phase) {
        const templateId =
            phase === "before"
                ? "board-upload-before-template"
                : "board-upload-after-template";
        const template = document.getElementById(templateId);
        if (!template) {
            const id = escapeHtml(property.id);
            const label = phase === "before" ? "قبل الفك" : "بعد الفك";
            return `<div class="mb-3" data-board-upload="${phase}-${id}"><label class="form-label small">${escapeHtml(property.name)} — ${label}</label><input type="file" class="form-control" name="board_images_${phase}[${id}][]" accept="image/jpeg,image/png,image/webp" multiple data-form-control></div>`;
        }
        return template.innerHTML
            .replaceAll("__PROPERTY_ID__", escapeHtml(property.id))
            .replaceAll("__PROPERTY_NAME__", escapeHtml(property.name));
    }

    function syncReportInputs() {
        const selectedIds = new Set(selectedProperties.keys());
        const results = document.getElementById("property-results-container");
        if (results) {
            results
                .querySelectorAll("[data-property-result]")
                .forEach((card) => {
                    if (!selectedIds.has(String(card.dataset.propertyResult)))
                        card.remove();
                });
            selectedProperties.forEach((property) => {
                if (
                    !results.querySelector(
                        `[data-property-result="${CSS.escape(String(property.id))}"]`,
                    )
                ) {
                    results.append(reportInputs.propertyCard(property));
                }
            });
            results
                .querySelector("#results-empty-msg")
                ?.classList.toggle("d-none", selectedIds.size > 0);
        }

        reportInputs.restorePropertyResults();

        [
            ["before", "board-before-container"],
            ["after", "board-after-container"],
        ].forEach(([phase, containerId]) => {
            const container = document.getElementById(containerId);
            if (!container) return;
            container
                .querySelectorAll("[data-board-upload]")
                .forEach((block) => {
                    const propertyId = String(
                        block.dataset.boardUpload,
                    ).replace(`${phase}-`, "");
                    if (!selectedIds.has(propertyId)) block.remove();
                });
            selectedProperties.forEach((property) => {
                if (
                    !container.querySelector(
                        `[data-board-upload="${phase}-${CSS.escape(String(property.id))}"]`,
                    )
                ) {
                    container.insertAdjacentHTML(
                        "beforeend",
                        boardUpload(property, phase),
                    );
                }
            });
            const hint = container.querySelector("p.text-muted");
            if (hint) hint.classList.toggle("d-none", selectedIds.size > 0);
        });
    }

    function syncSelectValue(selector, targetVal) {
        if (!targetVal) return;
        const $select = $(selector);
        if (!$select.length) return;

        const target = String(targetVal).trim();
        let found = null;
        $select.find("option").each(function () {
            const value = String($(this).val() || "");
            const label = $(this).text().trim();
            if (
                value &&
                (value.trim().toLowerCase() === target.toLowerCase() ||
                    label === target)
            ) {
                found = value;
                return false;
            }
        });

        if (found === null && selector === "#city") {
            $select.append(new Option(target, target));
            found = target;
        }

        if (found !== null) {
            $select.val(found).trigger("change").trigger("change.select2");
        }
    }

    // Dynamic Assignment Handling & Properties Loading
    let assignmentRequestVersion = 0;

    function loadAssignmentProperties(
        assignmentId,
        preselectedIds = [],
        isInitialLoad = false,
    ) {
        const requestVersion = ++assignmentRequestVersion;
        if (!assignmentId) {
            $("#selected-assignment-card").addClass("d-none");
            $("#properties-container").html(
                '<tr><td colspan="9" class="text-center py-4 text-muted">يرجى اختيار الإسناد في الخطوة الأولى لعرض العقارات التابعة له</td></tr>',
            );
            $("#start_date").removeAttr("min").removeAttr("max");
            $("#end_date").removeAttr("min").removeAttr("max");
            calculateSelectedProperties();
            return Promise.resolve();
        }

        const urlTemplate =
            form.dataset.assignmentPropertiesUrl ||
            "/assignments/:id/properties";
        const url = urlTemplate.replace(
            ":id",
            encodeURIComponent(assignmentId),
        );

        return $.ajax({
            url: url,
            type: "GET",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },
            success: function (res) {
                if (requestVersion !== assignmentRequestVersion) return;
                if (res.success && res.assignment) {
                    $("#asg-summary-num").text(
                        res.assignment.assignment_number || "-",
                    );
                    $("#asg-summary-entity").text(
                        res.assignment.issuing_entity || "-",
                    );
                    $("#asg-summary-status").text(
                        res.assignment.status_label || "-",
                    );
                    const formatDisplay = (val) =>
                        typeof window.formatSystemDate === "function"
                            ? window.formatSystemDate(val)
                            : val || "-";
                    const asgStartDate = formatDisplay(
                        res.assignment.start_date,
                    );
                    const asgEndDate = formatDisplay(res.assignment.end_date);
                    const asgDatesText =
                        res.assignment.start_date || res.assignment.end_date
                            ? `${asgStartDate} إلى ${asgEndDate}`
                            : "غير محددة";
                    $("#asg-summary-dates").text(asgDatesText);
                    $("#selected-assignment-card").removeClass("d-none");

                    const preserveValues =
                        form.id === "auction-edit-form" || isInitialLoad;

                    // Step 1: Link main city, category, times, etc.
                    if (!preserveValues) {
                        // In create mode: unconditionally link city and category
                        if (res.assignment.city) {
                            syncSelectValue("#city", res.assignment.city);
                        }
                        if (res.assignment.category) {
                            syncSelectValue(
                                "#category",
                                res.assignment.category,
                            );
                        }
                        // Default start_time: 00:00 and end_time: 23:59
                        $("#start_time")
                            .val("00:00")
                            .trigger("input")
                            .trigger("change");
                        $("#end_time")
                            .val("23:59")
                            .trigger("input")
                            .trigger("change");
                    } else {
                        // In edit mode: only fill if empty
                        if (!$("#city").val() && res.assignment.city) {
                            syncSelectValue("#city", res.assignment.city);
                        }
                        if (!$("#category").val() && res.assignment.category) {
                            syncSelectValue(
                                "#category",
                                res.assignment.category,
                            );
                        }
                        if (!$("#start_time").val()) {
                            $("#start_time")
                                .val("00:00")
                                .trigger("input")
                                .trigger("change");
                        }
                        if (!$("#end_time").val()) {
                            $("#end_time")
                                .val("23:59")
                                .trigger("input")
                                .trigger("change");
                        }
                    }

                    if (!$("#type").val()) {
                        syncSelectValue("#type", "electronic");
                    }

                    // Auto-fill supervising entity
                    if (!preserveValues || !$("#supervising_entity").val()) {
                        if (res.assignment.issuing_entity) {
                            $("#supervising_entity")
                                .val(res.assignment.issuing_entity)
                                .trigger("input")
                                .trigger("change");
                        }
                    }

                    // Auto-fill dates and constrain min/max based on assignment
                    $("#start_date, #end_date")
                        .removeAttr("min")
                        .removeAttr("max");
                    if (!preserveValues) {
                        $("#start_date").val(res.assignment.start_date || "");
                        $("#end_date").val(res.assignment.end_date || "");
                    }
                    if (res.assignment.start_date) {
                        const startDateInput = $("#start_date");
                        if (!preserveValues || !startDateInput.val()) {
                            startDateInput.val(res.assignment.start_date);
                        }
                        startDateInput.attr("min", res.assignment.start_date);
                        if (res.assignment.end_date) {
                            startDateInput.attr("max", res.assignment.end_date);
                        }
                        startDateInput.trigger("input").trigger("change");
                    }
                    if (res.assignment.end_date) {
                        const endDateInput = $("#end_date");
                        if (!preserveValues || !endDateInput.val()) {
                            endDateInput.val(res.assignment.end_date);
                        }
                        endDateInput.attr("max", res.assignment.end_date);
                        if (res.assignment.start_date) {
                            endDateInput.attr("min", res.assignment.start_date);
                        }
                        endDateInput.trigger("input").trigger("change");
                    }

                    // Auto-fill default expected properties count
                    const propCount = res.properties
                        ? res.properties.length
                        : 0;
                    if (
                        (!preserveValues ||
                            !$("#expected_properties_count").val()) &&
                        propCount > 0
                    ) {
                        $("#expected_properties_count")
                            .val(propCount)
                            .trigger("input")
                            .trigger("change");
                    }

                    syncTimeConstraints();

                    $("#review-assignment-name").text(
                        (res.assignment.name || "") +
                            " (" +
                            (res.assignment.assignment_number || "") +
                            ")",
                    );
                    $("#review-supervising").text(
                        $("#supervising_entity").val() ||
                            res.assignment.issuing_entity ||
                            "-",
                    );

                    let html = "";
                    if (res.properties && res.properties.length > 0) {
                        res.properties.forEach(function (prop) {
                            const img =
                                prop.image_url ||
                                prop.image ||
                                propertyImageFallback;
                            const isSelectable = prop.is_selectable !== false;
                            const disabledAttr = isSelectable ? "" : "disabled";
                            const statusBadge = prop.is_sold
                                ? '<span class="badge bg-danger">تم البيع</span>'
                                : prop.is_in_active_auction
                                  ? '<span class="badge bg-warning">مدرج بمزاد نشط</span>'
                                  : '<span class="badge bg-success">متاح</span>';

                            html += `
                                <tr class="${isSelectable ? "" : "table-light opacity-75"}">
                                    <td class="text-center">
                                        <input type="checkbox" value="${escapeHtml(prop.id)}" class="form-check-input property-select-check" ${disabledAttr}
                                            data-area="${parseFloat(prop.area) || 0}" data-val="${parseFloat(prop.estimated_value) || 0}" data-number="${escapeHtml(prop.property_number)}" data-name="${escapeHtml(prop.name)}" data-type="${escapeHtml(prop.type_label || prop.type || "")}" data-city="${escapeHtml(prop.city || "")}" data-image="${escapeHtml(img)}">
                                    </td>
                                    <td><code>${escapeHtml(prop.property_number)}</code></td>
                                    <td class="text-center">
                                        <img src="${escapeHtml(img)}" class="rounded" style="width:36px;height:36px;object-fit:cover" alt="" onerror="this.onerror=null;this.src='${escapeHtml(propertyImageFallback)}';" />
                                    </td>
                                    <td>
                                        <span class="fw-medium">${escapeHtml(prop.name)}</span>
                                    </td>
                                    <td><span class="badge bg-label-info">${escapeHtml(prop.type_label || prop.type || "-")}</span></td>
                                    <td>${escapeHtml(prop.city || "")}${prop.district ? " - " + escapeHtml(prop.district) : ""}</td>
                                    <td>${Number(prop.area || 0).toLocaleString()} م²</td>
                                    <td>${Number(prop.estimated_value || 0).toLocaleString()} <span class="icon-saudi_riyal icon-saudi_riyal--dark ms-1"></span></td>
                                    <td class="text-center">${statusBadge}</td>
                                </tr>
                            `;
                        });
                    } else {
                        html =
                            '<tr><td colspan="9" class="text-center py-4 text-muted">لا توجد عقارات مسجلة لهذا الإسناد حالياً.</td></tr>';
                    }

                    $("#properties-container").html(html);

                    // Check preselected properties
                    const targetCheckIds =
                        preselectedIds.length > 0
                            ? preselectedIds
                            : restoredPropertyIds.length > 0
                              ? restoredPropertyIds
                              : [...selectedProperties.keys()];

                    if (targetCheckIds.length > 0) {
                        targetCheckIds.forEach(function (propertyId) {
                            $(
                                '.property-select-check[value="' +
                                    propertyId +
                                    '"]',
                            ).prop("checked", true);
                        });
                    }

                    calculateSelectedProperties();
                    updateLivePreview();
                }
            },
            error: function () {
                if (requestVersion !== assignmentRequestVersion) return;
                $("#properties-container").html(
                    '<tr><td colspan="9" class="text-center py-4 text-danger">حدث خطأ أثناء تحميل عقارات الإسناد.</td></tr>',
                );
            },
        });
    }

    $("#assignment_id").on("change", function () {
        if (form.inert) return;
        loadAssignmentProperties($(this).val());
    });

    // Wizard draft restored handler
    $(form).on("wizardDraftRestored", function (event) {
        const detailData =
            event.originalEvent?.detail?.data || event.detail?.data || {};
        reportInputs.restore(
            detailData,
            event.originalEvent?.detail?.files || event.detail?.files || {},
        );
        if (detailData.property_ids) {
            restoredPropertyIds = (
                Array.isArray(detailData.property_ids)
                    ? detailData.property_ids
                    : [detailData.property_ids]
            ).map(String);
        }

        if (detailData.assignment_id) {
            $("#assignment_id").val(detailData.assignment_id);
            const task = loadAssignmentProperties(
                detailData.assignment_id,
                restoredPropertyIds,
                true,
            );
            (event.originalEvent?.detail || event.detail)?.waitUntil?.(task);
        }

        updateLivePreview();
        updateAttachmentReviewCount();
        syncTimeConstraints();
    });

    // Properties selection calculation & synchronization
    function calculateSelectedProperties() {
        let count = 0;
        let totalArea = 0;
        let totalVal = 0;
        const oldSelected = new Map(selectedProperties);
        selectedProperties.clear();

        $(".property-select-check:checked").each(function () {
            const id = String(this.value);
            const existing =
                oldSelected.get(id) ||
                (window.existingAuctionPropertiesData &&
                    window.existingAuctionPropertiesData[id]) ||
                {};
            count++;
            totalArea += parseFloat($(this).data("area")) || 0;
            totalVal += parseFloat($(this).data("val")) || 0;
            selectedProperties.set(id, {
                id,
                number: this.dataset.number || existing.number || "",
                name: this.dataset.name || existing.name || "",
                type: this.dataset.type || existing.type || "",
                city: this.dataset.city || existing.city || "",
                image:
                    this.dataset.image ||
                    existing.image ||
                    propertyImageFallback,
                area: parseFloat(this.dataset.area) || existing.area || 0,
                val:
                    parseFloat(this.dataset.val) ||
                    existing.estimated_value ||
                    existing.val ||
                    0,
                estimated_value:
                    parseFloat(this.dataset.val) ||
                    existing.estimated_value ||
                    existing.val ||
                    0,
                opening_value: existing.opening_value ?? "",
                entry_amount: existing.entry_amount ?? "",
                ownership_share: existing.ownership_share ?? "100",
                disclosure: existing.disclosure ?? "",
                board_count: existing.board_count ?? "1",
                board_dimensions: existing.board_dimensions ?? "",
                valuation_file: existing.valuation_file ?? null,
                board_before_files: existing.board_before_files ?? [],
                is_complete: Boolean(existing.is_complete),
            });
        });

        // Sync hidden inputs for FormRequest & backend validation
        if (propertyInputs) {
            propertyInputs.innerHTML = [...selectedProperties.keys()]
                .map(
                    (id) =>
                        `<input type="hidden" name="property_ids[]" value="${id}" data-form-control>`,
                )
                .join("");
        }

        $("#selected-props-count").text(count);
        $("#selected-props-area").text(totalArea.toLocaleString() + " م²");
        $("#selected-props-val").html(
            totalVal.toLocaleString() +
                ' <span class="icon-saudi_riyal icon-saudi_riyal--dark ms-1"></span>',
        );

        // Clean up hidden data for deselected properties
        const currentIds = new Set(selectedProperties.keys());
        const dataContainer = document.getElementById(
            "auction-properties-data-container",
        );
        if (dataContainer) {
            dataContainer
                .querySelectorAll("[data-prop-hidden]")
                .forEach((el) => {
                    if (!currentIds.has(String(el.dataset.propHidden))) {
                        el.remove();
                    }
                });
        }

        // Review step indicators
        $("#review-selected-props-count").text(count + " عقار");
        $("#review-selected-props-area").text(
            totalArea.toLocaleString() + " م²",
        );
        $("#review-selected-props-val").html(
            totalVal.toLocaleString() +
                ' <span class="icon-saudi_riyal icon-saudi_riyal--dark ms-1"></span>',
        );
        const names = [...selectedProperties.values()]
            .map((p) => p.name)
            .filter(Boolean)
            .join("، ");
        $("#review-selected-props-names").text(names || "-");

        // Error clear when at least 1 property is selected
        if (count > 0) {
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
            if (wrapper) wrapper.classList.remove("is-invalid");
        }

        const stepProps = document.getElementById("step-properties");
        const propNextBtn = stepProps?.querySelector(
            "[data-wizard-next], .btn-next:not(.btn-submit)",
        );
        if (propNextBtn) {
            const propertyInputsList = Array.from(
                form.querySelectorAll(
                    'input[name="property_ids[]"], input[name="property_ids"]',
                ),
            ).filter(
                (input) =>
                    input.value &&
                    String(input.value).trim() !== "" &&
                    String(input.value) !== "0",
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

        renderSelectedPropertyCards();
        syncReportInputs();

        const allCount = $(".property-select-check").length;
        if (allCount > 0 && count === allCount) {
            $("#select-all-properties").prop("checked", true);
        } else {
            $("#select-all-properties").prop("checked", false);
        }

        window.SharedFormValidation?.syncAllPropertyStepButtons?.(form);
        form.dispatchEvent(new Event("change", { bubbles: true }));
    }

    // Select all properties checkbox
    $(document).on("change", "#select-all-properties", function () {
        $(".property-select-check:not(:disabled)").prop(
            "checked",
            $(this).prop("checked"),
        );
        calculateSelectedProperties();
    });

    $(document).on("change", ".property-select-check", function () {
        calculateSelectedProperties();
    });

    $(form).on("click", "[data-remove-auction-property]", function (e) {
        e.preventDefault();
        const propertyId = String(this.dataset.removeAuctionProperty);
        $(`.property-select-check[value="${CSS.escape(propertyId)}"]`).prop(
            "checked",
            false,
        );
        selectedProperties.delete(propertyId);
        calculateSelectedProperties();
    });

    let currentEditingPropertyId = null;
    let propModalInstance = null;

    function getPropModal() {
        const modalEl = document.getElementById("auctionPropertyModal");
        if (!modalEl) return null;
        if (!propModalInstance && window.bootstrap?.Modal) {
            propModalInstance = new window.bootstrap.Modal(modalEl);
        }
        return propModalInstance;
    }

    function clearModalErrors() {
        const modalEl = document.getElementById("auctionPropertyModal");
        if (!modalEl) return;
        modalEl.querySelectorAll(".is-invalid").forEach((el) => {
            el.classList.remove("is-invalid");
        });
        modalEl
            .querySelectorAll("[data-form-error], .invalid-feedback")
            .forEach((el) => {
                el.textContent = "";
                el.hidden = true;
                el.style.display = "none";
            });
    }

    function showModalFieldError(fieldId, message) {
        const input = document.getElementById(fieldId);
        if (!input) return;
        input.classList.add("is-invalid");
        const inputGroup = input.closest(".input-group");
        if (inputGroup) {
            inputGroup.classList.add("is-invalid");
        }
        const fieldWrapper =
            input.closest("[data-form-field]") || input.parentElement;
        if (fieldWrapper) {
            fieldWrapper.classList.add("is-invalid");
            let errorEl = fieldWrapper.querySelector(
                "[data-form-error], .invalid-feedback",
            );
            if (!errorEl) {
                errorEl = document.createElement("div");
                errorEl.className = "invalid-feedback d-block";
                errorEl.setAttribute("data-form-error", "");
                errorEl.setAttribute("role", "alert");
                fieldWrapper.appendChild(errorEl);
            }
            errorEl.textContent = message;
            errorEl.hidden = false;
            errorEl.style.display = "block";
        }
    }

    function showModalFileError(inputId, message) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const uploadWrapper = input.closest("[data-file-upload-wrapper]");
        const fieldWrapper =
            input.closest("[data-form-field]") ||
            uploadWrapper ||
            input.parentElement;
        if (uploadWrapper) {
            uploadWrapper.classList.add("is-invalid");
            uploadWrapper
                .querySelector(".file-upload-area")
                ?.classList.add("is-invalid");
        }
        input.classList.add("is-invalid");
        if (fieldWrapper) {
            fieldWrapper.classList.add("is-invalid");
            let errorEl = fieldWrapper.querySelector(
                "[data-form-error], .invalid-feedback",
            );
            if (!errorEl) {
                errorEl = document.createElement("div");
                errorEl.className = "invalid-feedback d-block";
                errorEl.setAttribute("data-form-error", "");
                errorEl.setAttribute("role", "alert");
                fieldWrapper.appendChild(errorEl);
            }
            errorEl.textContent = message;
            errorEl.hidden = false;
            errorEl.style.display = "block";
        }
    }

    function openAuctionPropertyModal(propertyId) {
        const prop = selectedProperties.get(String(propertyId));
        if (!prop) return;

        currentEditingPropertyId = String(propertyId);
        clearModalErrors();

        $("#auctionPropertyModalTitle").text(prop.name || "-");
        $("#auctionPropertyModalNumber").text(prop.number || "-");
        $("#auctionPropertyModalType").text(prop.type || "-");
        $("#modal_auction_prop_id").val(prop.id);

        const estVal = Number(prop.estimated_value || 0);
        $("#modal_auction_estimated_value").val(
            estVal ? Number(estVal).toLocaleString() : "0",
        );

        $("#modal_auction_opening_value").val(prop.opening_value ?? "");
        $("#modal_auction_entry_amount").val(prop.entry_amount ?? "");
        $("#modal_auction_ownership_share").val(prop.ownership_share ?? "100");
        $("#modal_auction_board_count").val(prop.board_count ?? "1");
        $("#modal_auction_board_dimensions").val(prop.board_dimensions ?? "");
        $("#modal_auction_disclosure").val(prop.disclosure ?? "");

        // Reset file upload controls so no previous property files persist
        const valInput = document.getElementById("modal_file_valuation");
        const boardInput = document.getElementById("modal_file_board_before");
        const valCtrl = window.SharedFileUpload
            ? window.SharedFileUpload.getController(valInput)
            : null;
        const boardCtrl = window.SharedFileUpload
            ? window.SharedFileUpload.getController(boardInput)
            : null;

        if (valCtrl) {
            valCtrl.clear();
            valCtrl.clearExistingFiles();
        } else if (valInput) {
            valInput.value = "";
            valInput
                .closest("[data-file-upload-wrapper]")
                ?.querySelectorAll("[data-file-preview-container] .file-item")
                ?.forEach((el) => el.remove());
        }

        if (boardCtrl) {
            boardCtrl.clear();
            boardCtrl.clearExistingFiles();
        } else if (boardInput) {
            boardInput.value = "";
            boardInput
                .closest("[data-file-upload-wrapper]")
                ?.querySelectorAll("[data-file-preview-container] .file-item")
                ?.forEach((el) => el.remove());
        }

        // Restore THIS property's files only:
        if (prop._valFiles && prop._valFiles.length > 0) {
            valCtrl?.addFiles(prop._valFiles);
        } else if (prop.valuation_file) {
            const vf = prop.valuation_file;
            const fileData =
                typeof vf === "string"
                    ? { name: vf.split("/").pop(), url: vf }
                    : {
                          id: vf.id,
                          name: vf.name || "نموذج التقييم",
                          url: vf.url || vf.path,
                      };
            valCtrl?.setExistingFiles([fileData]);
        }

        if (prop._boardFiles && prop._boardFiles.length > 0) {
            boardCtrl?.addFiles(prop._boardFiles);
        } else if (
            prop.board_before_files &&
            prop.board_before_files.length > 0
        ) {
            const list = prop.board_before_files.map((bf) => {
                if (typeof bf === "string") {
                    return { name: bf.split("/").pop(), url: bf };
                }
                return {
                    id: bf.id,
                    name: bf.name || "صورة لوحة",
                    url: bf.url || bf.path,
                };
            });
            boardCtrl?.setExistingFiles(list);
        }

        getPropModal()?.show();
    }

    function syncPropertyHiddenData(prop, valFiles = null, boardFiles = null) {
        const dataContainer = document.getElementById(
            "auction-properties-data-container",
        );
        if (!dataContainer) return;
        const propId = String(prop.id);
        let block = dataContainer.querySelector(
            `[data-prop-hidden="${propId}"]`,
        );
        if (!block) {
            block = document.createElement("div");
            block.className = "property-hidden-data";
            block.dataset.propHidden = propId;
            block.innerHTML = `
                <input type="hidden" name="properties_data[${propId}][opening_value]" data-field="opening_value">
                <input type="hidden" name="properties_data[${propId}][entry_amount]" data-field="entry_amount">
                <input type="hidden" name="properties_data[${propId}][estimated_value]" data-field="estimated_value">
                <input type="hidden" name="properties_data[${propId}][ownership_share]" data-field="ownership_share">
                <input type="hidden" name="properties_data[${propId}][disclosure]" data-field="disclosure">
                <input type="hidden" name="properties_data[${propId}][board_count]" data-field="board_count">
                <input type="hidden" name="properties_data[${propId}][board_dimensions]" data-field="board_dimensions">
                <div class="property-file-inputs" data-prop-files="${propId}"></div>
            `;
            dataContainer.appendChild(block);
        }

        const setVal = (field, val) => {
            let input = block.querySelector(`[data-field="${field}"]`);
            if (!input) {
                input = document.createElement("input");
                input.type = "hidden";
                input.name = `properties_data[${propId}][${field}]`;
                input.dataset.field = field;
                block.appendChild(input);
            }
            input.value = val ?? "";
        };

        setVal("opening_value", prop.opening_value);
        setVal("entry_amount", prop.entry_amount);
        setVal("estimated_value", prop.estimated_value);
        setVal("ownership_share", prop.ownership_share);
        setVal("disclosure", prop.disclosure);
        setVal("board_count", prop.board_count);
        setVal("board_dimensions", prop.board_dimensions);

        const filesWrapper = block.querySelector(
            `[data-prop-files="${propId}"]`,
        );
        if (filesWrapper) {
            if (
                valFiles &&
                valFiles.length > 0 &&
                typeof DataTransfer !== "undefined"
            ) {
                let existingInput = filesWrapper.querySelector(
                    `input[name="properties_data[${propId}][valuation]"]`,
                );
                if (!existingInput) {
                    existingInput = document.createElement("input");
                    existingInput.type = "file";
                    existingInput.name = `properties_data[${propId}][valuation]`;
                    existingInput.style.display = "none";
                    filesWrapper.appendChild(existingInput);
                }
                const dt = new DataTransfer();
                dt.items.add(valFiles[0]);
                existingInput.files = dt.files;
                prop.valuation_file = valFiles[0].name;
            }

            if (
                boardFiles &&
                boardFiles.length > 0 &&
                typeof DataTransfer !== "undefined"
            ) {
                let existingInput = filesWrapper.querySelector(
                    `input[name="properties_data[${propId}][board_before][]"]`,
                );
                if (!existingInput) {
                    existingInput = document.createElement("input");
                    existingInput.type = "file";
                    existingInput.name = `properties_data[${propId}][board_before][]`;
                    existingInput.multiple = true;
                    existingInput.style.display = "none";
                    filesWrapper.appendChild(existingInput);
                }
                const dt = new DataTransfer();
                for (let i = 0; i < boardFiles.length; i++) {
                    dt.items.add(boardFiles[i]);
                }
                existingInput.files = dt.files;
                prop.board_before_files = Array.from(boardFiles).map(
                    (f) => f.name,
                );
            }
        }
    }

    function savePropertyModalData(propertyId) {
        const prop = selectedProperties.get(String(propertyId));
        if (!prop) return false;

        clearModalErrors();

        const parseCleanNum = (val) => {
            const clean = String(val ?? "")
                .replace(/,/g, "")
                .trim();
            return clean === "" ? NaN : Number(clean);
        };

        const openingRaw = $("#modal_auction_opening_value").val().trim();
        const openingNum = parseCleanNum(openingRaw);

        const entryRaw = $("#modal_auction_entry_amount").val().trim();
        const entryNum = parseCleanNum(entryRaw);

        const estValNum = parseCleanNum(prop.estimated_value);

        const ownShareRaw = $("#modal_auction_ownership_share").val().trim();
        const ownShareNum = parseCleanNum(ownShareRaw);

        const bCountRaw = $("#modal_auction_board_count").val().trim();
        const bCountNum = parseCleanNum(bCountRaw);

        const bDim = $("#modal_auction_board_dimensions").val().trim();
        const disc = $("#modal_auction_disclosure").val().trim();

        const valInput = document.getElementById("modal_file_valuation");
        const boardInput = document.getElementById("modal_file_board_before");
        const valCtrl = window.SharedFileUpload
            ? window.SharedFileUpload.getController(valInput)
            : null;
        const boardCtrl = window.SharedFileUpload
            ? window.SharedFileUpload.getController(boardInput)
            : null;

        const valWrapper = valInput?.closest("[data-file-upload-wrapper]");
        const boardWrapper = boardInput?.closest("[data-file-upload-wrapper]");

        let hasErrors = false;

        // 1. Validate opening_value
        if (!openingRaw) {
            showModalFieldError(
                "modal_auction_opening_value",
                "قيمة افتتاح المزاد مطلوبة.",
            );
            hasErrors = true;
        } else if (isNaN(openingNum) || openingNum < 0) {
            showModalFieldError(
                "modal_auction_opening_value",
                "قيمة افتتاح المزاد يجب أن تكون رقماً صالحاً.",
            );
            hasErrors = true;
        } else if (
            !isNaN(estValNum) &&
            estValNum > 0 &&
            openingNum > estValNum
        ) {
            showModalFieldError(
                "modal_auction_opening_value",
                `قيمة افتتاح المزاد لا يمكن أن تتجاوز القيمة التقديرية (${estValNum.toLocaleString()} ر.س).`,
            );
            hasErrors = true;
        }

        // 2. Validate entry_amount
        if (!entryRaw) {
            showModalFieldError(
                "modal_auction_entry_amount",
                "مبلغ الدخول مطلوب.",
            );
            hasErrors = true;
        } else if (isNaN(entryNum) || entryNum < 0) {
            showModalFieldError(
                "modal_auction_entry_amount",
                "مبلغ الدخول يجب أن يكون رقماً صالحاً.",
            );
            hasErrors = true;
        } else if (
            !isNaN(openingNum) &&
            openingNum >= 0 &&
            entryNum > openingNum
        ) {
            showModalFieldError(
                "modal_auction_entry_amount",
                "مبلغ الدخول لا يمكن أن يكون أكبر من قيمة افتتاح المزاد.",
            );
            hasErrors = true;
        }

        // 3. Validate ownership_share
        if (!ownShareRaw) {
            showModalFieldError(
                "modal_auction_ownership_share",
                "نسبة الملكية المعروضة مطلوبة.",
            );
            hasErrors = true;
        } else if (
            isNaN(ownShareNum) ||
            ownShareNum <= 0 ||
            ownShareNum > 100
        ) {
            showModalFieldError(
                "modal_auction_ownership_share",
                "نسبة الملكية المعروضة يجب أن تكون بين 0.01% و 100%.",
            );
            hasErrors = true;
        }

        // 4. Validate board_count
        if (!bCountRaw) {
            showModalFieldError(
                "modal_auction_board_count",
                "عدد اللوحات مطلوب.",
            );
            hasErrors = true;
        } else if (isNaN(bCountNum) || bCountNum < 1) {
            showModalFieldError(
                "modal_auction_board_count",
                "عدد اللوحات يجب أن يكون 1 على الأقل.",
            );
            hasErrors = true;
        }

        // 5. Validate board_dimensions
        if (!bDim) {
            showModalFieldError(
                "modal_auction_board_dimensions",
                "مقاس اللوحات مطلوب.",
            );
            hasErrors = true;
        }

        // 6. Validate disclosure
        if (!disc) {
            showModalFieldError(
                "modal_auction_disclosure",
                "الإفصاح الخاص بالعقار مطلوب.",
            );
            hasErrors = true;
        }

        // 7. Validate valuation file
        const remainingValCards =
            valWrapper?.querySelectorAll("[data-existing-file-card]") || [];
        const newChosenValFiles = valCtrl
            ? valCtrl.getFiles()
            : valInput?.files
              ? Array.from(valInput.files)
              : [];
        const hasValuation =
            remainingValCards.length > 0 ||
            newChosenValFiles.length > 0 ||
            Boolean(prop._valFiles && prop._valFiles.length > 0);

        if (!hasValuation) {
            showModalFileError(
                "modal_file_valuation",
                "يرجى إرفاق نموذج التقييم العقاري المعتمد.",
            );
            hasErrors = true;
        }

        // 8. Validate board before files
        const remainingBoardCards =
            boardWrapper?.querySelectorAll("[data-existing-file-card]") || [];
        const newChosenBoardFiles = boardCtrl
            ? boardCtrl.getFiles()
            : boardInput?.files
              ? Array.from(boardInput.files)
              : [];
        const hasBoard =
            remainingBoardCards.length > 0 ||
            newChosenBoardFiles.length > 0 ||
            Boolean(prop._boardFiles && prop._boardFiles.length > 0);

        if (!hasBoard) {
            showModalFileError(
                "modal_file_board_before",
                "يرجى إرفاق صورة واحدة على الأقل لتركيب اللوحات.",
            );
            hasErrors = true;
        }

        if (hasErrors) {
            if (window.toastr) {
                window.toastr.error(
                    "يرجى تصحيح الأخطاء الموضحة في بيانات العقار للمتابعة.",
                );
            }
            return false;
        }

        prop.opening_value = openingRaw.replace(/,/g, "");
        prop.entry_amount = entryRaw.replace(/,/g, "");
        prop.ownership_share = ownShareRaw;
        prop.board_count = bCountRaw;
        prop.board_dimensions = bDim;
        prop.disclosure = disc;

        if (newChosenValFiles.length > 0) {
            prop._valFiles = newChosenValFiles;
        } else if (remainingValCards.length === 0) {
            prop.valuation_file = null;
            prop._valFiles = null;
        }

        if (newChosenBoardFiles.length > 0) {
            prop._boardFiles = newChosenBoardFiles;
        } else if (remainingBoardCards.length === 0) {
            prop.board_before_files = [];
            prop._boardFiles = null;
        }

        prop.is_complete = true;

        syncPropertyHiddenData(prop, prop._valFiles, prop._boardFiles);

        // Reset file upload controllers after saving so modal state is clean
        valCtrl?.clear();
        valCtrl?.clearExistingFiles();
        boardCtrl?.clear();
        boardCtrl?.clearExistingFiles();

        renderSelectedPropertyCards();
        updatePropertiesCompletionAlert();
        updateLivePreview();

        return true;
    }

    // Modal input error clearing on user edit
    $("#auctionPropertyModal").on(
        "input change",
        "input, select, textarea",
        function () {
            this.classList.remove("is-invalid");
            this.closest(".input-group")?.classList.remove("is-invalid");
            const wrapper =
                this.closest("[data-form-field]") || this.parentElement;
            if (wrapper) {
                wrapper.classList.remove("is-invalid");
                const err = wrapper.querySelector(
                    "[data-form-error], .invalid-feedback",
                );
                if (err) {
                    err.textContent = "";
                    err.hidden = true;
                    err.style.display = "none";
                }
            }
        },
    );

    $("#auctionPropertyModal").on(
        "file:changed file:removed file:removed-existing",
        function (e) {
            const wrapper = e.target.closest("[data-file-upload-wrapper]");
            const fieldWrapper =
                e.target.closest("[data-form-field]") || wrapper;
            if (wrapper) {
                wrapper.classList.remove("is-invalid");
                wrapper
                    .querySelector(".file-upload-area")
                    ?.classList.remove("is-invalid");
            }
            if (fieldWrapper) {
                fieldWrapper.classList.remove("is-invalid");
                const err = fieldWrapper.querySelector(
                    "[data-form-error], .invalid-feedback",
                );
                if (err) {
                    err.textContent = "";
                    err.hidden = true;
                    err.style.display = "none";
                }
            }
        },
    );

    // Card click opens the unified property modal
    $(document).on(
        "click",
        "#selected-properties-cards .assignment-property-card",
        function (e) {
            if (
                e.target.closest(
                    "[data-remove-auction-property], .remove-property-card-btn",
                )
            )
                return;
            const propId = this.dataset.propertyCard;
            if (propId) {
                openAuctionPropertyModal(propId);
            }
        },
    );

    // Save button inside modal
    $("#btn-save-auction-prop-modal").on("click", function () {
        if (!currentEditingPropertyId) return;
        const saved = savePropertyModalData(currentEditingPropertyId);
        if (saved) {
            getPropModal()?.hide();
            if (window.toastr) {
                window.toastr.success("تم حفظ بيانات العقار بنجاح.");
            }
        }
    });

    // Modal hidden cleanup to ensure no cross-property file leaks
    document
        .getElementById("auctionPropertyModal")
        ?.addEventListener("hidden.bs.modal", function () {
            clearModalErrors();
            const valInput = document.getElementById("modal_file_valuation");
            const boardInput = document.getElementById(
                "modal_file_board_before",
            );
            const valCtrl = window.SharedFileUpload
                ? window.SharedFileUpload.getController(valInput)
                : null;
            const boardCtrl = window.SharedFileUpload
                ? window.SharedFileUpload.getController(boardInput)
                : null;
            valCtrl?.clear();
            valCtrl?.clearExistingFiles();
            boardCtrl?.clear();
            boardCtrl?.clearExistingFiles();
            currentEditingPropertyId = null;
        });

    // Guard against moving to next step without selecting at least 1 property and completing its data
    const stepProperties = document.getElementById("step-properties");
    if (stepProperties) {
        const nextBtn = stepProperties.querySelector(
            "[data-wizard-next], .btn-next",
        );
        if (nextBtn) {
            nextBtn.addEventListener(
                "click",
                function (e) {
                    const inputs = form.querySelectorAll(
                        'input[name="property_ids[]"], input[name="property_ids"]',
                    );
                    const hasValid = Array.from(inputs).some(
                        (input) =>
                            input.value &&
                            String(input.value).trim() !== "" &&
                            String(input.value) !== "0",
                    );
                    if (!hasValid || selectedProperties.size === 0) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        const errorEl =
                            document.getElementById("property_ids-error");
                        if (errorEl) {
                            errorEl.textContent =
                                "يجب اختيار عقار واحد على الأقل للمزاد.";
                            errorEl.removeAttribute("hidden");
                            errorEl.classList.remove("hidden");
                            errorEl.style.display = "block";
                        }
                        const wrapper = form.querySelector(
                            '[data-form-field][data-field-name="property_ids"]',
                        );
                        if (wrapper) wrapper.classList.add("is-invalid");
                        if (window.toastr) {
                            window.toastr.error(
                                "يجب اختيار عقار واحد على الأقل للمزاد.",
                            );
                        }
                        return false;
                    }

                    // Check completeness
                    const incomplete = [...selectedProperties.values()].filter(
                        (p) => !checkPropertyIsComplete(p),
                    );
                    if (incomplete.length > 0) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();
                        updatePropertiesCompletionAlert();
                        if (window.toastr) {
                            window.toastr.error(
                                "برجاء استكمال بيانات العقارات للمتابعة (انقر على بطاقات العقارات لإكمال بياناتها المطلوبة).",
                            );
                        }
                        const alertEl = document.getElementById(
                            "properties-completion-alert",
                        );
                        if (alertEl) {
                            alertEl.scrollIntoView({
                                behavior: "smooth",
                                block: "center",
                            });
                        }
                        return false;
                    }
                },
                true,
            );
        }
    }

    // Real-time live preview of all 7 steps
    function updateLivePreview() {
        // Step 1: Basic Info
        const nameVal = $("#name").val();
        $("#preview-auction-name").text(nameVal || "اسم المزاد");
        $("#review-name").text(nameVal || "-");

        const asgText = $("#assignment_id option:selected").text();
        if (asgText && $("#assignment_id").val()) {
            $("#review-assignment-name").text(asgText);
        }

        const supervising = $("#supervising_entity").val();
        $("#review-supervising").text(supervising || "-");

        const typeText = $("#type option:selected").text();
        $("#review-type").text(typeText || "-");

        const categoryText = $("#category option:selected").text();
        $("#preview-auction-category").text(categoryText || "التصنيف");
        $("#review-category").text(categoryText || "-");

        const cityVal = $("#city").val();
        $("#preview-auction-city").text(cityVal || "المدينة");
        $("#review-city").text(cityVal || "-");

        const formatDisplay = (val) =>
            typeof window.formatSystemDate === "function"
                ? window.formatSystemDate(val, "")
                : val || "";
        const startDate = formatDisplay($("#start_date").val());
        const startTime = $("#start_time").val();
        const endDate = formatDisplay($("#end_date").val());
        const endTime = $("#end_time").val();

        $("#preview-auction-date").text(startDate || "تاريخ الانطلاق");
        $("#review-start-datetime").text(
            startDate ? `${startDate} ${startTime || ""}`.trim() : "-",
        );
        $("#review-end-datetime").text(
            endDate ? `${endDate} ${endTime || ""}`.trim() : "-",
        );

        $("#review-expected-properties").text(
            $("#expected_properties_count").val() || "-",
        );
        $("#review-description").text($("#description").val() || "-");

        // Step 2: Platform & Project Management
        $("#review-project-manager").text(
            $("#project_manager_name").val() || "-",
        );
        $("#review-platform").text(
            $("#platform_entity_id").val()
                ? $("#platform_entity_id option:selected").text()
                : "-",
        );
        $("#review-platform-url").text($("#platform_url").val() || "-");
        $("#review-notes").text($("#notes").val() || "-");

        // Step 3: Properties Review Cards
        renderReviewPropertyCards();

        // Step 4: Marketing live preview
        const cityContextSelect = document.getElementById(
            "marketing_city_context",
        );
        if (cityContextSelect) {
            const selectedRegions = Array.from(
                cityContextSelect.selectedOptions,
            )
                .map((opt) => opt.textContent.trim())
                .filter(Boolean);
            $("#review-city-context").text(
                selectedRegions.length ? selectedRegions.join("، ") : "—",
            );
        }
        $("#review-planning-methodology").text(
            form.elements.namedItem("marketing[planning_methodology]")?.value ||
                "—",
        );
        $("#review-brochure-url").text(
            form.elements.namedItem("marketing[brochure_url]")?.value || "—",
        );
        $("#review-board-url").text(
            form.elements.namedItem("marketing[board_url]")?.value || "—",
        );
        const brokerNames = Array.from(
            form.querySelectorAll(
                '#auction-brokers-list [data-auction-broker-row] input[name$="[name]"]',
            ),
        )
            .map((input) => input.value.trim())
            .filter(Boolean);
        $("#review-brokers-summary").text(
            brokerNames.length ? brokerNames.join("، ") : "—",
        );

        // Step 5: Reports & Attachments
        const intro = form.elements.namedItem("marketing[introduction]")?.value;
        $("#review-report-introduction").text(intro || "—");
        const summary = form.elements.namedItem(
            "marketing[report_summary]",
        )?.value;
        $("#review-report-summary").text(summary || "—");

        const phone = form.elements.namedItem(
            "marketing[contacts][phone]",
        )?.value;
        $("#review-phone").text(phone || "-");

        const unifiedPhone = form.elements.namedItem(
            "marketing[contacts][unified_phone]",
        )?.value;
        $("#review-unified-phone").text(unifiedPhone || "-");

        const social = form.elements.namedItem(
            "marketing[contacts][social]",
        )?.value;
        $("#review-social").text(social || "-");

        const whatsappDate = form.elements.namedItem(
            "marketing[whatsapp_date]",
        )?.value;
        $("#review-whatsapp-date").text(whatsappDate || "-");

        renderReviewFiles();

        /*
        // Step 5: Results & Boards (معطل مؤقتاً)
        const soldCount = form.querySelectorAll(
            'select[name$="[status]"] option[value="sold"]:checked, select[name*="[status]"] option[value="sold"]:checked',
        ).length;
        const unsoldCount = form.querySelectorAll(
            'select[name$="[status]"] option[value="unsold"]:checked, select[name*="[status]"] option[value="unsold"]:checked',
        ).length;
        if (soldCount > 0 || unsoldCount > 0) {
            $("#review-results-summary").text(
                `${soldCount} مباع / ${unsoldCount} لم يُبع`,
            );
        } else {
            $("#review-results-summary").text("قيد الانتظار");
        }

        let beforeBoardFiles = 0;
        form.querySelectorAll(
            'input[name^="board_images_before"]',
        ).forEach((input) => {
            beforeBoardFiles += input.files?.length || 0;
        });
        if (beforeBoardFiles > 0) {
            $("#review-board-before-count").text(
                `${beforeBoardFiles} صور جديدة`,
            );
        }

        let afterBoardFiles = 0;
        form.querySelectorAll(
            'input[name^="board_images_after"]',
        ).forEach((input) => {
            afterBoardFiles += input.files?.length || 0;
        });
        if (afterBoardFiles > 0) {
            $("#review-board-after-count").text(`${afterBoardFiles} صور جديدة`);
        }

        // Step 6: Marketing Campaigns (معطل مؤقتاً)
        const campaignRows = form.querySelectorAll(".campaign-row");
        $("#review-campaigns-count").text(`${campaignRows.length} حملة`);

        const platforms = [];
        campaignRows.forEach((row) => {
            const select = row.querySelector('select[name$="[platform]"]');
            if (select && select.value) {
                const text =
                    select.options[select.selectedIndex]?.text || select.value;
                if (!platforms.includes(text)) {
                    platforms.push(text);
                }
            }
        });
        $("#review-campaigns-platforms").text(
            platforms.length > 0 ? platforms.join("، ") : "—",
        );

        const evidenceInput = form.elements.namedItem("marketing_evidence[]");
        const evidenceCount = evidenceInput?.files?.length || 0;
        if (evidenceCount > 0) {
            $("#review-evidence-count").text(`${evidenceCount} ملف جديد`);
        }
        */
    }

    function syncTimeConstraints() {
        const startDate = $("#start_date").val();
        const endDate = $("#end_date").val();
        const startTime = $("#start_time").val();
        const endTimeInput = document.getElementById("end_time");

        if (!endTimeInput) return;

        if (startDate && endDate && startDate === endDate && startTime) {
            endTimeInput.min = startTime;
        } else {
            endTimeInput.removeAttribute("min");
        }
    }

    // Live preview event listeners
    $("#name").on("input", updateLivePreview);
    $("#city").on("change", updateLivePreview);
    $("#category").on("change", updateLivePreview);
    $("#start_date, #end_date").on("change input", function () {
        updateLivePreview();
        syncTimeConstraints();
    });
    $("#start_time, #end_time").on("change input", function () {
        updateLivePreview();
        syncTimeConstraints();
    });
    $("#type").on("change", updateLivePreview);
    $("#supervising_entity").on("input", updateLivePreview);
    $(
        "#platform_entity_id, #platform_url, #project_manager_name, #description, #notes, #expected_properties_count",
    ).on("input change", updateLivePreview);

    const retainedReportImageReview =
        $("#review-report-image").text().trim() || "صورة المثال الأصلية";
    $(form).on("change", 'input[name="report_image"]', function () {
        $("#review-report-image").text(
            this.files?.length ? "تم اختيار صورة" : retainedReportImageReview,
        );
        renderReviewFiles();
    });

    $(form).on("change", 'input[name="cover_image"]', function () {
        $("#review-cover-image").text(
            this.files?.length ? "تم اختيار صورة" : "غير محددة",
        );
        renderReviewFiles();
    });

    $(form).on("change", 'input[name="attachments[]"]', function () {
        updateAttachmentReviewCount();
    });

    // Step 4 & 5 & 6 input events for live preview
    $(form).on(
        "input change",
        'input[name^="marketing"], textarea[name^="marketing"], select[name^="marketing"], input[name^="board_images"], input[name="marketing_evidence[]"], select[name^="property_results"]',
        updateLivePreview,
    );

    // Dynamic Brokers Repeater in Step 4 (same pattern as Property Owners)
    const brokersCard = form.querySelector("[data-auction-brokers-card]");
    const brokersList = document.getElementById("auction-brokers-list");
    const brokerTemplate = document.getElementById(
        "auction-broker-row-template",
    );
    const addBrokerBtn = document.getElementById("btn-add-auction-broker");

    function renumberAuctionBrokers() {
        if (!brokersList) return;
        const rows = brokersList.querySelectorAll("[data-auction-broker-row]");
        rows.forEach((row, index) => {
            const title = row.querySelector(".broker-number");
            if (title) {
                title.textContent = `الوسيط #${index + 1}`;
            }
        });
        let emptyEl = document.getElementById("auction-brokers-empty");
        if (rows.length === 0) {
            if (!emptyEl) {
                emptyEl = document.createElement("p");
                emptyEl.id = "auction-brokers-empty";
                emptyEl.className = "text-muted mb-0 text-center py-2";
                emptyEl.textContent =
                    'لم تتم إضافة وسطاء بعد. انقر على "إضافة وسيط" لإضافة بيانات وعقد الوسيط.';
                brokersList.appendChild(emptyEl);
            }
        } else if (emptyEl) {
            emptyEl.remove();
        }
    }

    if (addBrokerBtn && brokersCard && brokersList && brokerTemplate) {
        addBrokerBtn.addEventListener("click", () => {
            const nextIndex = Number(brokersCard.dataset.nextIndex || "0");
            brokersCard.dataset.nextIndex = String(nextIndex + 1);
            const key = `broker-${Date.now()}-${nextIndex}`;
            const currentCount =
                brokersList.querySelectorAll("[data-auction-broker-row]")
                    .length + 1;
            const html = brokerTemplate.innerHTML
                .replaceAll("__INDEX__", String(nextIndex))
                .replaceAll("__KEY__", key)
                .replaceAll("__NUM__", String(currentCount));
            const temp = document.createElement("div");
            temp.innerHTML = html.trim();
            const row = temp.firstElementChild;
            if (row) {
                brokersList.appendChild(row);
                window.FileUpload?.initAll?.(row);
                renumberAuctionBrokers();
                updateLivePreview();
            }
        });

        brokersList.addEventListener("click", (event) => {
            const removeBtn = event.target.closest(
                ".btn-remove-auction-broker",
            );
            if (!removeBtn) return;
            removeBtn.closest("[data-auction-broker-row]")?.remove();
            renumberAuctionBrokers();
            updateLivePreview();
        });

        brokersList.addEventListener("file:removed-existing", (event) => {
            const row = event.target.closest("[data-auction-broker-row]");
            const evidenceIdInput = row?.querySelector(
                "[data-broker-evidence-id]",
            );
            if (evidenceIdInput) {
                evidenceIdInput.value = "";
            }
        });
    }

    // Dynamic Campaign Channels Multi-Select & Tabs in Step 4
    const channelsSelect = document.getElementById("campaign-channels-select");
    const campaignTabsWrapper = document.getElementById(
        "campaign-tabs-wrapper",
    );
    const campaignTabsEmpty = document.getElementById("campaign-tabs-empty");

    function syncCampaignChannelsUi() {
        const selected = Array.from(channelsSelect?.selectedOptions || []).map(
            (o) => o.value,
        );
        if (!campaignTabsWrapper || !campaignTabsEmpty) return;

        if (selected.length === 0) {
            campaignTabsWrapper.classList.add("d-none");
            campaignTabsEmpty.classList.remove("d-none");
        } else {
            campaignTabsWrapper.classList.remove("d-none");
            campaignTabsEmpty.classList.add("d-none");

            document
                .querySelectorAll(".campaign-channel-tab")
                .forEach((tab) => {
                    const ch = tab.dataset.channel;
                    const isSel = selected.includes(ch);
                    tab.classList.toggle("d-none", !isSel);
                });

            document
                .querySelectorAll(".campaign-channel-pane")
                .forEach((pane) => {
                    const ch = pane.dataset.channel;
                    const isSel = selected.includes(ch);
                    if (!isSel) {
                        pane.classList.remove("show", "active");
                    }
                });

            // Ensure an active visible tab
            const activeTab = document.querySelector(
                ".campaign-channel-tab:not(.d-none) .nav-link.active",
            );
            if (!activeTab) {
                const firstTabBtn = document.querySelector(
                    ".campaign-channel-tab:not(.d-none) .nav-link",
                );
                if (firstTabBtn) {
                    if (window.bootstrap?.Tab) {
                        new window.bootstrap.Tab(firstTabBtn).show();
                    } else {
                        $(firstTabBtn).tab("show");
                    }
                }
            }
        }
    }

    if (channelsSelect) {
        $(channelsSelect).on(
            "change change.select2 select2:select select2:unselect",
            function () {
                syncCampaignChannelsUi();
                updateLivePreview();
            },
        );
        syncCampaignChannelsUi();
    }

    // Quick jump step buttons in Step 7 Review
    document.querySelectorAll("[data-edit-step]").forEach((button) => {
        button.addEventListener("click", () => {
            const target = Number(button.dataset.editStep);
            if (!target) return;
            const stepper =
                window.auctionStepper ||
                window.stepper ||
                form.closest("[data-form-stepper]")?.bsStepper;
            if (stepper) {
                stepper.to(target);
            }
        });
    });

    // Initial sync
    updateLivePreview();
    updateAttachmentReviewCount();
    syncReportInputs();
    syncTimeConstraints();

    // On initial page load: if assignment_id already has a value, load properties
    const initialAssignmentId = $("#assignment_id").val();
    if (initialAssignmentId) {
        loadAssignmentProperties(
            initialAssignmentId,
            [...selectedProperties.keys()],
            true,
        );
    }

    form.addEventListener("change", updateLivePreview);
    form.addEventListener("input", updateLivePreview);
    form.addEventListener("file:changed", updateLivePreview);
    form.addEventListener("file:removed", updateLivePreview);
    form.addEventListener("file:removed-existing", updateLivePreview);
    form.addEventListener("stepChanged", (e) => {
        updateLivePreview();
        if (e.detail?.stepIndex === 2) {
            calculateSelectedProperties();
        }
    });

    calculateSelectedProperties();
});
