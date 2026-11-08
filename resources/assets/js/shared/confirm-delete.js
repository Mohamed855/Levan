/**
 * Global Confirmation Engine — Bootstrap 5 Modal
 *
 * Uniform Bootstrap 5 confirmation modals replacing SweetAlert2 across the entire application.
 * Supports:
 *   - window.confirmAction(options)
 *   - window.confirmActionAsync(options) -> Promise<boolean>
 *   - window.confirmDelete(id)
 *   - window.confirmLogout(formId)
 *   - window.confirmDeleteSelected(selectedIds, deleteUrl)
 *   - window.confirmDeleteSelectedmss(selectedIds, deleteUrl, modelName) [NO PASSWORD]
 */

import './toastr.js';

const VARIANT_CONFIGS = {
    danger: {
        wrapperClass:
            "avatar avatar-xl mx-auto rounded-circle d-flex align-items-center justify-content-center bg-danger mb-3",
        iconClass: "ti ti-trash fs-2 text-white",
        btnClass: "btn btn-danger px-4",
        btnIcon: "ti-trash",
    },
    warning: {
        wrapperClass:
            "avatar avatar-xl mx-auto rounded-circle d-flex align-items-center justify-content-center bg-danger mb-3",
        iconClass: "ti ti-alert-triangle fs-2 text-white",
        btnClass: "btn btn-warning px-4 text-white",
        btnIcon: "ti-alert-triangle",
    },
    primary: {
        wrapperClass:
            "avatar avatar-xl mx-auto rounded-circle d-flex align-items-center justify-content-center bg-primary mb-3",
        iconClass: "ti ti-help fs-2 text-white",
        btnClass: "btn btn-primary px-4",
        btnIcon: "ti-check",
    },
    success: {
        wrapperClass:
            "avatar avatar-xl mx-auto rounded-circle d-flex align-items-center justify-content-center bg-success mb-3",
        iconClass: "ti ti-check fs-2 text-white",
        btnClass: "btn btn-success px-4",
        btnIcon: "ti-check",
    },
};

