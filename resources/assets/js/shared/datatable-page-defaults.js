"use strict";

/**
 * Shared DataTable Page Defaults
 * Provides global event delegation for:
 * 1. Checkbox selection (#select-all & .row-checkbox) with draw.dt resets
 * 2. Copyable code single-click clipboard copy (.copyable-code-wrapper)
 * 3. Unified mass delete helper (window.executeMassDelete)
 * 4. Automatic Select2 component initialization
 */

$(function () {
    // 1. Automatic Select2 initialization fallback
    if ($.fn.select2) {
        $(".select2")
            .not(".select2-hidden-accessible")
            .each(function () {
                const $this = $(this);
                if ($this.closest(".select2-container").length === 0) {
                    $this.select2({
                        placeholder: $this.data("placeholder") || "اختر",
                        allowClear: true,
                        width: "100%",
                        language: "ar",
                        dir: "rtl",
                    });
                }
            });
    }

    // 2. Event Delegation for Select-All Checkbox
    $(document).on(
        "click",
        ".dataTables_wrapper #select-all, [data-select-all]",
        function () {
            const isChecked = this.checked;
            const $wrapper = $(this).closest(".dataTables_wrapper");
            const $table = $wrapper.find("table.dataTable");
            const tableId = $table.attr("id");

            if (tableId && $.fn.DataTable.isDataTable("#" + tableId)) {
                const dt = $("#" + tableId).DataTable();
                $(".row-checkbox", dt.rows({ search: "applied" }).nodes()).prop(
                    "checked",
                    isChecked,
                );
            } else {
                $wrapper.find(".row-checkbox").prop("checked", isChecked);
            }
        },
    );

    // 3. Uncheck select-all if any single row-checkbox is unchecked
    $(document).on("change", ".dataTables_wrapper .row-checkbox", function () {
        if (!this.checked) {
            const $wrapper = $(this).closest(".dataTables_wrapper");
            $wrapper
                .find("#select-all, [data-select-all]")
                .prop("checked", false);
        }
    });

    // 4. Automatically reset select-all on table draw/page navigation
    $(document).on("draw.dt", function (e) {
        const $wrapper = $(e.target).closest(".dataTables_wrapper");
        $wrapper.find("#select-all, [data-select-all]").prop("checked", false);
    });

    // 5. Copyable Code Wrapper Single-Click Clipboard Copy
    $(document).on("click", ".copyable-code-wrapper", function () {
        const code = $(this).data("code") || $(this).text().trim();
        if (code && navigator.clipboard) {
            navigator.clipboard
                .writeText(code)
                .then(function () {
                    if (typeof toastr !== "undefined") {
                        toastr.success("تم النسخ إلى الحافظة: " + code);
                    }
                })
                .catch(function () {
                    // Fallback for non-secure contexts
                    const tempInput = document.createElement("input");
                    tempInput.value = code;
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand("copy");
                    document.body.removeChild(tempInput);
                    if (typeof toastr !== "undefined") {
                        toastr.success("تم النسخ إلى الحافظة: " + code);
                    }
                });
        }
    });

    // 6. Global Archive Force Delete Delegation (.js-force-delete)
    $(document).on("click", ".js-force-delete", function (e) {
        e.preventDefault();
        const $btn = $(this);
        const url = $btn.data("url") || $btn.attr("href");
        if (!url) return;

        const entityTitle = $btn.data("title") || "هذا السجل";
        const confirmMessage =
            $btn.data("message") ||
            "لن تتمكن من استرجاع " + entityTitle + " مجدداً!";

        window.confirmAction({
            title: "تأكيد الحذف النهائي",
            text: confirmMessage,
            variant: "danger",
            icon: "ti-trash",
            confirmText: "تأكيد الحذف نهائياً",
            confirmClass: "btn btn-danger px-4",
            onConfirm: function () {
                $.ajax({
                    url: url,
                    type: "DELETE",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr("content"),
                    },
                    success: function (response) {
                        if (response.success) {
                            if (typeof toastr !== "undefined") {
                                toastr.success(
                                    response.message ||
                                        "تم الحذف النهائي بنجاح",
                                );
                            }
                            reloadClosestDataTable($btn);
                        }
                    },
                    error: function (xhr) {
                        const errorMsg =
                            xhr.responseJSON?.message ||
                            "حدث خطأ أثناء الحذف النهائي";
                        if (typeof toastr !== "undefined") {
                            toastr.error(errorMsg);
                        }
                    },
                });
            },
        });
    });

    // 7. Global Archive Restore Delegation (.js-restore)
    $(document).on("click", ".js-restore", function (e) {
        e.preventDefault();
        const $btn = $(this);
        const url = $btn.data("url") || $btn.attr("href");
        if (!url) return;

        const entityTitle = $btn.data("title") || "هذا السجل";

        window.confirmAction({
            title: "تأكيد الاستعادة",
            text: "هل أنت متأكد من رغبتك في استعادة " + entityTitle + "؟",
            variant: "primary",
            icon: "ti-rotate-clockwise",
            confirmText: "نعم، استعادة",
            confirmClass: "btn btn-primary px-4",
            onConfirm: function () {
                $.ajax({
                    url: url,
                    type: "PATCH",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr("content"),
                    },
                    success: function (response) {
                        if (response.success) {
                            if (typeof toastr !== "undefined") {
                                toastr.success(
                                    response.message || "تمت الاستعادة بنجاح",
                                );
                            }
                            reloadClosestDataTable($btn);
                        }
                    },
                    error: function (xhr) {
                        const errorMsg =
                            xhr.responseJSON?.message ||
                            "حدث خطأ أثناء الاستعادة";
                        if (typeof toastr !== "undefined") {
                            toastr.error(errorMsg);
                        }
                    },
                });
            },
        });
    });
});

