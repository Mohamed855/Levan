"use strict";

import { initializeTableStatusToggles } from "../../shared/table-status-toggle";

$(function () {
    initializeTableStatusToggles();

    $(document).on(
        "click",
        "#subscriptions-table_wrapper #select-all, #select-all",
        function () {
            if (!$.fn.DataTable.isDataTable("#subscriptions-table")) return;
            const table = $("#subscriptions-table").DataTable();
            $(".row-checkbox", table.rows({ search: "applied" }).nodes()).prop(
                "checked",
                this.checked,
            );
        },
    );

    $(document).on(
        "change",
        "#subscriptions-table_wrapper .row-checkbox, .row-checkbox",
        function () {
            if (!$.fn.DataTable.isDataTable("#subscriptions-table")) return;
            const table = $("#subscriptions-table").DataTable();
            if (!this.checked) {
                $("#select-all").prop("checked", false);
            } else {
                const totalRows = $(
                    ".row-checkbox",
                    table.rows({ search: "applied" }).nodes(),
                ).length;
                const checkedRows = $(
                    ".row-checkbox:checked",
                    table.rows({ search: "applied" }).nodes(),
                ).length;
                $("#select-all").prop(
                    "checked",
                    totalRows > 0 && totalRows === checkedRows,
                );
            }
        },
    );

    $("#subscriptions-table").on("draw.dt", function () {
        $("#select-all").prop("checked", false);
        if (typeof bootstrap !== "undefined" && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(
                document.querySelectorAll(
                    '#subscriptions-table [data-bs-toggle="tooltip"]',
                ),
            );
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return bootstrap.Tooltip.getOrCreateInstance(tooltipTriggerEl);
            });
        }
    });

    document.addEventListener("table-status-toggled", () => {
        if ($.fn.DataTable.isDataTable("#subscriptions-table")) {
            $("#subscriptions-table").DataTable().ajax.reload(null, false);
        }
    });
});

function handleMassDelete() {
    const selectedIds = [];
    $(".row-checkbox:checked").each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        if (typeof toastr !== "undefined") {
            toastr.warning("يرجى تحديد اشتراك واحد على الأقل");
        }
        return;
    }

    if (typeof confirmDeleteSelectedmss === "function") {
        confirmDeleteSelectedmss(
            selectedIds,
            window.routes?.massDelete || "/admin/mass-delete",
            "Subscription",
        );
    }
}

window.handleMassDelete = handleMassDelete;
