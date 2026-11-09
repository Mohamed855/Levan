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

    const updateClosingFields = () => {
        form.querySelectorAll("[data-closing-result]").forEach((section) => {
            const statusSelect = section.querySelector("[data-result-status]");
            if (!statusSelect) return;
            const status = statusSelect.value;
            section
                .querySelectorAll("[data-result-kind]")
                .forEach((control) =>
                    setVisible(control, control.dataset.resultKind === status),
                );
        });
    };

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

    form.addEventListener("change", updateAll);
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
