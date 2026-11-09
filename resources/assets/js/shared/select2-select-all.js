/**
 * Global Select2 Multiple "Select All / Deselect All" Controller
 *
 * Automatically attaches to all multiple Select2 inputs in the system.
 * Handles toggle behavior, label updates, and event synchronization.
 */
(function () {
    function getSelectElement(btn) {
        if (!btn) return null;
        if (btn.dataset.selectAllFor) {
            const el = document.getElementById(btn.dataset.selectAllFor);
            if (el) return el;
        }
        if (btn.dataset.target) {
            const el = document.querySelector(btn.dataset.target);
            if (el) return el;
        }
        const groupSelect = btn.closest(".input-group")?.querySelector("select");
        if (groupSelect) return groupSelect;
        const fieldSelect = btn.closest("[data-form-field]")?.querySelector("select");
        if (fieldSelect) return fieldSelect;
        return null;
    }

    function updateButtonState(btn, select) {
        if (!btn || !select) return;
        const textSpan = btn.querySelector(
            ".js-select-all-label, #select-all-regions-text, [data-select-all-text]"
        );
        const options = Array.from(select.options).filter(
            (opt) => !opt.disabled && opt.value !== ""
        );
        const selectedCount = options.filter((opt) => opt.selected).length;

        if (options.length > 0 && selectedCount === options.length) {
            if (textSpan) textSpan.textContent = "إلغاء التحديد";
            btn.setAttribute("title", "إلغاء تحديد الكل");
        } else {
            if (textSpan) textSpan.textContent = "تحديد الكل";
            btn.setAttribute("title", "تحديد الكل");
        }
    }

    function handleToggle(btn) {
        const select = getSelectElement(btn);
        if (!select) return;

        const options = Array.from(select.options).filter(
            (opt) => !opt.disabled && opt.value !== ""
        );
        const selectedCount = options.filter((opt) => opt.selected).length;
        const shouldSelectAll = !(options.length > 0 && selectedCount === options.length);

        options.forEach((opt) => {
            opt.selected = shouldSelectAll;
        });

        if (window.jQuery) {
            window.jQuery(select).trigger("change").trigger("change.select2");
        } else {
            select.dispatchEvent(new Event("change", { bubbles: true }));
        }

        const icon = btn.querySelector("i");
        if (icon) {
            icon.classList.add("ti-spin");
            setTimeout(() => icon.classList.remove("ti-spin"), 400);
        }

        updateButtonState(btn, select);
    }

    function syncButtonsForSelect(select) {
        if (!select) return;
        const buttons = [];
        if (select.id) {
            document
                .querySelectorAll(`[data-select-all-for="${select.id}"]`)
                .forEach((b) => buttons.push(b));
        }
        const groupBtn = select
            .closest(".input-group")
            ?.querySelector(
                ".js-select-all-trigger, .btn-select-all, .btn-select-all-regions"
            );
        if (groupBtn && !buttons.includes(groupBtn)) {
            buttons.push(groupBtn);
        }

        buttons.forEach((btn) => updateButtonState(btn, select));
    }

    function initAllButtons() {
        document
            .querySelectorAll(
                ".js-select-all-trigger, .btn-select-all, .btn-select-all-regions"
            )
            .forEach((btn) => {
                const select = getSelectElement(btn);
                if (select) {
                    updateButtonState(btn, select);
                }
            });
    }

    // Event Delegation for Button Click
    document.addEventListener("click", function (e) {
        const btn = e.target.closest(
            ".js-select-all-trigger, .btn-select-all, .btn-select-all-regions"
        );
        if (btn) {
            e.preventDefault();
            handleToggle(btn);
        }
    });

    // Native Change listener
    document.addEventListener("change", function (e) {
        if (e.target && e.target.tagName === "SELECT" && e.target.multiple) {
            syncButtonsForSelect(e.target);
        }
    });

    // jQuery & Select2 Events
    if (window.jQuery) {
        window.jQuery(document).on(
            "change change.select2 select2:select select2:unselect select2:clear",
            "select[multiple]",
            function () {
                syncButtonsForSelect(this);
            }
        );
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initAllButtons);
    } else {
        initAllButtons();
    }

    window.GlobalSelectAll = {
        sync: syncButtonsForSelect,
        init: initAllButtons,
    };
})();

