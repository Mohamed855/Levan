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

const VARIANT_CONFIGS = {
    danger: {
        wrapperClass:
            "avatar avatar-xl mx-auto rounded-circle d-flex align-items-center justify-content-center bg-danger mb-3",
        iconClass: "ti ti-trash fs-2 text-danger",
        btnClass: "btn btn-danger px-4",
    },
    warning: {
        wrapperClass:
            "avatar avatar-xl mx-auto rounded-circle d-flex align-items-center justify-content-center bg-warning mb-3",
        iconClass: "ti ti-alert-triangle fs-2 text-warning",
        btnClass: "btn btn-warning px-4 text-white",
    },
    primary: {
        wrapperClass:
            "avatar avatar-xl mx-auto rounded-circle d-flex align-items-center justify-content-center bg-primary mb-3",
        iconClass: "ti ti-help fs-2 text-primary",
        btnClass: "btn btn-primary px-4",
    },
    success: {
        wrapperClass:
            "avatar avatar-xl mx-auto rounded-circle d-flex align-items-center justify-content-center bg-success mb-3",
        iconClass: "ti ti-check fs-2 text-success",
        btnClass: "btn btn-success px-4",
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
    if (cancelBtn) cancelBtn.textContent = cancelText;

    if (iconWrapper) iconWrapper.className = config.wrapperClass;
    if (iconEl) {
        if (icon) {
            let iconClasses = icon.trim();
            if (!iconClasses.includes("ti-") && !iconClasses.startsWith("ti")) {
                iconClasses = `ti ti-${iconClasses}`;
            } else if (!iconClasses.startsWith("ti")) {
                iconClasses = `ti ${iconClasses}`;
            }
            if (!iconClasses.includes("fs-")) {
                iconClasses += " fs-2";
            }
            if (!iconClasses.includes("text-")) {
                iconClasses += ` text-${variant}`;
            }
            iconEl.className = iconClasses;
        } else {
            iconEl.className = config.iconClass;
        }
    }

    if (submitBtn) {
        submitBtn.className = confirmClass || config.btnClass;
        submitBtn.textContent = confirmText;
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

    newSubmitBtn.addEventListener("click", function () {
        const val = inputEl ? inputEl.value : "";
        isConfirmed = true;
        bsModal.hide();
        if (typeof onConfirm === "function") {
            onConfirm(val);
        }
    });

    if (modalEl._confirmCancelHandler) {
        modalEl.removeEventListener(
            "hidden.bs.modal",
            modalEl._confirmCancelHandler,
        );
    }

    const handleHidden = function () {
        modalEl.removeEventListener("hidden.bs.modal", handleHidden);
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
 | confirmDelete — single item via hidden form submit
 -------------------------------------------------------------------------- */
window.confirmDelete = function (id) {
    window.confirmAction({
        title: "تأكيد الحذف",
        text: "هل أنت متأكد من عملية الحذف؟ لا يمكن التراجع عن هذا الإجراء!",
        variant: "danger",
        icon: "ti-trash",
        confirmText: "نعم، احذف",
        confirmClass: "btn btn-danger px-4",
        onConfirm: function () {
            const form = document.getElementById("delete-form-" + id);
            if (form) {
                form.submit();
            }
        },
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

/* --------------------------------------------------------------------------
 | confirmDeleteSelected — bulk delete via AJAX (no password)
 -------------------------------------------------------------------------- */
window.confirmDeleteSelected = function (selectedIds, deleteUrl) {
    if (!selectedIds || selectedIds.length === 0) {
        if (typeof toastr !== "undefined")
            toastr.warning("لم تقم بتحديد أي صفوف.");
        return;
    }

    window.confirmAction({
        title: "تأكيد الحذف",
        text: `هل أنت متأكد من رغبتك في حذف ${selectedIds.length} عنصر محدد؟ لن تتمكن من التراجع!`,
        variant: "danger",
        icon: "ti-trash",
        confirmText: "نعم، حذف المحدد",
        confirmClass: "btn btn-danger px-4",
        onConfirm: function () {
            $.ajax({
                url: deleteUrl,
                method: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr("content"),
                    ids: selectedIds,
                },
                success: function (response) {
                    if (response.success) {
                        if (typeof toastr !== "undefined")
                            toastr.success("تم حذف المحدد بنجاح.");
                        location.reload();
                    } else {
                        if (typeof toastr !== "undefined")
                            toastr.error(
                                response.message || "فشل في حذف المحدد.",
                            );
                    }
                },
                error: function (xhr) {
                    if (typeof toastr !== "undefined")
                        toastr.error(
                            xhr.responseJSON?.message ||
                                "حدث خطأ أثناء حذف المحدد.",
                        );
                },
            });
        },
    });
};

/* --------------------------------------------------------------------------
 | confirmDeleteSelectedmss — bulk delete via AJAX without password
 -------------------------------------------------------------------------- */
window.confirmDeleteSelectedmss = function (selectedIds, deleteUrl, modelName) {
    if (!selectedIds || selectedIds.length === 0) {
        if (typeof toastr !== "undefined")
            toastr.warning("لم تقم بتحديد أي صفوف.");
        return;
    }

    window.confirmAction({
        title: "تأكيد الحذف المحدد",
        text: `هل أنت متأكد من رغبتك في حذف ${selectedIds.length} عنصر؟ لن تتمكن من التراجع عن هذا الإجراء!`,
        variant: "danger",
        icon: "ti-trash",
        confirmText: "نعم، حذف المحدد",
        confirmClass: "btn btn-danger px-4",
        onConfirm: function () {
            if (typeof toastr !== "undefined")
                toastr.info("جاري تنفيذ الحذف...");
            $.ajax({
                url: deleteUrl,
                method: "POST",
                timeout: 60000,
                data: {
                    _token: $('meta[name="csrf-token"]').attr("content"),
                    ids: selectedIds.join(","),
                    model: modelName,
                },
                success: function (response) {
                    if (response.success) {
                        if (typeof toastr !== "undefined")
                            toastr.success(
                                response.message || "تم حذف المحدد بنجاح.",
                            );
                        setTimeout(function () {
                            location.reload();
                        }, 1200);
                    } else {
                        if (typeof toastr !== "undefined")
                            toastr.error(
                                response.message || "فشل في حذف المحدد.",
                            );
                    }
                },
                error: function (xhr) {
                    if (typeof toastr !== "undefined")
                        toastr.error(
                            xhr.responseJSON?.message || "حدث خطأ أثناء الحذف.",
                        );
                },
            });
        },
    });
};

/* --------------------------------------------------------------------------
 | Delete button — delegated click handler
 -------------------------------------------------------------------------- */
document.addEventListener("click", function (e) {
    var btn = e.target.closest(".js-delete-btn");
    if (!btn) return;
    var id = btn.dataset.id;
    if (!id) return;
    window.confirmDelete(id);
});
