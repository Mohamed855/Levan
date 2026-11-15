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

    $(document).on("click", ".js-sub-periods-btn", function () {
        const url = $(this).data("url");
        const number = $(this).data("number");

        $("#modalSubNumber").text(number || "-");
        $("#modalSubCompany").text("-");
        $("#modalSubPeriodsBody").html('<tr><td colspan="7" class="text-center py-3 text-muted">جارٍ تحميل البيانات...</td></tr>');

        const modalEl = document.getElementById("subPeriodsModal");
        if (modalEl && typeof bootstrap !== "undefined") {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }

        $.getJSON(url)
            .done(function (res) {
                if (!res.success || !res.periods || res.periods.length === 0) {
                    $("#modalSubPeriodsBody").html('<tr><td colspan="7" class="text-center py-3 text-muted">لا توجد فترات مسجلة لهذا الاشتراك.</td></tr>');
                    return;
                }

                $("#modalSubCompany").text(res.company_name || "-");

                let html = "";
                res.periods.forEach(function (p) {
                    const currentBadge = p.is_current ? ' <span class="badge bg-success ms-1">الحالية</span>' : '';
                    const queuedBadge = p.status === 'queued' ? ' <span class="badge bg-info ms-1">Pre-pay قادمة</span>' : '';
                    const rowClass = p.is_current ? 'table-primary-subtle' : '';

                    html += `<tr class="${rowClass}">
                        <td><span class="fw-bold">الفترة #${p.period_number}</span>${currentBadge}${queuedBadge}</td>
                        <td>${p.plan_name}</td>
                        <td>${p.period_start}</td>
                        <td>${p.period_end}</td>
                        <td><span class="badge bg-label-${p.status_color}">${p.status_label}</span></td>
                        <td>${p.used_users} / ${p.total_users}</td>
                        <td>${p.price} ر.س</td>
                    </tr>`;
                });

                $("#modalSubPeriodsBody").html(html);
            })
            .fail(function () {
                $("#modalSubPeriodsBody").html('<tr><td colspan="7" class="text-center py-3 text-danger">حدث خطأ أثناء تحميل الفترات.</td></tr>');
            });
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
