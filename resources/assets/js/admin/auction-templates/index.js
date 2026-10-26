"use strict";

import { initializeTableStatusToggles } from "../../shared/table-status-toggle";

$(document).ready(function () {
    initializeTableStatusToggles();

    $("#auction-templates-table").on("init.dt", function () {
        bindTableEvents();
    });

    bindFieldsModal();
});

function bindTableEvents() {
    const table = $("#auction-templates-table").DataTable();
    const tableWrapper = $("#auction-templates-table_wrapper");

    // Select all
    tableWrapper.on("click", "#select-all", function () {
        $(".row-checkbox", table.rows({ search: "applied" }).nodes()).prop(
            "checked",
            this.checked,
        );
    });

    tableWrapper.on("change", ".row-checkbox", function () {
        if (!this.checked) {
            $("#select-all").prop("checked", false);
        }
    });

    // Uncheck on draw
    $("#auction-templates-table").on("draw.dt", function () {
        $("#select-all").prop("checked", false);
    });
}

function bindFieldsModal() {
    $(document).on("click", ".js-view-template-fields", function () {
        const url = $(this).data("url");
        const modal = $("#templateFieldsModal");
        const tbody = $("#modal-fields-tbody");
        const extraContainer = $("#modal-extra-fields");

        tbody.html(
            '<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>',
        );
        extraContainer.addClass("d-none").html("");

        $.ajax({
            url: url,
            method: "GET",
            success: function (res) {
                $("#modalTemplateName").text(
                    res.template.name + " (" + res.template.type + ")",
                );
                $("#modalTotalFields").text(res.template.fields_count);

                let rows = "";
                res.comparison.forEach(function (f) {
                    const statusBadge = f.found
                        ? '<span class="badge bg-success"><i class="ti ti-check me-1"></i>متطابق وموجود</span>'
                        : f.required
                          ? '<span class="badge bg-danger"><i class="ti ti-alert-triangle me-1"></i>مطلوب وغير موجود</span>'
                          : '<span class="badge bg-secondary">اختياري وغير متوفر</span>';

                    rows += `
                        <tr>
                            <td><code>${f.name}</code></td>
                            <td>${f.label}</td>
                            <td class="text-center">${f.required ? '<span class="badge bg-warning">إلزامي</span>' : '<span class="badge bg-info">اختياري</span>'}</td>
                            <td class="text-center">${statusBadge}</td>
                        </tr>
                    `;
                });

                tbody.html(rows);

                if (res.unexpected && res.unexpected.length > 0) {
                    let tags = res.unexpected
                        .map(
                            (u) =>
                                `<span class="badge bg-dark me-1 mb-1 font-monospace">${u}</span>`,
                        )
                        .join("");
                    extraContainer.removeClass("d-none").html(`
                        <div class="alert alert-info mb-0">
                            <h6 class="alert-heading fw-bold mb-2"><i class="ti ti-info-circle me-1"></i>حقول إضافية مستخرجة من الـ PDF غير محددة بالنموذج القياسي:</h6>
                            <div class="d-flex flex-wrap">${tags}</div>
                        </div>
                    `);
                }
            },
            error: function () {
                tbody.html(
                    '<tr><td colspan="4" class="text-center text-danger py-3">فشل جلب بيانات الحقول.</td></tr>',
                );
            },
        });
    });
}

function handleMassDelete() {
    const selectedIds = [];
    $(".row-checkbox:checked").each(function () {
        selectedIds.push($(this).val());
    });

    if (selectedIds.length === 0) {
        toastr.warning("يرجى تحديد قالب واحد على الأقل");
        return;
    }

    if (typeof confirmDeleteSelectedmss === "function") {
        confirmDeleteSelectedmss(
            selectedIds,
            window.routes.massDelete,
            "AuctionTemplate",
        );
    }
}

window.handleMassDelete = handleMassDelete;