window.confirmAction = function (options = {}) {
    const {
        title = "تأكيد العملية",
        text = "هل أنت متأكد من رغبتك في المتابعة؟",
        variant = options.variant ||
            (options.confirmButtonClass?.includes("primary") ||
            options.confirmClass?.includes("primary")
                ? "primary"
                : "danger"),
        icon = options.icon || null,
        confirmText = options.confirmText ||
            options.confirmButtonText ||
            "تأكيد",
        confirmClass = options.confirmClass ||
            options.confirmButtonClass ||
            null,
        cancelText = options.cancelText || options.cancelButtonText || "إلغاء",
        showInput = false,
        inputLabel = "",
        inputValue = "",
        onConfirm = null,
        onCancel = null,
    } = options;

    const modalEl = document.getElementById("globalConfirmationModal");
    if (modalEl?.dataset.submitting === 'true') return;
    if (!modalEl) {
        if (window.confirm(`${title}\n${text}`)) {
            if (typeof onConfirm === "function") onConfirm(inputValue);
        } else {
            if (typeof onCancel === "function") onCancel();
        }
        return;
    }

    const config =
        VARIANT_CONFIGS[variant] ||
        VARIANT_CONFIGS.primary ||
        VARIANT_CONFIGS.danger;
    const iconWrapper = document.getElementById(
        "globalConfirmationModalIconWrapper",
    );
    const iconEl = document.getElementById("globalConfirmationModalIcon");
    const headingEl = document.getElementById("globalConfirmationModalHeading");
    const messageEl = document.getElementById("globalConfirmationModalMessage");
    const inputContainer = document.getElementById(
        "globalConfirmationModalCustomInputContainer",
    );
    const inputLabelEl = document.getElementById(
        "globalConfirmationModalCustomInputLabel",
    );
    const inputEl = document.getElementById(
        "globalConfirmationModalCustomInput",
    );
    const inputError = document.getElementById(
        "globalConfirmationModalCustomInputError",
    );
    const cancelBtn = document.getElementById(
        "globalConfirmationModalCancelBtn",
    );
    const submitBtn = document.getElementById(
        "globalConfirmationModalSubmitBtn",
    );

    if (headingEl) headingEl.textContent = title;
    if (messageEl) messageEl.textContent = text;
    if (cancelBtn) {
        cancelBtn.innerHTML = `<i class="ti ti-x me-1"></i>${cancelText}`;
    }

    if (iconWrapper) iconWrapper.className = config.wrapperClass;
    if (iconEl) {
        if (icon) {
            let iconName = icon.trim();
            let parts = iconName.split(/\s+/);
            let tiIcon = parts.find((p) => p.startsWith("ti-"));
            if (!tiIcon && !parts.some((p) => p === "ti")) {
                tiIcon = `ti-${parts[0]}`;
                parts = [tiIcon];
            }
            if (!parts.includes("ti")) {
                parts.unshift("ti");
            }
            if (!parts.some((p) => p.startsWith("fs-"))) {
                parts.push("fs-2");
            }
            if (!parts.some((p) => p.startsWith("text-"))) {
                parts.push("text-white");
            }
            iconEl.className = parts.join(" ");
        } else {
            iconEl.className = config.iconClass;
        }
    }

    if (submitBtn) {
        submitBtn.className = confirmClass || config.btnClass;
        const submitIcon =
            options.confirmIcon ||
            (variant === "danger"
                ? "ti-trash"
                : confirmText.includes("خروج")
                  ? "ti-logout"
                  : config.btnIcon || "ti-check");
        submitBtn.innerHTML = `<i class="ti ${submitIcon} me-1"></i>${confirmText}`;
        submitBtn.disabled = false;
    }

    if (inputContainer && inputEl && inputLabelEl) {
        if (showInput) {
            inputContainer.classList.remove("d-none");
            inputLabelEl.textContent = inputLabel;
            inputEl.value = inputValue || "";
            if (inputError) {
                inputError.textContent = "";
                inputError.classList.add("d-none");
            }
        } else {
            inputContainer.classList.add("d-none");
        }
    }

    const bs =
        window.bootstrap ||
        (typeof bootstrap !== "undefined" ? bootstrap : null);
    if (!bs || !bs.Modal) {
        if (window.confirm(`${title}\n${text}`)) {
            if (typeof onConfirm === "function")
                onConfirm(inputEl ? inputEl.value : "");
        } else {
            if (typeof onCancel === "function") onCancel();
        }
        return;
    }

    let isConfirmed = false;
    const bsModal = bs.Modal.getOrCreateInstance(modalEl);

    // Clean up previous listeners on submit button
    const newSubmitBtn = submitBtn.cloneNode(true);
    submitBtn.parentNode.replaceChild(newSubmitBtn, submitBtn);

    newSubmitBtn.addEventListener("click", async function () {
        if (newSubmitBtn.disabled) return;
        const val = inputEl ? inputEl.value : "";
        newSubmitBtn.disabled = true;
        cancelBtn.disabled = true;
        modalEl.dataset.submitting = 'true';
        try {
            const result = typeof onConfirm === "function" ? await onConfirm(val) : undefined;
            if (result !== false) {
                isConfirmed = true;
                bsModal.hide();
            }
        } catch {
            window.toastr.error('تعذر إتمام العملية. يرجى المحاولة مرة أخرى.');
        } finally {
            newSubmitBtn.disabled = false;
            cancelBtn.disabled = false;
            delete modalEl.dataset.submitting;
        }
    });

    if (modalEl._confirmCancelHandler) {
        modalEl.removeEventListener(
            "hidden.bs.modal",
            modalEl._confirmCancelHandler,
        );
    }

    if (modalEl._confirmHideHandler) {
        modalEl.removeEventListener('hide.bs.modal', modalEl._confirmHideHandler);
    }
    const handleHide = function (event) {
        if (!isConfirmed && modalEl.dataset.submitting === 'true') event.preventDefault();
    };
    modalEl._confirmHideHandler = handleHide;
    modalEl.addEventListener('hide.bs.modal', handleHide);

    const handleHidden = function () {
        modalEl.removeEventListener("hidden.bs.modal", handleHidden);
        modalEl.removeEventListener('hide.bs.modal', handleHide);
        modalEl._confirmHideHandler = null;
        modalEl._confirmCancelHandler = null;
        if (!isConfirmed && typeof onCancel === "function") {
            onCancel();
        }
    };
    modalEl._confirmCancelHandler = handleHidden;
    modalEl.addEventListener("hidden.bs.modal", handleHidden);

    bsModal.show();
};

