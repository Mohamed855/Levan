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
            selected = Array.from(channelsSelect.selectedOptions).map((o) => o.value);
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
                ".campaign-channel-tab:not(.d-none) .nav-link.active"
            );
            if (!currentActiveTab) {
                // Prefer tab with validation errors
                const errorPane = form.querySelector(
                    '.campaign-channel-pane:not(.d-none):has(.is-invalid, [data-field-error]), .campaign-channel-box:not(.d-none):has(.is-invalid, [data-field-error])'
                );
                let targetTabBtn = null;
                if (errorPane) {
                    const errCh = errorPane.dataset.channel;
                    const errTabItem = form.querySelector(
                        `.campaign-channel-tab[data-channel="${errCh}"]:not(.d-none)`
                    );
                    if (errTabItem) targetTabBtn = errTabItem.querySelector(".nav-link");
                }
                if (!targetTabBtn) {
                    const firstVisibleTab = form.querySelector(
                        ".campaign-channel-tab:not(.d-none) .nav-link"
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
                    if (activePane && !activePane.classList.contains("active")) {
                        activateTab(currentActiveTab);
                    }
                }
            }
        }

        // Update properties dropdown for board_before based on selected auction
        const auctionInput = form.querySelector('[name="auction_id"]');
        const auctionId = auctionInput ? auctionInput.value : "";
        const propertySelect = form.querySelector(
            '[name="campaigns[board_before][metrics][property_id]"]'
        );
        if (propertySelect && form.dataset.campaignProperties && auctionId) {
            try {
                const properties =
                    JSON.parse(form.dataset.campaignProperties)[auctionId] || [];
                const selectedProp = propertySelect.value;
                const ids = properties.map((p) => String(p.value));
                if (propertySelect.dataset.auction !== auctionId) {
                    propertySelect.replaceChildren(
                        new Option("", ""),
                        ...properties.map((p) => new Option(p.label, p.value))
                    );
                    propertySelect.value = ids.includes(selectedProp) ? selectedProp : "";
                    propertySelect.dataset.auction = auctionId;
                    if (window.jQuery) window.jQuery(propertySelect).trigger("change.select2");
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
            setVisible(form.querySelector('[name="campaign_date"]'), channel === "whatsapp");
            setVisible(form.querySelector('[name="url"]'), fields.links.includes(channel));
            setVisible(form.querySelector('[name="evidence[]"]'), fields.evidence.includes(channel));
            const auction = form.querySelector('[name="auction_id"]')?.value;
            const propertySelect = form.querySelector('[name="metrics[property_id]"]');
            if (propertySelect && auction) {
                const properties = JSON.parse(form.dataset.campaignProperties)[auction] || [];
                const selected = propertySelect.value;
                const ids = properties.map((p) => String(p.value));
                if (propertySelect.dataset.auction !== auction) {
                    propertySelect.replaceChildren(new Option("", ""), ...properties.map(p => new Option(p.label, p.value)));
                    propertySelect.value = ids.includes(selected) ? selected : "";
                    propertySelect.dataset.auction = auction;
                    if (window.jQuery) window.jQuery(propertySelect).trigger("change.select2");
                }
            }
        }
    };

    const updateClosingStats = () => {
        let soldCount = 0;
        let unsoldCount = 0;

        const rows = form.querySelectorAll("#closing-properties-table tbody tr.closing-property-row");
        rows.forEach((row) => {
            const badge = row.querySelector(".closing-status-badge");
            const text = badge ? badge.textContent.trim() : "";
            if (text === "مباع / تمت الترسية") {
                soldCount++;
            } else if (text === "لم تتم الترسية") {
                unsoldCount++;
            }
        });

        const completedCount = soldCount + unsoldCount;

        const completedEl = document.getElementById("completed-closing-props-count");
        if (completedEl) completedEl.textContent = String(completedCount);

        const soldEl = document.getElementById("sold-closing-props-count");
        if (soldEl) soldEl.textContent = String(soldCount);

        const unsoldEl = document.getElementById("unsold-closing-props-count");
        if (unsoldEl) unsoldEl.textContent = String(unsoldCount);
    };

    const updateClosingFields = () => {
        updateClosingStats();
    };

    // Open single modal for property editing via AJAX
    $(document).on("click", ".edit-closing-prop-btn", function (e) {
        e.preventDefault();
        e.stopPropagation();

        const propId = String(this.dataset.propertyId);
        const propUrl = this.dataset.propertyUrl;
        const modalEl = document.getElementById("closingPropertyModal");
        if (!modalEl) return;

        const modal = window.bootstrap && window.bootstrap.Modal
            ? window.bootstrap.Modal.getOrCreateInstance(modalEl)
            : null;
        if (!modal) return;

        const spinner = document.getElementById("closing-modal-spinner");
        const content = document.getElementById("closing-modal-content");
        if (spinner && content) {
            spinner.classList.remove("d-none");
            content.classList.add("d-none");
        }

        modal.show();

        // Clear any previous field validation errors
        modalEl.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
        modalEl.querySelectorAll("[data-form-error]").forEach((el) => {
            el.textContent = "";
            el.hidden = true;
        });

        // Check if there are local hidden unsaved/staged inputs in the form
        const hiddenDataEl = form.querySelector(`[data-prop-hidden="${propId}"]`);
        const localData = {};
        if (hiddenDataEl) {
            hiddenDataEl.querySelectorAll("[data-field]").forEach((inp) => {
                localData[inp.dataset.field] = inp.value;
            });
        }

        const populateModal = (data) => {
            const titleEl = document.getElementById("closingPropertyModalTitle");
            if (titleEl) titleEl.textContent = `نتيجة إقفال العقار: ${data.name || "عقار"}`;

            const numEl = document.getElementById("closingPropertyModalNumber");
            if (numEl) numEl.textContent = data.property_number || "-";

            const propIdInput = document.getElementById("modal_property_id");
            if (propIdInput) propIdInput.value = propId;

            const setField = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val !== undefined && val !== null ? val : "";
            };

            setField("modal_opening_price", localData.opening_price !== undefined ? localData.opening_price : data.opening_price);
            setField("modal_participants_count", localData.participants_count !== undefined ? localData.participants_count : data.participants_count);
            setField("modal_bids_count", localData.bids_count !== undefined ? localData.bids_count : data.bids_count);

            const statusVal = localData.status !== undefined ? localData.status : data.result_status;
            setField("modal_result_status", statusVal);

            setField("modal_buyer_name", localData.buyer_name !== undefined ? localData.buyer_name : data.buyer_name);
            setField("modal_sale_price", localData.sale_price !== undefined ? localData.sale_price : data.sale_price);
            setField("modal_unsold_reason", localData.unsold_reason !== undefined ? localData.unsold_reason : data.unsold_reason);
            setField("modal_result_notes", localData.result_notes !== undefined ? localData.result_notes : data.result_notes);

            const fileInp = document.getElementById("modal_board_images_after");
            if (fileInp) fileInp.value = "";

            // Toggle sold vs unsold sections
            const soldSec = document.getElementById("modal_sold_section");
            const unsoldSec = document.getElementById("modal_unsold_section");
            if (soldSec && unsoldSec) {
                soldSec.classList.toggle("d-none", statusVal !== "sold");
                unsoldSec.classList.toggle("d-none", statusVal !== "unsold");
            }

            // Existing attachments
            const attachContainer = document.getElementById("modal_existing_attachments_container");
            const attachList = document.getElementById("modal_existing_attachments_list");
            if (attachContainer && attachList) {
                if (data.attachments && data.attachments.length > 0) {
                    attachContainer.classList.remove("d-none");
                    attachList.innerHTML = data.attachments.map((file) => `
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-truncate small fw-semibold" title="${file.file_name}">${file.file_name}</span>
                                    <a href="${file.file_url}" target="_blank" class="btn btn-sm btn-icon btn-label-primary" title="معاينة"><i class="ti ti-eye"></i></a>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="remove_board_evidence[]" value="${file.id}" class="form-check-input" id="remove-board-${file.id}">
                                    <label for="remove-board-${file.id}" class="form-check-label text-danger small">حذف الصورة عند الحفظ</label>
                                </div>
                            </div>
                        </div>
                    `).join("");
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
                    // Fallback to row data if ajax fails
                    const row = form.querySelector(`[data-property-row="${propId}"]`);
                    const name = row?.querySelector(".fw-semibold")?.textContent.trim() || "";
                    const num = row?.querySelector("code")?.textContent.trim() || "";
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
    });

    // Toggle sold vs unsold inside modal
    $(document).on("change", "#modal_result_status", function () {
        const val = this.value;
        const soldSec = document.getElementById("modal_sold_section");
        const unsoldSec = document.getElementById("modal_unsold_section");
        if (soldSec && unsoldSec) {
            soldSec.classList.toggle("d-none", val !== "sold");
            unsoldSec.classList.toggle("d-none", val !== "unsold");
        }
    });

    // Save modal data with strict validation
    $(document).on("click", "#btn-save-closing-modal", function (e) {
        e.preventDefault();

        const modalEl = document.getElementById("closingPropertyModal");
        if (!modalEl) return;

        // Clear previous validation errors
        modalEl.querySelectorAll(".is-invalid").forEach((el) => el.classList.remove("is-invalid"));
        modalEl.querySelectorAll("[data-form-error]").forEach((el) => {
            el.textContent = "";
            el.hidden = true;
        });

        const setError = (inputEl, message) => {
            if (!inputEl) return;
            inputEl.classList.add("is-invalid");
            const fieldWrapper = inputEl.closest("[data-form-field]") || inputEl.parentElement;
            const errorEl = fieldWrapper?.querySelector("[data-form-error]");
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.hidden = false;
            }
        };

        let hasError = false;
        let firstInvalidInput = null;

        const checkRequired = (id, message, validateFn) => {
            const input = document.getElementById(id);
            if (!input) return null;
            const val = input.value.trim();
            const isValid = val !== "" && (!validateFn || validateFn(val));
            if (!isValid) {
                setError(input, message);
                hasError = true;
                if (!firstInvalidInput) firstInvalidInput = input;
                return null;
            }
            return val;
        };

        const openingPrice = checkRequired("modal_opening_price", "سعر الافتتاح مطلوب ويجب أن يكون رقمًا غير سالب.", (v) => !isNaN(Number(v)) && Number(v) >= 0);
        const participantsCount = checkRequired("modal_participants_count", "عدد المشاركين مطلوب ويجب أن يكون رقمًا غير سالب.", (v) => !isNaN(Number(v)) && Number(v) >= 0);
        const bidsCount = checkRequired("modal_bids_count", "عدد المزايدات مطلوب ويجب أن يكون رقمًا غير سالب.", (v) => !isNaN(Number(v)) && Number(v) >= 0);
        const status = checkRequired("modal_result_status", "يرجى تحديد نتيجة العقار.", (v) => v === "sold" || v === "unsold");

        let buyerName = "";
        let salePrice = "";
        let unsoldReason = "";

        if (status === "sold") {
            buyerName = checkRequired("modal_buyer_name", "اسم من تمت الترسية عليه مطلوب للعقار المباع.");
            salePrice = checkRequired("modal_sale_price", "قيمة الترسية مطلوبة ويجب أن تكون أكبر من صفر.", (v) => !isNaN(Number(v)) && Number(v) > 0);
        } else if (status === "unsold") {
            unsoldReason = checkRequired("modal_unsold_reason", "سبب عدم البيع مطلوب للعقار غير المباع.");
        }

        if (hasError) {
            if (firstInvalidInput && typeof firstInvalidInput.focus === "function") {
                firstInvalidInput.focus();
            }
            if (typeof window.toastr !== "undefined") {
                window.toastr.warning("يرجى إكمال جميع الحقول الإلزامية للعقار بشكل صحيح.");
            }
            return;
        }

        const propId = document.getElementById("modal_property_id")?.value;
        if (!propId) return;

        const resultNotes = document.getElementById("modal_result_notes")?.value || "";

        // Update hidden inputs in the form
        const hiddenDataEl = form.querySelector(`[data-prop-hidden="${propId}"]`);
        if (hiddenDataEl) {
            const setHidden = (field, val) => {
                const inp = hiddenDataEl.querySelector(`[data-field="${field}"]`);
                if (inp) inp.value = val !== undefined && val !== null ? val : "";
            };

            setHidden("status", status);
            setHidden("opening_price", openingPrice);
            setHidden("participants_count", participantsCount);
            setHidden("bids_count", bidsCount);
            setHidden("buyer_name", status === "sold" ? buyerName : "");
            setHidden("sale_price", status === "sold" ? salePrice : "");
            setHidden("unsold_reason", status === "unsold" ? unsoldReason : "");
            setHidden("result_notes", resultNotes);

            // Copy file input if user selected new files
            const modalFileInp = document.getElementById("modal_board_images_after");
            const filesContainer = hiddenDataEl.querySelector(`[data-prop-files="${propId}"]`);
            if (modalFileInp && modalFileInp.files && modalFileInp.files.length > 0 && filesContainer) {
                // Move or recreate input
                const clonedInp = modalFileInp.cloneNode(true);
                clonedInp.id = `board_images_after_${propId}`;
                clonedInp.name = `board_images_after[${propId}][]`;
                clonedInp.classList.add("d-none");
                filesContainer.replaceChildren(clonedInp);
            }
        }

        // Copy any checked remove_board_evidence checkboxes to form
        modalEl.querySelectorAll('[name="remove_board_evidence[]"]:checked').forEach((chk) => {
            const val = chk.value;
            if (!form.querySelector(`input[type="hidden"][name="remove_board_evidence[]"][value="${val}"]`)) {
                const hiddenChk = document.createElement("input");
                hiddenChk.type = "hidden";
                hiddenChk.name = "remove_board_evidence[]";
                hiddenChk.value = val;
                form.appendChild(hiddenChk);
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
                    badge.className = "badge bg-label-success closing-status-badge";
                    badge.textContent = "مباع / تمت الترسية";
                } else if (status === "unsold") {
                    badge.className = "badge bg-label-danger closing-status-badge";
                    badge.textContent = "لم تتم الترسية";
                }
            }

            row.classList.toggle("table-success-subtle", status === "sold");
            row.classList.toggle("table-danger-subtle", status === "unsold");

            const openingCell = row.querySelector(`[data-opening-cell="${propId}"]`);
            if (openingCell && openingPrice) {
                openingCell.textContent = Number(openingPrice).toLocaleString() + " ر.س";
            }
        }

        updateClosingStats();

        // Close modal
        const modalInstance = window.bootstrap && window.bootstrap.Modal
            ? window.bootstrap.Modal.getInstance(modalEl)
            : null;
        if (modalInstance) {
            modalInstance.hide();
        }

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
            data.auction_id ||
            form.querySelector('[name="auction_id"]')?.value;
        if (auctionId) {
            const auctionSelect = form.querySelector('[name="auction_id"]');
            if (auctionSelect && String(auctionSelect.value) !== String(auctionId)) {
                auctionSelect.value = auctionId;
                if (window.jQuery) {
                    window.jQuery(auctionSelect)
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
                window.jQuery(channelsSelect)
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
                                        window.jQuery(metricInput)
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
                    ".is-invalid, [aria-invalid='true'], [data-form-error]:not([hidden]):not([style*='display: none'])"
                ) !== null;
            if (hasError) {
                btn.classList.add("border-danger", "text-danger");
            } else {
                btn.classList.remove("border-danger", "text-danger");
            }
        });
    };

    form.addEventListener("change", () => {
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
        true
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
                const newTabBtn = form.querySelector(`#tab-btn-${newlySelected}`);
                if (newTabBtn) {
                    activateTab(newTabBtn);
                }
            }
        });
        $(form).on("wizardDraftRestored", handleDraftRestored);
    }

    const channelsEl = form.querySelector("#campaign-channels-select") || form.querySelector('[name="channels[]"]');
    if (channelsEl) {
        channelsEl.addEventListener("change", updateAll);
    }

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