/**
 * Helper to reload the nearest active DataTable
 * @param {jQuery} [$element] - Element inside or near the target DataTable
 */
function reloadClosestDataTable($element) {
    if ($element && $element.length) {
        const $wrapper = $element.closest(".dataTables_wrapper");
        const $table = $wrapper.length
            ? $wrapper.find("table.dataTable")
            : $("table.dataTable");
        if ($table.length) {
            $table.each(function () {
                if ($.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable().ajax.reload(null, false);
                }
            });
            return;
        }
    }

    if (window.LaravelDataTables) {
        Object.values(window.LaravelDataTables).forEach(function (dt) {
            if (
                dt &&
                typeof dt.ajax === "object" &&
                typeof dt.ajax.reload === "function"
            ) {
                dt.ajax.reload(null, false);
            }
        });
    }
}

/**
 * Unified Mass Delete Helper
 * @param {string} [modelName] - Model name mapped in config/models.php
 * @param {string} [deleteUrl] - Mass delete endpoint (defaults to window.routes.massDelete)
 * @param {string} [emptyWarning] - Warning message if no rows selected
 */
window.executeMassDelete = function (modelName, deleteUrl, emptyWarning) {
    const selectedIds = [];
    $(".row-checkbox:checked").each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        if (typeof toastr !== "undefined") {
            toastr.warning(emptyWarning || "يرجى تحديد عنصر واحد على الأقل");
        }
        return;
    }

    const targetUrl = deleteUrl || (window.routes && window.routes.massDelete);
    if (!targetUrl) {
        console.error(
            "Mass delete URL is not defined in window.routes.massDelete",
        );
        return;
    }

    if (typeof confirmDeleteSelectedmss === "function") {
        confirmDeleteSelectedmss(selectedIds, targetUrl, modelName || "");
    } else {
        console.error("confirmDeleteSelectedmss function is not available.");
    }
};

if (typeof window.handleMassDelete !== "function") {
    window.handleMassDelete = function (modelName, deleteUrl, emptyWarning) {
        window.executeMassDelete(modelName, deleteUrl, emptyWarning);
    };
}
