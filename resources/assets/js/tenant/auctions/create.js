import { mountReportInputs } from "./report-inputs.js";

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
        propertyCards.querySelectorAll("[data-property-card]").forEach((card) => {
            const id = String(card.dataset.propertyCard);
            selectedProperties.set(id, {
                id,
                name:
                    card.querySelector("h6, .text-truncate")?.textContent.trim() ||
                    "",
                number: card.querySelector("code")?.textContent.trim() || "",
                type: card.querySelector(".badge")?.textContent.trim() || "",
                city:
                    card
                        .querySelector(".text-muted")
                        ?.textContent.replace(/[\n\r]+/g, " ")
                        .trim() || "",
                image: card.querySelector("img")?.src || propertyImageFallback,
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

    function updateAttachmentReviewCount() {
        const input = attachmentInput();
        const files = Array.from(input?.files || []);
        const countText = `${files.length} ${files.length === 1 ? "ملف" : "ملفات"}`;
        $("#review-attachments-count").text(countText);
    }

    function renderSelectedPropertyCards() {
        const cards = document.getElementById("selected-properties-cards");
        const count = document.getElementById("selected-properties-count");
        if (!cards || !count) return;
        const properties = [...selectedProperties.values()];
        count.textContent = String(properties.length);
        cards.innerHTML = properties.length
            ? properties
                  .map(
                      (property) => `
                <article class="assignment-property-card" data-property-card="${escapeHtml(property.id)}">
                    <img class="assignment-property-card__image" src="${escapeHtml(property.image || propertyImageFallback)}" alt="" onerror="this.onerror=null;this.src='${escapeHtml(propertyImageFallback)}';">
                    <div class="assignment-property-card__content">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <code class="copyable-code">${escapeHtml(property.number || "-")}</code>
                            ${property.type ? `<span class="badge bg-info">${escapeHtml(property.type)}</span>` : ""}
                        </div>
                        <h6 class="mb-1 text-body text-truncate" title="${escapeHtml(property.name)}">${escapeHtml(property.name)}</h6>
                        <p class="mb-0 text-muted small"><i class="ti ti-map-pin me-1"></i>${escapeHtml(property.city || "-")}</p>
                    </div>
                    <button type="button" class="btn btn-sm remove-property-card-btn" data-remove-auction-property="${escapeHtml(property.id)}" aria-label="إزالة ${escapeHtml(property.name)}"><i class="ti ti-x" aria-hidden="true"></i></button>
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

    // Dynamic Assignment Handling & Properties Loading
    function loadAssignmentProperties(
        assignmentId,
        preselectedIds = [],
        isInitialLoad = false,
    ) {
        if (!assignmentId) {
            $("#selected-assignment-card").addClass("d-none");
            $("#properties-container").html(
                '<tr><td colspan="9" class="text-center py-4 text-muted">يرجى اختيار الإسناد في الخطوة الأولى لعرض العقارات التابعة له</td></tr>',
            );
            $("#start_date").removeAttr("min").removeAttr("max");
            $("#end_date").removeAttr("min").removeAttr("max");
            calculateSelectedProperties();
            return;
        }

        const urlTemplate =
            form.dataset.assignmentPropertiesUrl ||
            "/assignments/:id/properties";
        const url = urlTemplate.replace(
            ":id",
            encodeURIComponent(assignmentId),
        );

        $.ajax({
            url: url,
            type: "GET",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },
            success: function (res) {
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
                    const asgStartDate = res.assignment.start_date || "-";
                    const asgEndDate = res.assignment.end_date || "-";
                    const asgDatesText =
                        res.assignment.start_date || res.assignment.end_date
                            ? `${asgStartDate} إلى ${asgEndDate}`
                            : "غير محددة";
                    $("#asg-summary-dates").text(asgDatesText);
                    $("#selected-assignment-card").removeClass("d-none");

                    const isEditForm = form.id === "auction-edit-form";

                    // Step 1: Link main city and category automatically matching assignment
                    if (!isEditForm || !$("#city").val()) {
                        if (res.assignment.city) {
                            $("#city")
                                .val(res.assignment.city)
                                .trigger("change");
                        }
                    }

                    if (!isEditForm || !$("#category").val()) {
                        if (res.assignment.category) {
                            $("#category")
                                .val(res.assignment.category)
                                .trigger("change");
                        }
                    }

                    // Auto-fill start_time: 00:00 and end_time: 23:59
                    if (!isEditForm || !$("#start_time").val()) {
                        $("#start_time")
                            .val("00:00")
                            .trigger("input")
                            .trigger("change");
                    }
                    if (!isEditForm || !$("#end_time").val()) {
                        $("#end_time")
                            .val("23:59")
                            .trigger("input")
                            .trigger("change");
                    }

                    // Auto-fill supervising entity
                    if (!isEditForm || !$("#supervising_entity").val()) {
                        if (res.assignment.issuing_entity) {
                            $("#supervising_entity")
                                .val(res.assignment.issuing_entity)
                                .trigger("input");
                        }
                    }

                    // Auto-fill dates and constrain min/max based on assignment
                    if (res.assignment.start_date) {
                        const startDateInput = $("#start_date");
                        if (!isEditForm || !startDateInput.val()) {
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
                        if (!isEditForm || !endDateInput.val()) {
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
                        (!isEditForm || !$("#expected_properties_count").val()) &&
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
                                    <td>${Number(prop.estimated_value || 0).toLocaleString()} ر.س</td>
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
                $("#properties-container").html(
                    '<tr><td colspan="9" class="text-center py-4 text-danger">حدث خطأ أثناء تحميل عقارات الإسناد.</td></tr>',
                );
            },
        });
    }

    $("#assignment_id").on("change", function () {
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
            loadAssignmentProperties(
                detailData.assignment_id,
                restoredPropertyIds,
            );
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
        selectedProperties.clear();

        $(".property-select-check:checked").each(function () {
            count++;
            totalArea += parseFloat($(this).data("area")) || 0;
            totalVal += parseFloat($(this).data("val")) || 0;
            selectedProperties.set(String(this.value), {
                id: String(this.value),
                number: this.dataset.number || "",
                name: this.dataset.name || "",
                type: this.dataset.type || "",
                city: this.dataset.city || "",
                image: this.dataset.image || propertyImageFallback,
                area: parseFloat(this.dataset.area) || 0,
                val: parseFloat(this.dataset.val) || 0,
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
        $("#selected-props-val").text(totalVal.toLocaleString() + " ر.س");

        // Review step indicators
        $("#review-selected-props-count").text(count + " عقار");
        $("#review-selected-props-area").text(
            totalArea.toLocaleString() + " م²",
        );
        $("#review-selected-props-val").text(totalVal.toLocaleString() + " ر.س");
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

        renderSelectedPropertyCards();
        syncReportInputs();

        const allCount = $(".property-select-check").length;
        if (allCount > 0 && count === allCount) {
            $("#select-all-properties").prop("checked", true);
        } else {
            $("#select-all-properties").prop("checked", false);
        }

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

    // Guard against moving to next step without selecting at least 1 property
    const stepProperties = document.getElementById("step-properties");
    if (stepProperties) {
        const nextBtn = stepProperties.querySelector(
            "[data-wizard-next], .btn-next",
        );
        if (nextBtn) {
            nextBtn.addEventListener(
                "click",
                function (e) {
                    if (selectedProperties.size === 0) {
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

        const startDate = $("#start_date").val();
        const startTime = $("#start_time").val();
        const endDate = $("#end_date").val();
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
        $("#review-platform").text($("#platform_name").val() || "-");
        $("#review-platform-url").text($("#platform_url").val() || "-");
        $("#review-notes").text($("#notes").val() || "-");

        // Step 4: Reports & Attachments
        const intro = form.elements.namedItem("marketing[introduction]")?.value;
        $("#review-report-introduction").text(intro || "—");

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

        // Step 5: Results & Boards
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

        // Step 6: Marketing Campaigns
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
        "#platform_name, #platform_url, #project_manager_name, #description, #notes, #expected_properties_count",
    ).on("input change", updateLivePreview);

    const retainedReportImageReview =
        $("#review-report-image").text().trim() || "صورة المثال الأصلية";
    $(form).on("change", 'input[name="report_image"]', function () {
        $("#review-report-image").text(
            this.files?.length ? "تم اختيار صورة" : retainedReportImageReview,
        );
    });

    $(form).on("change", 'input[name="cover_image"]', function () {
        $("#review-cover-image").text(
            this.files?.length ? "تم اختيار صورة" : "غير محددة",
        );
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

    // Dynamic campaign addition & Select2 initialization
    $("#add-campaign-btn").on("click", function () {
        setTimeout(() => {
            $(form)
                .find(".campaign-row select")
                .each(function () {
                    if (
                        !$(this).hasClass("select2-hidden-accessible") &&
                        window.jQuery &&
                        $.fn.select2
                    ) {
                        $(this).select2({
                            dir: "rtl",
                            width: "100%",
                        });
                    }
                });
            updateLivePreview();
        }, 50);
    });

    $(form).on("click", ".remove-campaign", function () {
        setTimeout(updateLivePreview, 50);
    });

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
});
