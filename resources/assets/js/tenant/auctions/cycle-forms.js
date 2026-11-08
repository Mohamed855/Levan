const mountCycleForm = () => {
    const form = document.querySelector("#report-cycle-form");
    if (!form) return;

    const setVisible = (control, visible) => {
        const field = control.closest("[data-form-field]");
        field.hidden = !visible;
        field.querySelectorAll("input, select, textarea").forEach((input) => {
            input.disabled = !visible;
        });
    };
    const updateFields = () => {
        if (form.dataset.campaignMetrics) {
            const channel = form.querySelector('[name="channel"]').value;
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
            const auction = form.querySelector('[name="auction_id"]').value;
            const propertySelect = form.querySelector('[name="metrics[property_id]"]');
            const properties = JSON.parse(form.dataset.campaignProperties)[auction] || [];
            const selected = propertySelect.value;
            const ids = properties.map(property => String(property.value));
            if (propertySelect.dataset.auction !== auction) {
                propertySelect.replaceChildren(new Option("", ""), ...properties.map(property => new Option(property.label, property.value)));
                propertySelect.value = ids.includes(selected) ? selected : "";
                propertySelect.dataset.auction = auction;
                if (window.jQuery) window.jQuery(propertySelect).trigger("change.select2");
            }
        }
        form.querySelectorAll("[data-closing-result]").forEach((section) => {
            const status = section.querySelector("[data-result-status]").value;
            section
                .querySelectorAll("[data-result-kind]")
                .forEach((control) =>
                    setVisible(control, control.dataset.resultKind === status),
                );
        });
    };
    form.addEventListener("change", updateFields);
    form.addEventListener("wizardDraftRestored", updateFields);
    if (window.jQuery) {
        window.jQuery(form).on("change", "select", updateFields);
        window.jQuery(form).on("wizardDraftRestored", updateFields);
    }
    updateFields();
};

if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", mountCycleForm, {
        once: true,
    });
else mountCycleForm();