window.confirmActionAsync = function (options = {}) {
    return new Promise((resolve) => {
        window.confirmAction({
            ...options,
            onConfirm: (val) => resolve(options.showInput ? val : true),
            onCancel: () => resolve(false),
        });
    });
};

/* --------------------------------------------------------------------------
 | confirmDelete — compatibility adapter for existing row action forms
 -------------------------------------------------------------------------- */
window.confirmDelete = function (id, options = {}) {
    const form = document.getElementById("delete-form-" + id);
    if (!form && !options.url) return;

    return window.confirmRecordDelete({
        url: form?.action,
        method: form?.querySelector('[name="_method"]')?.value || 'DELETE',
        table: form?.closest('table'),
        ...options,
    });
};

/* --------------------------------------------------------------------------
 | confirmLogout — confirmation modal before submitting logout form
 -------------------------------------------------------------------------- */
window.confirmLogout = function (formId) {
    const targetForm =
        typeof formId === "string"
            ? document.getElementById(formId)
            : document.getElementById("tenant-logout-form") ||
              document.getElementById("logout-form");

    window.confirmAction({
        title: "تسجيل الخروج",
        text: "هل أنت متأكد من رغبتك في تسجيل الخروج من النظام؟",
        variant: "warning",
        icon: "ti-logout",
        confirmText: "تسجيل الخروج",
        confirmClass: "btn btn-danger px-4",
        onConfirm: function () {
            if (targetForm) targetForm.submit();
        },
    });
};

document.addEventListener("DOMContentLoaded", function () {
    const tenantLogoutBtn = document.getElementById("tenant-logout-btn");
    if (tenantLogoutBtn) {
        tenantLogoutBtn.addEventListener("click", function (e) {
            e.preventDefault();
            window.confirmLogout("tenant-logout-form");
        });
    }

    const adminLogoutBtn = document.getElementById("logout-btn");
    if (adminLogoutBtn) {
        adminLogoutBtn.addEventListener("click", function (e) {
            e.preventDefault();
            window.confirmLogout("logout-form");
        });
    }
});

/** Submit every regular and permanent deletion through the same AJAX action. */
let deletionPending = false;

function refreshDeletedRecords(options) {
    const jq = window.jQuery || window.$;
    if (jq?.fn?.dataTable) {
        const tables = options.table
            ? [options.table]
            : Array.from(jq.fn.dataTable.tables());
        let refreshed = false;
        for (const table of tables) {
            if (!jq.fn.dataTable.isDataTable(table)) continue;
            const api = jq(table).DataTable();
            if (api.ajax?.reload && (!api.ajax.url || api.ajax.url())) {
                api.ajax.reload(null, false);
                refreshed = true;
            }
        }
        if (refreshed) return true;
    }
    return false;
}

