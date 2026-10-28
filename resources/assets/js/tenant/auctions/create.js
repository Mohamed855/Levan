import { mountReportInputs } from "./report-inputs.js";

("use strict");

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("wizard-validation-form");
    if (!form) return;
    const reportInputs = mountReportInputs(form);

    let restoredPropertyIds = [];
    const selectedProperties = new Map();
    const propertyImageFallback =
        form.dataset.propertyImageFallback || "/static/brand/logos/logo.svg";

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
        const files = Array.from(attachmentInput()?.files || []);
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
                <article class="assignment-property-card">
                    <img class="assignment-property-card__image" src="${escapeHtml(property.image || propertyImageFallback)}" alt="" onerror="this.onerror=null;this.src='${escapeHtml(propertyImageFallback)}';">
                    <div class="assignment-property-card__content">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <code class="copyable-code">${escapeHtml(property.number || "-")}</code>
                            ${property.type ? `<span class="badge bg-info">${escapeHtml(property.type)}</span>` : ""}
                        </div>
                        <h6 class="mb-1 text-body text-truncate" title="${escapeHtml(property.name)}">${escapeHtml(property.name)}</h6>
                        <p class="mb-0 text-muted small"><i class="ti ti-map-pin me-1"></i>${escapeHtml(property.city || "-")}</p>
                    </div>
                    <button type="button" class="btn btn-sm" data-remove-auction-property="${escapeHtml(property.id)}" aria-label="إزالة ${escapeHtml(property.name)}"><i class="ti ti-x" aria-hidden="true"></i></button>
                </article>`,
                  )
                  .join("")
            : '<p class="text-muted mb-0" data-empty>لم يتم اختيار عقارات بعد.</p>';
    }

    function boardUpload(property, phase) {
        const id = escapeHtml(property.id);
        const label = phase === "before" ? "قبل الفك" : "بعد الفك";
        return `<div class="mb-3" data-board-upload="${phase}-${id}"><label class="form-label small">${escapeHtml(property.name)} — ${label}</label><input type="file" class="form-control" name="board_images_${phase}[${id}][]" accept="image/jpeg,image/png,image/webp" multiple data-form-control></div>`;
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
            $("#assignment_id").val(detailData.assignment_id).trigger("change");
        }

        updateLivePreview();
        updateAttachmentReviewCount();
        syncTimeConstraints();
    });

    function updateLivePreview() {
        $("#review-marketing-counts").text(
            `${form.querySelectorAll(".campaign-row").length} حملة / ${form.querySelectorAll(".link-row").length} رابط`,
        );
        $("#review-report-introduction").text(
            form.elements.namedItem("marketing[introduction]")?.value ||
                "غير مضافة",
        );
        $("#review-results-narrative").text(
            form.elements.namedItem("marketing[results_narrative]")?.value ||
                "غير مضافة",
        );
        const nameVal = $("#name").val();
        $("#preview-auction-name").text(nameVal || "اسم المزاد");
        $("#review-name").text(nameVal || "-");

        const cityVal = $("#city").val();
        $("#preview-auction-city").text(cityVal || "المدينة");
        $("#review-city").text(cityVal || "-");

        const categoryText = $("#category option:selected").text();
        $("#preview-auction-category").text(categoryText || "التصنيف");
        $("#review-category").text(categoryText || "-");

        const startDate = $("#start_date").val();
        const endDate = $("#end_date").val();
        $("#preview-auction-date").text(startDate || "تاريخ الانطلاق");
        if (startDate || endDate) {
            $("#review-dates").text(
                (startDate || "-") + " إلى " + (endDate || "-"),
            );
        } else {
            $("#review-dates").text("-");
        }

        const typeText = $("#type option:selected").text();
        $("#review-type").text(typeText || "-");

        const asgText = $("#assignment_id option:selected").text();
        if (asgText && $("#assignment_id").val()) {
            $("#review-assignment-name").text(asgText);
        }

        const supervising = $("#supervising_entity").val();
        $("#review-supervising").text(supervising || "-");
        $("#review-platform").text($("#platform_name").val() || "-");
        $("#review-project-manager").text(
            $("#project_manager_name").val() || "-",
        );
        $("#review-description").text($("#description").val() || "-");
        $("#review-expected-properties").text(
            $("#expected_properties_count").val() || "-",
        );
        $("#review-brochure-template").text(
            $("#brochure_template_id option:selected").text() || "-",
        );
        $("#review-billboard-template").text(
            $("#billboard_template_id option:selected").text() || "-",
        );
        $("#review-social-template").text(
            $("#social_template_id option:selected").text() || "-",
        );
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
    $("#start_time").on("change input", syncTimeConstraints);
    $("#type").on("change", updateLivePreview);
    $("#supervising_entity").on("input", updateLivePreview);
    $("#assignment_id").on("change", updateLivePreview);
    $(
        "#platform_name, #project_manager_name, #description, #expected_properties_count",
    ).on("input change", updateLivePreview);

    $(form).on("change", 'input[name="report_image"]', function () {
        $("#review-report-image").text(
            this.files?.length ? "تم اختيار صورة" : "غير محددة",
        );
    });
    $(form).on("change", 'input[name="cover_image"]', function () {
        $("#review-cover-image").text(
            this.files?.length ? "تم اختيار صورة" : "غير محددة",
        );
    });
    $("#brochure_template_id, #billboard_template_id, #social_template_id").on(
        "change",
        updateLivePreview,
    );

    $(form).on("change", 'input[name="attachments[]"]', function () {
        updateAttachmentReviewCount();
    });

    // Dynamic Assignment Handling in Step 2 -> Loading Properties in Step 3
    $("#assignment_id").on("change", function () {
        const assignmentId = $(this).val();
        if (!assignmentId) {
            $("#selected-assignment-card").addClass("d-none");
            $("#properties-container").html(
                '<tr><td colspan="6" class="text-center py-4 text-muted">يرجى اختيار الإسناد في الخطوة الأولى لعرض العقارات التابعة له</td></tr>',
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

                    // Auto-fill supervising entity
                    if (res.assignment.issuing_entity) {
                        $("#supervising_entity")
                            .val(res.assignment.issuing_entity)
                            .trigger("input");
                    }

                    // Auto-fill dates and constrain min/max based on assignment
                    if (res.assignment.start_date) {
                        const startDateInput = $("#start_date");
                        startDateInput.val(res.assignment.start_date);
                        startDateInput.attr("min", res.assignment.start_date);
                        if (res.assignment.end_date) {
                            startDateInput.attr("max", res.assignment.end_date);
                        }
                        startDateInput.trigger("input").trigger("change");
                    }
                    if (res.assignment.end_date) {
                        const endDateInput = $("#end_date");
                        endDateInput.val(res.assignment.end_date);
                        endDateInput.attr("max", res.assignment.end_date);
                        if (res.assignment.start_date) {
                            endDateInput.attr("min", res.assignment.start_date);
                        }
                        endDateInput.trigger("input").trigger("change");
                    }

                    // Auto-fill default expected properties count based on assignment properties count
                    const propCount = res.properties
                        ? res.properties.length
                        : 0;
                    if (propCount > 0) {
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
                        res.assignment.issuing_entity || "-",
                    );

                    let html = "";
                    if (res.properties && res.properties.length > 0) {
                        res.properties.forEach(function (prop) {
                            const img = prop.main_image
                                ? "/tenant-assets/" + encodeURI(prop.main_image)
                                : propertyImageFallback;

                            html += `
                                <tr>
                                    <td>
                                        <input type="checkbox" name="property_ids[]" value="${escapeHtml(prop.id)}" class="form-check-input property-select-check" data-form-control
                                            data-area="${parseFloat(prop.area) || 0}" data-val="${parseFloat(prop.estimated_value) || 0}" data-number="${escapeHtml(prop.property_number)}" data-name="${escapeHtml(prop.name)}" data-type="${escapeHtml(prop.type || "")}" data-city="${escapeHtml(prop.city || "")}" data-image="${escapeHtml(img)}">
                                    </td>
                                    <td><code>${escapeHtml(prop.property_number)}</code></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="${escapeHtml(img)}" class="rounded me-2" style="width:28px;height:28px;object-fit:cover" alt="" onerror="this.onerror=null;this.src='${escapeHtml(propertyImageFallback)}';" />
                                            <span class="fw-medium">${escapeHtml(prop.name)}</span>
                                        </div>
                                    </td>
                                    <td>${escapeHtml(prop.city || "")}${prop.district ? " - " + escapeHtml(prop.district) : ""}</td>
                                    <td>${Number(prop.area || 0).toLocaleString()} م²</td>
                                    <td>${Number(prop.estimated_value || 0).toLocaleString()} ر.س</td>
                                </tr>
                            `;
                        });
                    } else {
                        html =
                            '<tr><td colspan="6" class="text-center py-4 text-muted">لا توجد عقارات مسجلة لهذا الإسناد حالياً.</td></tr>';
                    }

                    $("#properties-container").html(html);

                    if (restoredPropertyIds.length > 0) {
                        restoredPropertyIds.forEach(function (propertyId) {
                            $(
                                '.property-select-check[value="' +
                                    propertyId +
                                    '"]',
                            ).prop("checked", true);
                        });
                    }

                    calculateSelectedProperties();
                }
            },
            error: function () {
                $("#properties-container").html(
                    '<tr><td colspan="6" class="text-center py-4 text-danger">حدث خطأ أثناء تحميل عقارات الإسناد.</td></tr>',
                );
            },
        });
    });

    // Select all properties checkbox
    $(document).on("change", "#select-all-properties", function () {
        $(".property-select-check").prop("checked", $(this).prop("checked"));
        calculateSelectedProperties();
    });

    $(document).on("change", ".property-select-check", function () {
        calculateSelectedProperties();
    });

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
            });
        });

        $("#selected-props-count").text(count);
        $("#selected-props-area").text(totalArea.toLocaleString() + " م²");
        $("#selected-props-val").text(totalVal.toLocaleString() + " ر.س");
        $("#review-selected-props-count").text(count + " عقارات");
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

    $(form).on("click", "[data-remove-auction-property]", function () {
        const propertyId = String(this.dataset.removeAuctionProperty);
        $(`.property-select-check[value="${CSS.escape(propertyId)}"]`).prop(
            "checked",
            false,
        );
        calculateSelectedProperties();
    });

    // Quick jump step buttons in Step 5 Review
    document.querySelectorAll("[data-edit-step]").forEach((button) => {
        button.addEventListener("click", () => {
            const target = Number(button.dataset.editStep);
            if (!target) return;
            const stepper = window.auctionStepper || window.stepper;
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
});
