const mountCycleForm = () => {
    const form = document.querySelector("#report-cycle-form");
    if (!form) return;

    const setVisible = (control, visible) => {
        if (!control) return;
        const field = control.closest("[data-form-field]") || control;
        field.hidden = !visible;
        field.querySelectorAll("input, select, textarea").forEach((input) => {
            input.disabled = !visible;
        });
    };

    const activateTab = (tabBtn) => {
        if (!tabBtn) return;
        if (window.bootstrap && window.bootstrap.Tab) {
            window.bootstrap.Tab.getOrCreateInstance(tabBtn).show();
        } else {
            const tabsList = tabBtn.closest('[role="tablist"]');
            if (tabsList) {
                tabsList.querySelectorAll(".nav-link").forEach((btn) => {
                    btn.classList.remove("active");
                    btn.setAttribute("aria-selected", "false");
                });
            }
            tabBtn.classList.add("active");
            tabBtn.setAttribute("aria-selected", "true");
            const targetSelector =
                tabBtn.getAttribute("data-bs-target") ||
                tabBtn.getAttribute("href");
            if (targetSelector) {
                const targetPane = form.querySelector(targetSelector);
                if (targetPane) {
                    const contentContainer = targetPane.closest(".tab-content");
                    if (contentContainer) {
                        contentContainer
                            .querySelectorAll(".tab-pane")
                            .forEach((pane) => {
                                pane.classList.remove("show", "active");
                            });
                    }
                    targetPane.classList.add("show", "active");
                }
            }
        }
        try {
            tabBtn.scrollIntoView({
                inline: "nearest",
                block: "nearest",
            });
        } catch (_) {}
    };

    const updateMarketingBoxes = () => {
        const channelsSelect =
            form.querySelector("#campaign-channels-select") ||
            form.querySelector('[name="channels[]"]') ||
            form.querySelector('[name="channels"]');
        if (!channelsSelect) return;

        let selected = [];
        if (window.jQuery && window.jQuery(channelsSelect).data("select2")) {
            selected = window.jQuery(channelsSelect).val() || [];
        } else if (channelsSelect.selectedOptions) {
            selected = Array.from(channelsSelect.selectedOptions).map(
                (o) => o.value,
            );
            if (!selected.length && window.jQuery) {
                selected = window.jQuery(channelsSelect).val() || [];
            }
        } else if (window.jQuery) {
            selected = window.jQuery(channelsSelect).val() || [];
        }

        if (!Array.isArray(selected)) {
            selected = selected ? [selected] : [];
        }
        selected = selected.map(String);

        // Toggle tabs container vs empty state
        const tabsWrapper = form.querySelector("#campaign-tabs-wrapper");
        const emptyState = form.querySelector("#campaign-tabs-empty");
        if (tabsWrapper && emptyState) {
            if (selected.length === 0) {
                tabsWrapper.classList.add("d-none");
                emptyState.classList.remove("d-none");
            } else {
                tabsWrapper.classList.remove("d-none");
                emptyState.classList.add("d-none");
            }
        }

        // Toggle each tab item button
        const tabItems = form.querySelectorAll(".campaign-channel-tab");
        tabItems.forEach((tabItem) => {
            const ch = String(tabItem.dataset.channel || "");
            const isSelected = selected.includes(ch);
            if (isSelected) {
                tabItem.classList.remove("d-none");
            } else {
                tabItem.classList.add("d-none");
                const btn = tabItem.querySelector(".nav-link");
                if (btn) {
                    btn.classList.remove("active");
                    btn.setAttribute("aria-selected", "false");
                }
            }
        });

        // Toggle each tab pane & enable/disable inputs
        const boxes = form.querySelectorAll(".campaign-channel-box");
        boxes.forEach((box) => {
            const ch = String(box.dataset.channel || "");
            const isSelected = selected.includes(ch);

            box.querySelectorAll("input, select, textarea").forEach((input) => {
                input.disabled = !isSelected;
            });

            if (!isSelected) {
                box.classList.remove("show", "active");
                box.classList.add("d-none");
                box.style.display = "none";
                box.hidden = true;
            } else {
                box.classList.remove("d-none");
                box.style.display = "";
                box.hidden = false;
            }
        });

        // Ensure one visible tab is active if any channels are selected
        if (selected.length > 0) {
            const currentActiveTab = form.querySelector(
                ".campaign-channel-tab:not(.d-none) .nav-link.active",
            );
            if (!currentActiveTab) {
                // Prefer tab with validation errors
                const errorPane = form.querySelector(
                    ".campaign-channel-pane:not(.d-none):has(.is-invalid, [data-field-error]), .campaign-channel-box:not(.d-none):has(.is-invalid, [data-field-error])",
                );
                let targetTabBtn = null;
                if (errorPane) {
                    const errCh = errorPane.dataset.channel;
                    const errTabItem = form.querySelector(
                        `.campaign-channel-tab[data-channel="${errCh}"]:not(.d-none)`,
                    );
                    if (errTabItem)
                        targetTabBtn = errTabItem.querySelector(".nav-link");
                }
                if (!targetTabBtn) {
                    const firstVisibleTab = form.querySelector(
                        ".campaign-channel-tab:not(.d-none) .nav-link",
                    );
                    targetTabBtn = firstVisibleTab;
                }
                if (targetTabBtn) {
                    activateTab(targetTabBtn);
                }
            } else {
                // Ensure active pane matches active tab
                const targetSelector =
                    currentActiveTab.getAttribute("data-bs-target") ||
                    currentActiveTab.getAttribute("href");
                if (targetSelector) {
                    const activePane = form.querySelector(targetSelector);
                    if (
                        activePane &&
                        !activePane.classList.contains("active")
                    ) {
                        activateTab(currentActiveTab);
                    }
                }
            }
        }

        // Update properties dropdown for board_before based on selected auction
        const auctionInput = form.querySelector('[name="auction_id"]');
        const auctionId = auctionInput ? auctionInput.value : "";
        const propertySelect = form.querySelector(
            '[name="campaigns[board_before][metrics][property_id]"]',
        );
        if (propertySelect && form.dataset.campaignProperties && auctionId) {
            try {
                const properties =
                    JSON.parse(form.dataset.campaignProperties)[auctionId] ||
                    [];
                const selectedProp = propertySelect.value;
                const ids = properties.map((p) => String(p.value));
                if (propertySelect.dataset.auction !== auctionId) {
                    propertySelect.replaceChildren(
                        new Option("", ""),
                        ...properties.map((p) => new Option(p.label, p.value)),
                    );
                    propertySelect.value = ids.includes(selectedProp)
                        ? selectedProp
                        : "";
                    propertySelect.dataset.auction = auctionId;
                    if (window.jQuery)
                        window.jQuery(propertySelect).trigger("change.select2");
                }
            } catch (e) {
                console.error("Error updating campaign property options:", e);
            }
        }
    };

    const updateLegacyFields = () => {
        const singleChannel = form.querySelector('[name="channel"]');
        if (!singleChannel) return;
        if (form.dataset.campaignMetrics) {
            const channel = singleChannel.value;
            const metrics =
                JSON.parse(form.dataset.campaignMetrics)[channel] || [];
            form.querySelectorAll("[data-campaign-metric]").forEach((control) =>
                setVisible(
                    control,
                    metrics.includes(control.dataset.campaignMetric),
                ),
            );
            const fields = JSON.parse(form.dataset.campaignFields);
            setVisible(
                form.querySelector('[name="campaign_date"]'),
                channel === "whatsapp",
            );
            setVisible(
                form.querySelector('[name="url"]'),
                fields.links.includes(channel),
            );
            setVisible(
                form.querySelector('[name="evidence[]"]'),
                fields.evidence.includes(channel),
            );
            const auction = form.querySelector('[name="auction_id"]')?.value;
            const propertySelect = form.querySelector(
                '[name="metrics[property_id]"]',
            );
            if (propertySelect && auction) {
                const properties =
                    JSON.parse(form.dataset.campaignProperties)[auction] || [];
                const selected = propertySelect.value;
                const ids = properties.map((p) => String(p.value));
                if (propertySelect.dataset.auction !== auction) {
                    propertySelect.replaceChildren(
                        new Option("", ""),
                        ...properties.map((p) => new Option(p.label, p.value)),
                    );
                    propertySelect.value = ids.includes(selected)
                        ? selected
                        : "";
                    propertySelect.dataset.auction = auction;
                    if (window.jQuery)
                        window.jQuery(propertySelect).trigger("change.select2");
                }
            }
        }
    };

    const updateClosingStats = () => {
        let soldCount = 0;
        let unsoldCount = 0;

        const hiddenProps = form.querySelectorAll(
            "#closing-properties-data-container .property-hidden-data",
        );
        if (hiddenProps.length > 0) {
            hiddenProps.forEach((prop) => {
                const st = prop.querySelector('[data-field="status"]')?.value;
                if (st === "sold") {
                    soldCount++;
                } else if (st === "unsold") {
                    unsoldCount++;
                }
            });
        } else {
            const rows = form.querySelectorAll(
                "#closing-properties-table tbody tr.closing-property-row",
            );
            rows.forEach((row) => {
                const badge = row.querySelector(".closing-status-badge");
                const text = badge ? badge.textContent.trim() : "";
                if (text.includes("تمت الترسية")) {
                    soldCount++;
                } else if (
                    text.includes("غير مباعة") ||
                    text.includes("لم تتم الترسية")
                ) {
                    unsoldCount++;
                }
            });
        }

        const completedCount = soldCount + unsoldCount;

        const completedEl = document.getElementById(
            "completed-closing-props-count",
        );
        if (completedEl) completedEl.textContent = String(completedCount);

        const soldEl = document.getElementById("sold-closing-props-count");
        if (soldEl) soldEl.textContent = String(soldCount);

        const unsoldEl = document.getElementById("unsold-closing-props-count");
        if (unsoldEl) unsoldEl.textContent = String(unsoldCount);
    };

    const updateClosingFields = () => {
        updateClosingStats();
    };

    // Helper functions for closing modal Select2 and validation
    const initClosingModalSelect2 = () => {
        const $select = $("#modal_result_status");
        if ($select.length && window.jQuery && $.fn.select2) {
            if ($select.data("select2")) {
                $select.select2("destroy");
            }
            $select.select2({
                dropdownParent: $("#closingPropertyModal"),
                placeholder: "اختر النتيجة",
                allowClear: false,
                width: "100%",
                dir: "rtl",
                minimumResultsForSearch: Infinity,
            });
        }
    };

    $(document).on("shown.bs.modal", "#closingPropertyModal", function () {
        initClosingModalSelect2();
    });

    const setClosingModalFieldError = (inputEl, message) => {
        if (!inputEl) return;
        inputEl.classList.add("is-invalid");
        inputEl.setAttribute("aria-invalid", "true");

        const inputGroup = inputEl.closest(".input-group");
        if (inputGroup) {
            inputGroup.classList.add("is-invalid");
        }

        if (inputEl.tagName === "SELECT") {
            const select2Container = inputEl.nextElementSibling?.matches(
                ".select2, .select2-container",
            )
                ? inputEl.nextElementSibling
                : inputEl.parentElement?.querySelector(".select2-container");
            if (select2Container) {
                select2Container.classList.add("is-invalid");
                const selection =
                    select2Container.querySelector(".select2-selection");
                if (selection) selection.classList.add("is-invalid");
            }
        }

        const fieldWrapper =
            inputEl.closest("[data-form-field]") || inputEl.parentElement;
        if (fieldWrapper) {
            fieldWrapper.classList.add("is-invalid");
            const errorEl = fieldWrapper.querySelector("[data-form-error]");
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.hidden = false;
                errorEl.removeAttribute("hidden");
                errorEl.classList.remove("hidden");
                errorEl.style.display = "block";
            }
        }
    };

    const clearClosingModalFieldError = (inputEl) => {
        if (!inputEl) return;
        inputEl.classList.remove("is-invalid");
        inputEl.removeAttribute("aria-invalid");

        const inputGroup = inputEl.closest(".input-group");
        if (inputGroup) {
            inputGroup.classList.remove("is-invalid");
        }

        if (inputEl.tagName === "SELECT") {
            const select2Container = inputEl.nextElementSibling?.matches(
                ".select2, .select2-container",
            )
                ? inputEl.nextElementSibling
                : inputEl.parentElement?.querySelector(".select2-container");
            if (select2Container) {
                select2Container.classList.remove("is-invalid");
                const selection =
                    select2Container.querySelector(".select2-selection");
                if (selection) selection.classList.remove("is-invalid");
            }
        }

        const fieldWrapper =
            inputEl.closest("[data-form-field]") || inputEl.parentElement;
        if (fieldWrapper) {
            fieldWrapper.classList.remove("is-invalid");
            const errorEl = fieldWrapper.querySelector("[data-form-error]");
            if (errorEl) {
                errorEl.textContent = "";
                errorEl.hidden = true;
                errorEl.style.display = "none";
            }
        }
    };

    const clearAllClosingModalErrors = () => {
        const modalEl = document.getElementById("closingPropertyModal");
        if (!modalEl) return;
        modalEl
            .querySelectorAll(".is-invalid")
            .forEach((el) => el.classList.remove("is-invalid"));
        modalEl.querySelectorAll("[data-form-error]").forEach((el) => {
            el.textContent = "";
            el.hidden = true;
            el.style.display = "none";
        });
    };

    const resetClosingModalFiles = () => {
        const fileInp = document.getElementById("modal_board_images_after");
        if (fileInp) {
            try {
                fileInp.value = "";
            } catch (_) {}
            if (
                fileInp._fileUploadController &&
                typeof fileInp._fileUploadController.clear === "function"
            ) {
                fileInp._fileUploadController.clear();
            }
        }

        const zone = document.getElementById(
            "modal_board_images_after-upload-zone",
        );
        if (zone) {
            zone.classList.remove("has-files", "drag-over");
            if (
                zone._fileUploadController &&
                typeof zone._fileUploadController.clear === "function"
            ) {
                zone._fileUploadController.clear();
            }
        }

        const previewContainer =
            document.getElementById("modal_board_images_after-preview-list") ||
            document.querySelector(
                '[data-field-name="modal_board_images_after[]"] [data-file-preview-container]',
            );
        if (previewContainer) {
            previewContainer.innerHTML = "";
        }

        const attachContainer = document.getElementById(
            "modal_existing_attachments_container",
        );
        const attachList = document.getElementById(
            "modal_existing_attachments_list",
        );
        if (attachContainer) attachContainer.classList.add("d-none");
        if (attachList) attachList.innerHTML = "";
    };

    const validateClosingModalField = (inputEl) => {
        if (!inputEl) return true;
        const id = inputEl.id;
        const val = (inputEl.value || "").trim();
        const statusVal = document.getElementById("modal_result_status")?.value || "";

        let error = null;

        if (id === "modal_opening_price") {
            if (!val) {
                error = "سعر الافتتاح مطلوب ويجب أن يكون رقمًا غير سالب.";
            } else if (isNaN(Number(val)) || Number(val) < 0) {
                error = "سعر الافتتاح يجب أن يكون رقمًا غير سالب.";
            }
        } else if (id === "modal_participants_count") {
            if (!val) {
                error = "عدد المشاركين مطلوب ويجب أن يكون رقمًا غير سالب.";
            } else if (isNaN(Number(val)) || Number(val) < 0) {
                error = "عدد المشاركين يجب أن يكون رقمًا غير سالب.";
            }
        } else if (id === "modal_bids_count") {
            if (!val) {
                error = "عدد المزايدات مطلوب ويجب أن يكون رقمًا غير سالب.";
            } else if (isNaN(Number(val)) || Number(val) < 0) {
                error = "عدد المزايدات يجب أن يكون رقمًا غير سالب.";
            }
        } else if (id === "modal_result_status") {
            if (!val || (val !== "sold" && val !== "unsold")) {
                error = "يرجى تحديد نتيجة العقار.";
            }
        } else if (id === "modal_buyer_name" && statusVal === "sold") {
            if (!val) {
                error = "اسم من تمت الترسية عليه مطلوب للعقار المباع.";
            } else if (val.length < 3) {
                error = "اسم المشتري يجب ألا يقل عن 3 أحرف.";
            }
        } else if (id === "modal_sale_price" && statusVal === "sold") {
            if (!val) {
                error = "قيمة الترسية مطلوبة ويجب أن تكون أكبر من صفر.";
            } else if (isNaN(Number(val)) || Number(val) <= 0) {
                error = "قيمة الترسية يجب أن تكون أكبر من صفر.";
            }
        } else if (id === "modal_unsold_reason" && statusVal === "unsold") {
            if (!val) {
                error = "سبب عدم البيع مطلوب للعقار غير المباع.";
            } else if (val.length < 3) {
                error = "سبب عدم البيع يجب ألا يقل عن 3 أحرف.";
            }
        }

        if (error) {
            setClosingModalFieldError(inputEl, error);
            return false;
        } else {
            clearClosingModalFieldError(inputEl);
            return true;
        }
    };

    $(document).on("hidden.bs.modal", "#closingPropertyModal", function () {
        resetClosingModalFiles();
        clearAllClosingModalErrors();
    });

    // Live validation listener for user changes inside closing modal (on change, blur, focusout, and live input if invalid)
    $(document).on(
        "change blur focusout input",
        "#closingPropertyModal input:not([type='hidden']):not([type='file']), #closingPropertyModal select, #closingPropertyModal textarea",
        function (e) {
            if (e.type === "focusout" || e.type === "blur" || e.type === "change" || $(this).hasClass("is-invalid")) {
                validateClosingModalField(this);
            }
        },
    );

    // Open single modal for property editing via AJAX (click on row or edit button)
    $(document).on(
        "click",
        ".closing-property-row, .edit-closing-prop-btn",
        function (e) {
            if (
                $(e.target).closest("input, select, textarea, label, a")
                    .length &&
                !$(e.target).closest(".edit-closing-prop-btn").length
            ) {
                return;
            }
            e.preventDefault();

            const $btn = $(this).hasClass("edit-closing-prop-btn")
                ? $(this)
                : $(this).find(".edit-closing-prop-btn");
            const $row = $(this).closest("tr.closing-property-row");
            const propId = String(
                $btn.data("property-id") ||
                    $row.data("property-id") ||
                    $row.data("property-row"),
            );
            const propUrl =
                $btn.data("property-url") || $row.data("property-url");

            const modalEl = document.getElementById("closingPropertyModal");
            if (!modalEl) return;

            const modal =
                window.bootstrap && window.bootstrap.Modal
                    ? window.bootstrap.Modal.getOrCreateInstance(modalEl, {
                          backdrop: "static",
                          keyboard: false,
                      })
                    : null;
            if (!modal) return;

            const spinner = document.getElementById("closing-modal-spinner");
            const content = document.getElementById("closing-modal-content");
            if (spinner && content) {
                spinner.classList.remove("d-none");
                content.classList.add("d-none");
            }

            modal.show();
            clearAllClosingModalErrors();
            resetClosingModalFiles();

            // Check if there are local hidden unsaved/staged inputs in the form
            const hiddenDataEl = form.querySelector(
                `[data-prop-hidden="${propId}"]`,
            );
            const localData = {};
            if (hiddenDataEl) {
                hiddenDataEl.querySelectorAll("[data-field]").forEach((inp) => {
                    localData[inp.dataset.field] = inp.value;
                });
            }

            const populateModal = (data) => {
                resetClosingModalFiles();

                const titleEl = document.getElementById(
                    "closingPropertyModalTitle",
                );
                if (titleEl)
                    titleEl.textContent = `نتيجة إقفال العقار: ${data.name || "عقار"}`;

                const numEl = document.getElementById(
                    "closingPropertyModalNumber",
                );
                if (numEl) numEl.textContent = data.property_number || "-";

                const propIdInput =
                    document.getElementById("modal_property_id");
                if (propIdInput) propIdInput.value = propId;

                const setField = (id, val) => {
                    const el = document.getElementById(id);
                    if (el)
                        el.value = val !== undefined && val !== null ? val : "";
                };

                setField(
                    "modal_opening_price",
                    localData.opening_price !== undefined
                        ? localData.opening_price
                        : data.opening_price,
                );
                setField(
                    "modal_participants_count",
                    localData.participants_count !== undefined
                        ? localData.participants_count
                        : data.participants_count,
                );
                setField(
                    "modal_bids_count",
                    localData.bids_count !== undefined
                        ? localData.bids_count
                        : data.bids_count,
                );

                const statusVal =
                    localData.status !== undefined
                        ? localData.status
                        : data.result_status;
                const $statusSelect = $("#modal_result_status");
                $statusSelect.val(statusVal || "");
                if ($statusSelect.data("select2")) {
                    $statusSelect.trigger("change.select2");
                } else {
                    initClosingModalSelect2();
                    $statusSelect.val(statusVal || "").trigger("change");
                }

                setField(
                    "modal_buyer_name",
                    localData.buyer_name !== undefined
                        ? localData.buyer_name
                        : data.buyer_name,
                );
                setField(
                    "modal_sale_price",
                    localData.sale_price !== undefined
                        ? localData.sale_price
                        : data.sale_price,
                );
                setField(
                    "modal_unsold_reason",
                    localData.unsold_reason !== undefined
                        ? localData.unsold_reason
                        : data.unsold_reason,
                );
                setField(
                    "modal_result_notes",
                    localData.result_notes !== undefined
                        ? localData.result_notes
                        : data.result_notes,
                );

                // Toggle sold vs unsold sections
                const soldSec = document.getElementById("modal_sold_section");
                const unsoldSec = document.getElementById(
                    "modal_unsold_section",
                );
                if (soldSec && unsoldSec) {
                    soldSec.classList.toggle("d-none", statusVal !== "sold");
                    unsoldSec.classList.toggle(
                        "d-none",
                        statusVal !== "unsold",
                    );
                }

                // Restore any staged new files specifically for this property
                const filesContainer = hiddenDataEl
                    ? hiddenDataEl.querySelector(
                          `[data-prop-files="${propId}"]`,
                      )
                    : null;
                const savedFileInput = filesContainer
                    ? filesContainer.querySelector("input[type='file']")
                    : null;
                if (
                    savedFileInput &&
                    savedFileInput.files &&
                    savedFileInput.files.length > 0
                ) {
                    const fileInp = document.getElementById(
                        "modal_board_images_after",
                    );
                    if (fileInp) {
                        if (window.DataTransfer) {
                            const dt = new DataTransfer();
                            Array.from(savedFileInput.files).forEach((f) =>
                                dt.items.add(f),
                            );
                            fileInp.files = dt.files;
                        }
                        if (
                            fileInp._fileUploadController &&
                            typeof fileInp._fileUploadController.setFiles ===
                                "function"
                        ) {
                            fileInp._fileUploadController.setFiles(
                                savedFileInput.files,
                            );
                        } else {
                            fileInp.dispatchEvent(
                                new Event("change", { bubbles: true }),
                            );
                        }
                    }
                }

                // Existing server attachments for THIS property
                const attachContainer = document.getElementById(
                    "modal_existing_attachments_container",
                );
                const attachList = document.getElementById(
                    "modal_existing_attachments_list",
                );
                if (attachContainer && attachList) {
                    if (data.attachments && data.attachments.length > 0) {
                        attachContainer.classList.remove("d-none");
                        attachList.innerHTML = data.attachments
                            .map((file) => {
                                const isMarkedRemoved = Boolean(
                                    form.querySelector(
                                        `input[type="hidden"][name="remove_board_evidence[]"][value="${file.id}"]`,
                                    ),
                                );
                                return `
                        <div class="col-md-6" id="existing-attach-${file.id}">
                            <div class="border rounded p-2">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-truncate small fw-semibold" title="${file.file_name}">${file.file_name}</span>
                                    <a href="${file.file_url}" target="_blank" class="btn btn-sm btn-icon btn-label-primary" title="معاينة"><i class="ti ti-eye"></i></a>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="remove_board_evidence[]" value="${file.id}" class="form-check-input" id="remove-board-${file.id}" ${isMarkedRemoved ? "checked" : ""}>
                                    <label for="remove-board-${file.id}" class="form-check-label text-danger small">حذف الصورة عند الحفظ</label>
                                </div>
                            </div>
                        </div>
                    `;
                            })
                            .join("");
                    } else {
                        attachContainer.classList.add("d-none");
                        attachList.innerHTML = "";
                    }
                }

                if (spinner && content) {
                    spinner.classList.add("d-none");
                    content.classList.remove("d-none");
                }
            };

            if (propUrl) {
                $.ajax({
                    url: propUrl,
                    type: "GET",
                    dataType: "json",
                    success: function (res) {
                        populateModal(res);
                    },
                    error: function () {
                        const row = form.querySelector(
                            `[data-property-row="${propId}"]`,
                        );
                        const name =
                            row
                                ?.querySelector(".fw-semibold")
                                ?.textContent.trim() || "";
                        const num =
                            row?.querySelector("code")?.textContent.trim() ||
                            "";
                        populateModal({
                            name: name,
                            property_number: num,
                            opening_price: "",
                            participants_count: "",
                            bids_count: "",
                            result_status: "",
                            buyer_name: "",
                            sale_price: "",
                            unsold_reason: "",
                            result_notes: "",
                            attachments: [],
                        });
                    },
                });
            }
        },
    );

    // Toggle sold vs unsold inside modal
    $(document).on("change", "#modal_result_status", function () {
        const val = $(this).val();
        const soldSec = document.getElementById("modal_sold_section");
        const unsoldSec = document.getElementById("modal_unsold_section");
        if (soldSec && unsoldSec) {
            soldSec.classList.toggle("d-none", val !== "sold");
            unsoldSec.classList.toggle("d-none", val !== "unsold");
        }
        validateClosingModalField(this);
        if (val === "sold") {
            clearClosingModalFieldError(
                document.getElementById("modal_unsold_reason"),
            );
        } else if (val === "unsold") {
            clearClosingModalFieldError(
                document.getElementById("modal_buyer_name"),
            );
            clearClosingModalFieldError(
                document.getElementById("modal_sale_price"),
            );
        } else {
            clearClosingModalFieldError(
                document.getElementById("modal_unsold_reason"),
            );
            clearClosingModalFieldError(
                document.getElementById("modal_buyer_name"),
            );
            clearClosingModalFieldError(
                document.getElementById("modal_sale_price"),
            );
        }
    });

    // Save modal data with strict validation
    $(document).on("click", "#btn-save-closing-modal", function (e) {
        e.preventDefault();

        const modalEl = document.getElementById("closingPropertyModal");
        if (!modalEl) return;

        clearAllClosingModalErrors();

        let hasError = false;
        let firstInvalidInput = null;

        const fieldsToCheck = [
            document.getElementById("modal_opening_price"),
            document.getElementById("modal_participants_count"),
            document.getElementById("modal_bids_count"),
            document.getElementById("modal_result_status"),
        ];

        const status = document.getElementById("modal_result_status")?.value;
        if (status === "sold") {
            fieldsToCheck.push(document.getElementById("modal_buyer_name"));
            fieldsToCheck.push(document.getElementById("modal_sale_price"));
        } else if (status === "unsold") {
            fieldsToCheck.push(document.getElementById("modal_unsold_reason"));
        }

        fieldsToCheck.forEach((inp) => {
            if (inp && !validateClosingModalField(inp)) {
                hasError = true;
                if (!firstInvalidInput) firstInvalidInput = inp;
            }
        });

        if (hasError) {
            if (firstInvalidInput) {
                if ($(firstInvalidInput).data("select2")) {
                    $(firstInvalidInput).select2("open");
                } else if (typeof firstInvalidInput.focus === "function") {
                    firstInvalidInput.focus();
                }
            }
            if (typeof window.toastr !== "undefined") {
                window.toastr.warning(
                    "يرجى إكمال جميع الحقول الإلزامية للعقار بشكل صحيح.",
                );
            }
            return;
        }

        const openingPrice = document.getElementById("modal_opening_price")?.value?.trim() || "";
        const participantsCount = document.getElementById("modal_participants_count")?.value?.trim() || "";
        const bidsCount = document.getElementById("modal_bids_count")?.value?.trim() || "";
        const buyerName = status === "sold" ? (document.getElementById("modal_buyer_name")?.value?.trim() || "") : "";
        const salePrice = status === "sold" ? (document.getElementById("modal_sale_price")?.value?.trim() || "") : "";
        const unsoldReason = status === "unsold" ? (document.getElementById("modal_unsold_reason")?.value?.trim() || "") : "";

        const propId = document.getElementById("modal_property_id")?.value;
        if (!propId) return;

        const resultNotes =
            document.getElementById("modal_result_notes")?.value || "";

        // Update hidden inputs in the form
        const hiddenDataEl = form.querySelector(
            `[data-prop-hidden="${propId}"]`,
        );
        if (hiddenDataEl) {
            const setHidden = (field, val) => {
                const inp = hiddenDataEl.querySelector(
                    `[data-field="${field}"]`,
                );
                if (inp)
                    inp.value = val !== undefined && val !== null ? val : "";
            };

            setHidden("status", status);
            setHidden("opening_price", openingPrice);
            setHidden("participants_count", participantsCount);
            setHidden("bids_count", bidsCount);
            setHidden("buyer_name", status === "sold" ? buyerName : "");
            setHidden("sale_price", status === "sold" ? salePrice : "");
            setHidden("unsold_reason", status === "unsold" ? unsoldReason : "");
            setHidden("result_notes", resultNotes);

            // Save staged files for this property
            const modalFileInp = document.getElementById(
                "modal_board_images_after",
            );
            const filesContainer = hiddenDataEl.querySelector(
                `[data-prop-files="${propId}"]`,
            );
            if (filesContainer) {
                if (
                    modalFileInp &&
                    modalFileInp.files &&
                    modalFileInp.files.length > 0
                ) {
                    const hiddenFileInput = document.createElement("input");
                    hiddenFileInput.type = "file";
                    hiddenFileInput.multiple = true;
                    hiddenFileInput.id = `board_images_after_${propId}`;
                    hiddenFileInput.name = `board_images_after[${propId}][]`;
                    hiddenFileInput.classList.add("d-none");
                    if (window.DataTransfer) {
                        const dt = new DataTransfer();
                        Array.from(modalFileInp.files).forEach((f) =>
                            dt.items.add(f),
                        );
                        hiddenFileInput.files = dt.files;
                    }
                    filesContainer.replaceChildren(hiddenFileInput);
                } else if (
                    !filesContainer.querySelector("input[type='file']")
                ) {
                    filesContainer.innerHTML = "";
                }
            }
        }

        // Sync remove_board_evidence checkboxes for this property
        modalEl
            .querySelectorAll('[name="remove_board_evidence[]"]')
            .forEach((chk) => {
                const val = chk.value;
                const existingHidden = form.querySelector(
                    `input[type="hidden"][name="remove_board_evidence[]"][value="${val}"]`,
                );
                if (chk.checked) {
                    if (!existingHidden) {
                        const hiddenChk = document.createElement("input");
                        hiddenChk.type = "hidden";
                        hiddenChk.name = "remove_board_evidence[]";
                        hiddenChk.value = val;
                        form.appendChild(hiddenChk);
                    }
                } else if (existingHidden) {
                    existingHidden.remove();
                }
            });

        // Update table row
        const row = form.querySelector(`[data-property-row="${propId}"]`);
        if (row) {
            const check = row.querySelector(`[data-closing-check="${propId}"]`);
            if (check) check.checked = true;

            const badge = row.querySelector(`[data-closing-badge="${propId}"]`);
            if (badge) {
                if (status === "sold") {
                    badge.className =
                        "badge bg-label-success closing-status-badge";
                    badge.textContent = "مباع / تمت الترسية";
                } else if (status === "unsold") {
                    badge.className =
                        "badge bg-label-danger closing-status-badge";
                    badge.textContent = "غير مباعة";
                }
            }

            row.classList.toggle("table-success-subtle", status === "sold");
            row.classList.toggle("table-danger-subtle", status === "unsold");

            const openingCell = row.querySelector(
                `[data-opening-cell="${propId}"]`,
            );
            if (openingCell && openingPrice) {
                openingCell.textContent =
                    Number(openingPrice).toLocaleString() + " ر.س";
            }
        }

        updateClosingStats();

        // Close modal
        const modalInstance =
            window.bootstrap && window.bootstrap.Modal
                ? window.bootstrap.Modal.getInstance(modalEl)
                : null;
        if (modalInstance) {
            modalInstance.hide();
        }

        // Clean modal files immediately after closing/saving
        resetClosingModalFiles();

        if (typeof window.toastr !== "undefined") {
            window.toastr.success("تم حفظ وتحديث نتيجة العقار بنجاح.");
        }
    });

    const updateAll = () => {
        updateMarketingBoxes();
        updateLegacyFields();
        updateClosingFields();
    };

    const handleDraftRestored = (event) => {
        const data = event?.detail?.data || {};

        // 1. If draft has auction_id, set it and populate properties
        const auctionId =
            data.auction_id || form.querySelector('[name="auction_id"]')?.value;
        if (auctionId) {
            const auctionSelect = form.querySelector('[name="auction_id"]');
            if (
                auctionSelect &&
                String(auctionSelect.value) !== String(auctionId)
            ) {
                auctionSelect.value = auctionId;
                if (window.jQuery) {
                    window
                        .jQuery(auctionSelect)
                        .val(auctionId)
                        .trigger("change.select2");
                }
            }
        }

        // 2. Resolve selected channels from data.channels or data.campaigns keys
        let channels = [];
        if (Array.isArray(data.channels) && data.channels.length) {
            channels = data.channels.map(String);
        } else if (data.campaigns && typeof data.campaigns === "object") {
            channels = Object.keys(data.campaigns).map(String);
        }

        const channelsSelect =
            form.querySelector("#campaign-channels-select") ||
            form.querySelector('[name="channels[]"]') ||
            form.querySelector('[name="channels"]');

        if (channelsSelect && channels.length) {
            Array.from(channelsSelect.options).forEach((opt) => {
                opt.selected = channels.includes(String(opt.value));
            });
            if (window.jQuery) {
                window
                    .jQuery(channelsSelect)
                    .val(channels)
                    .trigger("change.select2");
            }
        }

        // 3. Update boxes visibility and enable inputs
        updateAll();

        // 4. Fill in restored campaign values for each channel
        if (data.campaigns && typeof data.campaigns === "object") {
            Object.entries(data.campaigns).forEach(([ch, campaignObj]) => {
                if (!campaignObj || typeof campaignObj !== "object") return;

                if (campaignObj.campaign_date) {
                    const dateInput = form.querySelector(
                        `[name="campaigns[${ch}][campaign_date]"]`,
                    );
                    if (dateInput) dateInput.value = campaignObj.campaign_date;
                }

                if (campaignObj.url) {
                    const urlInput = form.querySelector(
                        `[name="campaigns[${ch}][url]"]`,
                    );
                    if (urlInput) urlInput.value = campaignObj.url;
                }

                if (
                    campaignObj.metrics &&
                    typeof campaignObj.metrics === "object"
                ) {
                    Object.entries(campaignObj.metrics).forEach(
                        ([metricKey, metricVal]) => {
                            const metricInput = form.querySelector(
                                `[name="campaigns[${ch}][metrics][${metricKey}]"]`,
                            );
                            if (metricInput) {
                                metricInput.value = String(metricVal ?? "");
                                if (metricInput.tagName === "SELECT") {
                                    Array.from(metricInput.options).forEach(
                                        (opt) => {
                                            opt.selected =
                                                String(opt.value) ===
                                                String(metricVal);
                                        },
                                    );
                                    if (window.jQuery) {
                                        window
                                            .jQuery(metricInput)
                                            .val(metricVal)
                                            .trigger("change.select2");
                                    }
                                }
                            }
                        },
                    );
                }
            });
        }

        initClosingModalSelect2();
        updateAll();
        setTimeout(updateAll, 50);
        setTimeout(updateAll, 200);
    };

    const syncTabErrorStates = () => {
        const tabItems = form.querySelectorAll(".campaign-channel-tab");
        tabItems.forEach((tabItem) => {
            const ch = String(tabItem.dataset.channel || "");
            const pane = form.querySelector(`#channel-pane-${ch}`);
            const btn = tabItem.querySelector(".nav-link");
            if (!pane || !btn) return;
            const hasError =
                pane.querySelector(
                    ".is-invalid, [aria-invalid='true'], [data-form-error]:not([hidden]):not([style*='display: none'])",
                ) !== null;
            if (hasError) {
                btn.classList.add("border-danger", "text-danger");
            } else {
                btn.classList.remove("border-danger", "text-danger");
            }
        });
    };

    form.addEventListener("change", () => {
        initClosingModalSelect2();
        updateAll();
        syncTabErrorStates();
    });
    form.addEventListener("input", syncTabErrorStates);
    form.addEventListener(
        "invalid",
        (e) => {
            const pane = e.target.closest(".campaign-channel-pane");
            if (pane) {
                const ch = pane.dataset.channel;
                const tabBtn = form.querySelector(`#tab-btn-${ch}`);
                if (tabBtn) {
                    activateTab(tabBtn);
                }
            }
            syncTabErrorStates();
        },
        true,
    );
    form.addEventListener("wizardDraftRestored", handleDraftRestored);
    form.addEventListener("click", (e) => {
        const tabBtn = e.target.closest(".campaign-scrolled-tabs .nav-link");
        if (tabBtn) {
            activateTab(tabBtn);
        }
    });

    if (window.jQuery) {
        const $ = window.jQuery;
        $(form).on(
            "change change.select2 select2:select select2:unselect select2:clear",
            "select, #campaign-channels-select, [name='channels[]'], [name='auction_id']",
            updateAll,
        );
        $(document).on(
            "change change.select2 select2:select select2:unselect select2:clear",
            "#campaign-channels-select",
            updateAll,
        );
        $(form).on("select2:select", "#campaign-channels-select", function (e) {
            const newlySelected = e.params?.data?.id;
            updateAll();
            if (newlySelected) {
                const newTabBtn = form.querySelector(
                    `#tab-btn-${newlySelected}`,
                );
                if (newTabBtn) {
                    activateTab(newTabBtn);
                }
            }
        });
        $(form).on("wizardDraftRestored", handleDraftRestored);
    }

    const channelsEl =
        form.querySelector("#campaign-channels-select") ||
        form.querySelector('[name="channels[]"]');
    if (channelsEl) {
        channelsEl.addEventListener("change", updateAll);
    }

    initClosingModalSelect2();
    updateAll();
    setTimeout(updateAll, 50);
    setTimeout(updateAll, 200);
    setTimeout(updateAll, 500);
};

if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", mountCycleForm, {
        once: true,
    });
else mountCycleForm();