window.confirmRecordDelete = function (options = {}) {
    if (deletionPending) return;
    let target;
    try {
        target = new URL(options.url, window.location.href);
        if (!options.url || target.origin !== window.location.origin) throw new Error('Invalid delete URL');
    } catch {
        window.toastr.error('تعذر تحديد رابط الحذف.');
        return;
    }

    return window.confirmAction({
        title: options.title || 'تأكيد الحذف',
        text: options.text || 'هل أنت متأكد من عملية الحذف؟ لا يمكن التراجع عن هذا الإجراء!',
        variant: 'danger',
        icon: 'ti-trash',
        confirmText: options.confirmText || 'تأكيد الحذف',
        onConfirm: async function () {
            if (deletionPending) return false;
            deletionPending = true;
            let wasDeleted = false;
            try {
                const jq = window.jQuery || window.$;
                const response = await new Promise((resolve, reject) => jq.ajax({
                    url: target.href,
                    method: options.method || 'DELETE',
                    dataType: 'json',
                    timeout: 60000,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    data: options.data || {},
                    success: resolve,
                    error: reject,
                }));
                if (!response || response.success === false || response.status === 'error') {
                    throw { responseJSON: response };
                }
                wasDeleted = true;

                const message = response.message || options.successMessage || 'تم الحذف بنجاح.';
                const redirect = options.redirect || response.redirect;
                if (typeof options.onSuccess === 'function') await options.onSuccess(response);
                const refreshed = !redirect && (options.refresh === false || refreshDeletedRecords(options));
                if (refreshed) {
                    window.toastr.success(message);
                    return true;
                }

                // Carry the toast once across navigation from a detail/static page.
                const destination = new URL(redirect || window.location.href, window.location.href);
                if (destination.origin !== window.location.origin) {
                    throw new Error('تعذر تحديد صفحة العودة.');
                }
                try {
                    window.sessionStorage.setItem('delete-feedback:' + destination.pathname, message);
                } catch {
                    window.toastr.success(message);
                }
                if (redirect) window.location.assign(destination.href);
                else window.location.reload();
                return true;
            } catch (error) {
                const errors = error.responseJSON?.errors;
                const validationMessage = errors && Object.values(errors).flat()[0];
                const message = wasDeleted
                    ? 'تم الحذف، لكن تعذر تحديث الصفحة. يرجى تحديثها.'
                    : validationMessage || error.responseJSON?.message || (error.status === 419
                        ? 'انتهت صلاحية الجلسة. حدّث الصفحة وحاول مرة أخرى.' : 'حدث خطأ أثناء الحذف.');
                window.toastr.error(message);
                return wasDeleted;
            } finally {
                deletionPending = false;
            }
        },
    });
};

window.confirmDeleteSelected = function (selectedIds, deleteUrl) {
    if (!selectedIds?.length) {
        window.toastr.warning('لم تقم بتحديد أي صفوف.');
        return;
    }
    return window.confirmRecordDelete({
        url: deleteUrl,
        method: 'POST',
        data: { ids: [...selectedIds] },
        text: 'هل أنت متأكد من رغبتك في حذف ' + selectedIds.length + ' عنصر محدد؟ لن تتمكن من التراجع!',
        confirmText: 'نعم، حذف المحدد',
        successMessage: 'تم حذف المحدد بنجاح.',
    });
};

window.confirmDeleteSelectedmss = function (selectedIds, deleteUrl, modelName) {
    if (!selectedIds?.length) {
        window.toastr.warning('لم تقم بتحديد أي صفوف.');
        return;
    }
    return window.confirmRecordDelete({
        url: deleteUrl,
        method: 'POST',
        data: { ids: selectedIds.join(','), model: modelName },
        text: 'هل أنت متأكد من رغبتك في حذف ' + selectedIds.length + ' عنصر محدد؟ لن تتمكن من التراجع!',
        confirmText: 'نعم، حذف المحدد',
        successMessage: 'تم حذف المحدد بنجاح.',
    });
};

// Delegation also covers rows inserted by a DataTable redraw.
document.addEventListener('click', function (event) {
    const button = event.target.closest('.js-delete-btn, .btn-delete');
    if (!button) return;
    event.preventDefault();
    const options = {
        table: button.closest('table'),
        title: button.dataset.deleteTitle,
        text: button.dataset.deleteMessage,
        confirmText: button.dataset.deleteConfirmText,
    };
    if (button.dataset.deleteUrl) {
        window.confirmRecordDelete({ ...options, url: button.dataset.deleteUrl });
    } else if (button.dataset.id) {
        window.confirmDelete(button.dataset.id, options);
    }
});
