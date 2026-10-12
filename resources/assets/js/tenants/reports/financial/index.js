"use strict";

let financialReportsTable;
let exportProgressInterval;
let currentExportId = null;

$(document).ready(function () {
    initializeComponents();
    initializeCharts();
    bindEvents();
    initializeTableAfterRender();
});

/*
|--------------------------------------------------------------------------
| Initialize Components
|--------------------------------------------------------------------------
*/
function initializeComponents() {
    // تهيئة Select2 العادي
    $(".select2").each(function () {
        $(this).select2({
            placeholder: function () {
                return $(this).data("placeholder");
            },
            allowClear: true,
            width: "100%",
            language: {
                noResults: function () {
                    return "لا توجد نتائج";
                },
                searching: function () {
                    return "جاري البحث...";
                },
            },
            dir: "rtl",
        });
    });

    // تهيئة Select2 مع AJAX للعملاء
    $(".select2-ajax").select2({
        placeholder: function () {
            return $(this).data("placeholder");
        },
        allowClear: true,
        width: "100%",
        language: {
            noResults: function () {
                return "لا توجد نتائج";
            },
            searching: function () {
                return "جاري البحث...";
            },
        },
        dir: "rtl",
        minimumInputLength: 0,
        ajax: {
            url: function () {
                return window.routes.financialSearchCustomers;
            },
            dataType: "json",
            delay: 300,
            data: function (params) {
                return {
                    q: params.term || "",
                    page: params.page || 1,
                };
            },
            processResults: function (data) {
                return {
                    results: data,
                    pagination: {
                        more: data.length === 20,
                    },
                };
            },
            cache: true,
        },
    });
}

/*
|--------------------------------------------------------------------------
| Initialize Table After Render
|--------------------------------------------------------------------------
*/
function initializeTableAfterRender() {
    const checkTable = setInterval(function () {
        if (
            $("#financial-reports-table").length &&
            $.fn.DataTable.isDataTable("#financial-reports-table")
        ) {
            financialReportsTable = $("#financial-reports-table").DataTable();
            setupTableEvents();
            clearInterval(checkTable);
        }
    }, 500);
}

/*
|--------------------------------------------------------------------------
| Setup Table Events
|--------------------------------------------------------------------------
*/
function setupTableEvents() {
    if (!financialReportsTable) return;

    const tableWrapper = $("#financial-reports-table_wrapper");

    tableWrapper.on("click", "#select-all", function () {
        const rows = financialReportsTable.rows({ search: "applied" }).nodes();
        $("input.row-checkbox", rows).prop("checked", this.checked);
    });

    tableWrapper.on("change", ".row-checkbox", function () {
        if (!this.checked) {
            $("#select-all").prop("checked", false);
        }
    });

    financialReportsTable.on("draw", function () {
        $("#select-all").prop("checked", false);
    });
}

/*
|--------------------------------------------------------------------------
| Initialize Charts
|--------------------------------------------------------------------------
*/
function initializeCharts() {
    initializeStatusChart();
    initializeUserChart();
    initializeMonthlyChart();
}

/*
|--------------------------------------------------------------------------
| Generate Color Palette
|--------------------------------------------------------------------------
*/
function generateColorPalette(baseColor, count) {
    const colors = [];

    if (baseColor === "#4cc1bb") {
        // أزرق فيروزي بدرجات متفاوتة
        const baseR = 76,
            baseG = 193,
            baseB = 187;
        for (let i = 0; i < count; i++) {
            const factor = 1 - i * 0.15;
            const r = Math.round(baseR * factor);
            const g = Math.round(baseG * factor);
            const b = Math.round(baseB * factor);
            colors.push(`rgb(${r}, ${g}, ${b})`);
        }
    } else if (baseColor === "#372632") {
        // أزرق داكن بدرجات متفاوتة
        const baseR = 31,
            baseG = 42,
            baseB = 68;
        for (let i = 0; i < count; i++) {
            const factor = 1 - i * 0.12;
            const r = Math.round(baseR * factor);
            const g = Math.round(baseG * factor);
            const b = Math.round(baseB * factor);
            colors.push(`rgb(${r}, ${g}, ${b})`);
        }
    } else {
        // اللون الافتراضي
        for (let i = 0; i < count; i++) {
            const opacity = 1 - i * 0.15;
            colors.push(
                baseColor +
                    Math.round(opacity * 255)
                        .toString(16)
                        .padStart(2, "0"),
            );
        }
    }

    return colors;
}

/*
|--------------------------------------------------------------------------
| Initialize Status Chart
|--------------------------------------------------------------------------
*/
function initializeStatusChart() {
    const statusData = window.chartData.collectionsByStatus;

    if (!statusData || Object.keys(statusData).length === 0) {
        $("#transactionTypeChart").html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-chart-pie-off ti-24px mb-2 d-block"></i>
                <small>لا توجد بيانات للحالات</small>
            </div>
        `);
        return;
    }

    const labels = Object.keys(statusData).map(
        (key) => window.chartData.collectionStatusLabels[key] ?? key,
    );
    const series = Object.values(statusData);

    const totalValues = series.reduce(
        (sum, val) => sum + (parseFloat(val) || 0),
        0,
    );
    if (totalValues === 0) {
        $("#transactionTypeChart").html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-chart-pie-off ti-24px mb-2 d-block"></i>
                <small>لا توجد بيانات للحالات</small>
            </div>
        `);
        return;
    }

    const colors = ["#4cc1bb", "#372632", "#6fc8d3"];

    const options = {
        series: series,
        chart: {
            type: "donut",
            height: 280,
            fontFamily: "Cairo, sans-serif",
            toolbar: { show: false },
        },
        labels: labels,
        colors: colors,
        legend: {
            position: "bottom",
            fontSize: "12px",
            fontWeight: "500",
        },
        plotOptions: {
            pie: {
                donut: {
                    size: "65%",
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: "المجموع",
                            fontSize: "14px",
                            fontWeight: "600",
                            color: "#333",
                        },
                    },
                },
            },
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return Math.round(val) + "%";
            },
            style: {
                fontSize: "11px",
                fontWeight: "bold",
                colors: ["#fff"],
            },
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " تحصيل";
                },
            },
        },
    };

    const statusChart = new ApexCharts(
        document.querySelector("#transactionTypeChart"),
        options,
    );
    statusChart.render();
}

/*
|--------------------------------------------------------------------------
| Initialize User Chart
|--------------------------------------------------------------------------
*/
function initializeUserChart() {
    const userData = window.chartData.transactionsByUser;

    if (!userData || Object.keys(userData).length === 0) {
        $("#userChart").html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-user-off ti-24px mb-2 d-block"></i>
                <small>لا توجد بيانات للمستخدمين</small>
            </div>
        `);
        return;
    }

    const labels = Object.keys(userData);
    const series = Object.values(userData).map((val) => parseFloat(val) || 0);

    const totalValues = series.reduce((sum, val) => sum + val, 0);
    if (totalValues === 0) {
        $("#userChart").html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-user-off ti-24px mb-2 d-block"></i>
                <small>لا توجد بيانات للمستخدمين</small>
            </div>
        `);
        return;
    }

    const colors = generateColorPalette("#372632", labels.length);

    const options = {
        series: series,
        chart: {
            type: "donut",
            height: 280,
            fontFamily: "Cairo, sans-serif",
            toolbar: { show: false },
        },
        labels: labels,
        colors: colors,
        legend: {
            position: "bottom",
            fontSize: "12px",
            fontWeight: "500",
        },
        plotOptions: {
            pie: {
                donut: {
                    size: "65%",
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: "الإجمالي",
                            fontSize: "14px",
                            fontWeight: "600",
                            color: "#333",
                            formatter: function (val) {
                                return formatCurrency(totalValues);
                            },
                        },
                    },
                },
            },
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return Math.round(val) + "%";
            },
            style: {
                fontSize: "11px",
                fontWeight: "bold",
                colors: ["#fff"],
            },
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return formatCurrency(val);
                },
            },
        },
    };

    const userChart = new ApexCharts(
        document.querySelector("#userChart"),
        options,
    );
    userChart.render();
}

/*
|--------------------------------------------------------------------------
| Format Currency Helper
|--------------------------------------------------------------------------
*/
function formatCurrency(amount) {
    if (amount >= 1000000) {
        return (amount / 1000000).toFixed(1) + "م";
    } else if (amount >= 1000) {
        return (amount / 1000).toFixed(1) + "ألف";
    } else {
        return amount.toFixed(2) + "SAR";
    }
}

/*
|--------------------------------------------------------------------------
| Initialize Monthly Chart
|--------------------------------------------------------------------------
*/
function initializeMonthlyChart() {
    const monthlyData = window.chartData.transactionsByMonth;

    if (!monthlyData || Object.keys(monthlyData).length === 0) {
        $("#monthlyChart").html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-calendar-off ti-24px mb-2 d-block"></i>
                <small>لا توجد بيانات شهرية</small>
            </div>
        `);
        return;
    }

    const categories = Object.keys(monthlyData);
    const series = Object.values(monthlyData).map(
        (val) => parseFloat(val) || 0,
    );

    const totalAmount = series.reduce((sum, val) => sum + val, 0);
    if (totalAmount === 0) {
        $("#monthlyChart").html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-calendar-off ti-24px mb-2 d-block"></i>
                <small>لا توجد بيانات شهرية</small>
            </div>
        `);
        return;
    }

    const options = {
        series: [
            {
                name: "عدد المعاملات",
                data: series,
            },
        ],
        chart: {
            type: "bar",
            height: 280,
            fontFamily: "Cairo, sans-serif",
            toolbar: { show: false },
            background: "transparent",
        },
        plotOptions: {
            bar: {
                borderRadius: 8,
                columnWidth: "60%",
                dataLabels: {
                    position: "top",
                },
            },
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return val;
            },
            style: {
                fontSize: "12px",
                colors: ["#333"],
                fontWeight: "bold",
            },
            offsetY: -20,
        },
        colors: ["#4cc1bb"],
        grid: {
            show: true,
            borderColor: "#e0e0e0",
            strokeDashArray: 4,
            position: "back",
            xaxis: {
                lines: {
                    show: true,
                    color: "#f0f0f0",
                },
            },
            yaxis: {
                lines: {
                    show: true,
                    color: "#f0f0f0",
                },
            },
            padding: {
                top: 0,
                right: 0,
                bottom: 0,
                left: 0,
            },
        },
        xaxis: {
            categories: categories,
            labels: {
                style: {
                    colors: "#666",
                    fontSize: "12px",
                    fontWeight: "500",
                },
                rotate: -45,
                offsetY: 5,
            },
            axisBorder: {
                show: false,
            },
            axisTicks: {
                show: false,
            },
        },
        yaxis: {
            labels: {
                style: {
                    colors: "#666",
                    fontSize: "12px",
                    fontWeight: "500",
                },
                formatter: function (val) {
                    return Math.floor(val);
                },
            },
            axisBorder: {
                show: false,
            },
            axisTicks: {
                show: false,
            },
            min: 0,
        },
        tooltip: {
            enabled: true,
            shared: true,
            intersect: false,
            followCursor: true,
            custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                const value = series[seriesIndex][dataPointIndex];
                const month = categories[dataPointIndex];
                return `<div class="apexcharts-tooltip-title" style="font-family: Cairo; font-size: 13px; font-weight: bold; color: #fff; background: #372632; padding: 5px 10px; border-radius: 4px;">${month}</div>
                        <div class="apexcharts-tooltip-value" style="font-family: Cairo; font-size: 12px; color: #fff; background: #4cc1bb; padding: 5px 10px; border-radius: 4px; margin-top: 2px;">${value} معاملة</div>`;
            },
            style: {
                fontSize: "13px",
                fontFamily: "Cairo, sans-serif",
            },
            marker: {
                show: false,
            },
        },
        responsive: [
            {
                breakpoint: 768,
                options: {
                    plotOptions: {
                        bar: {
                            columnWidth: "80%",
                        },
                    },
                    dataLabels: {
                        offsetY: -15,
                        style: {
                            fontSize: "10px",
                        },
                    },
                },
            },
        ],
    };

    const monthlyChart = new ApexCharts(
        document.querySelector("#monthlyChart"),
        options,
    );
    monthlyChart.render();
}

/*
|--------------------------------------------------------------------------
| Bind Events
|--------------------------------------------------------------------------
*/
function bindEvents() {
    $("#filter-company").on("change", function () {
        const companyId = $(this).val();
        const portfolioSelect = $("#filter-portfolio");

        portfolioSelect.val("").trigger("change.select2");

        if (companyId) {
            portfolioSelect.html('<option value="">جاري التحميل...</option>');
            portfolioSelect.prop("disabled", true);

            $.ajax({
                url: window.routes.financialPortfoliosByCompany,
                type: "GET",
                data: { company_id: companyId },
                success: function (data) {
                    portfolioSelect.empty();

                    if (data && data.length > 0) {
                        portfolioSelect.append(
                            '<option value="">اختر المحفظة</option>',
                        );
                        $.each(data, function (index, portfolio) {
                            portfolioSelect.append(
                                '<option value="' +
                                    portfolio.id +
                                    '">' +
                                    portfolio.name +
                                    "</option>",
                            );
                        });
                        portfolioSelect.prop("disabled", false);
                    } else {
                        portfolioSelect.append(
                            '<option value="" disabled>لا توجد محافظ لهذه الشركة</option>',
                        );
                        portfolioSelect.prop("disabled", true);
                    }
                },
                error: function () {
                    portfolioSelect.empty();
                    portfolioSelect.append(
                        '<option value="" disabled>حدث خطأ في التحميل</option>',
                    );
                    portfolioSelect.prop("disabled", true);
                    toastr.error("حدث خطأ أثناء تحميل المحافظ");
                },
            });
        } else {
            portfolioSelect.empty();
            portfolioSelect.append(
                '<option value="" disabled>اختر المحفظة</option>',
            );
            portfolioSelect.prop("disabled", true);
        }
    });

    $(document).on("datatable-filter:reset", function (e, data) {
        if (data && data.tableSelector === "#financial-reports-table") {
            const portfolioSelect = $("#filter-portfolio");
            portfolioSelect
                .empty()
                .append('<option value="" disabled>اختر الشركة أولاً</option>')
                .prop("disabled", true);
        }
    });

    $(document).on("click", ".btn-export-pdf", function () {
        exportCustomReportToPDF();
    });

    $("#filtersSection").on("show.bs.collapse", function () {
        $('[data-bs-target="#filtersSection"] i.ti-chevron-down')
            .removeClass("ti-chevron-down")
            .addClass("ti-chevron-up");
    });

    $("#filtersSection").on("hide.bs.collapse", function () {
        $('[data-bs-target="#filtersSection"] i.ti-chevron-up')
            .removeClass("ti-chevron-up")
            .addClass("ti-chevron-down");
    });
}

/*
|--------------------------------------------------------------------------
| Get Current Filters As Params
|--------------------------------------------------------------------------
*/
function getCurrentFiltersAsParams() {
    const filters = getCurrentFilters();
    const params = new URLSearchParams();

    Object.keys(filters).forEach((key) => {
        if (filters[key] && filters[key] !== "") {
            params.append(key, filters[key]);
        }
    });

    return params.toString();
}

/*
|--------------------------------------------------------------------------
| Get Current Filters
|--------------------------------------------------------------------------
*/
function getCurrentFilters() {
    return {
        date_from: $("#filter-date-from").val(),
        date_to: $("#filter-date-to").val(),
        created_from: $("#filter-created-from").val(),
        created_to: $("#filter-created-to").val(),
        customer_id: $("#filter-customer").val(),
        status: $("#filter-status").val(),
        company_id: $("#filter-company").val(),
        portfolio_id: $("#filter-portfolio").val(),
        created_by: $("#filter-created-by").val(),
        amount_from: $("#filter-amount-from").val(),
        amount_to: $("#filter-amount-to").val(),
        search_query: $("#filter-search-query").val(),
    };
}

/*
|--------------------------------------------------------------------------
| Export Custom Report To PDF
|--------------------------------------------------------------------------
*/
function exportCustomReportToPDF() {
    const filters = getCurrentFilters();

    toastr.info("جاري تحضير تقرير PDF...");

    const form = $("<form>", {
        method: "POST",
        action: window.routes.financialReportsExportPDF,
        target: "_blank",
    });

    form.append(
        $("<input>", {
            type: "hidden",
            name: "_token",
            value: window.csrfToken,
        }),
    );

    $.each(filters, function (key, value) {
        if (value && value !== "") {
            form.append(
                $("<input>", {
                    type: "hidden",
                    name: key,
                    value: value,
                }),
            );
        }
    });

    form.appendTo("body").submit().remove();
}

/*
|--------------------------------------------------------------------------
| Export Filtered To Excel
|--------------------------------------------------------------------------
*/
// function exportFilteredToExcel() {
//     const filters = getCurrentFilters();

//     $.ajax({
//         url: window.routes.financialReportsShowExportModal,
//         type: 'POST',
//         data: {
//             _token: window.csrfToken,
//             ...filters
//         },
//         success: function (response) {
//             if (response.success) {
//                 showExportFieldsModal(response.fields, response.count, filters, response.is_large_export);
//             }
//         },
//         error: function () {
//             toastr.error('حدث خطأ أثناء جلب بيانات التصدير');
//         }
//     });
// }

/*
|--------------------------------------------------------------------------
| Export Filtered To Excel - مع فحص تاريخ التحصيل أو الإنشاء
|--------------------------------------------------------------------------
*/
function exportFilteredToExcel() {
    const filters = getCurrentFilters();

    const hasCollectionDateRange = filters.date_from && filters.date_to;
    const hasCreatedDateRange = filters.created_from && filters.created_to;

    if (!hasCollectionDateRange && !hasCreatedDateRange) {
        toastr.error(
            "يرجى تحديد نطاق تاريخ واحد على الأقل (تاريخ التحصيل أو تاريخ الإنشاء)",
        );
        return;
    }

    if (hasCollectionDateRange) {
        const collectionDateFrom = new Date(filters.date_from);
        const collectionDateTo = new Date(filters.date_to);
        const collectionDiffTime = Math.abs(
            collectionDateTo - collectionDateFrom,
        );
        const collectionDiffDays = Math.ceil(
            collectionDiffTime / (1000 * 60 * 60 * 24),
        );

        if (collectionDiffDays > 31) {
            toastr.error(
                "نطاق تاريخ التحصيل يتجاوز 31 يوم. الرجاء تحديد فترة أقصر",
            );
            return;
        }
    }

    if (hasCreatedDateRange) {
        const createdDateFrom = new Date(filters.created_from);
        const createdDateTo = new Date(filters.created_to);
        const createdDiffTime = Math.abs(createdDateTo - createdDateFrom);
        const createdDiffDays = Math.ceil(
            createdDiffTime / (1000 * 60 * 60 * 24),
        );

        if (createdDiffDays > 31) {
            toastr.error(
                "نطاق تاريخ الإنشاء يتجاوز 31 يوم. الرجاء تحديد فترة أقصر",
            );
            return;
        }
    }

    $.ajax({
        url: window.routes.financialReportsShowExportModal,
        type: "POST",
        data: {
            _token: window.csrfToken,
            ...filters,
        },
        success: function (response) {
            if (response.success) {
                showExportFieldsModal(
                    response.fields,
                    response.count,
                    filters,
                    response.is_large_export,
                );
            }
        },
        error: function () {
            toastr.error("حدث خطأ أثناء جلب بيانات التصدير");
        },
    });
}

/*
|--------------------------------------------------------------------------
| Show Export Fields Modal
|--------------------------------------------------------------------------
*/
function showExportFieldsModal(fields, count, filters, isLargeExport) {
    const allFields = fields.fields;
    let modalContent = `
        <div class="alert alert-info py-2 mb-3">
            <small>
                <strong>عدد السجلات:</strong> ${count.toLocaleString()} تحصيل
                <span class="mx-2">|</span>
                <span id="selectedFieldsCount">0</span> حقل محدد
            </small>
        </div>
        
        <div class="text-center mb-3">
            <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2 mx-1" id="selectAllFields">
                تحديد الكل
            </button>
            <button type="button" class="btn btn-secondary btn-sm py-1 px-2 mx-1" id="deselectAllFields">
                إلغاء التحديد
            </button>
            <button type="button" class="btn btn-outline-info btn-sm py-1 px-2 mx-1" id="selectDefaultFields">
                الافتراضية
            </button>
        </div>
        
        <div class="card">
            <div class="card-header py-2" data-bs-toggle="collapse" data-bs-target="#fieldsCollapse" aria-expanded="false" style="cursor: pointer;">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">الحقول المتاحة</h6>
                    <div>
                        <span class="badge bg-secondary" id="allFieldsCount">0/${allFields.length}</span>
                        <i class="ti ti-chevron-down ms-2"></i>
                    </div>
                </div>
            </div>
            <div class="collapse" id="fieldsCollapse">
                <div class="card-body py-3">
                    <div class="row g-2">
                        ${generateFieldsGrid(allFields)}
                    </div>
                </div>
            </div>
        </div>
    `;

    $("#fieldsContainer").html(modalContent);
    $("#exportFieldsModal").modal("show");

    updateFieldsCount();
    bindExportModalEvents(filters, isLargeExport);

    $("#fieldsCollapse").on("show.bs.collapse", function () {
        $(".ti-chevron-down")
            .removeClass("ti-chevron-down")
            .addClass("ti-chevron-up");
    });

    $("#fieldsCollapse").on("hide.bs.collapse", function () {
        $(".ti-chevron-up")
            .removeClass("ti-chevron-up")
            .addClass("ti-chevron-down");
    });
}

/*
|--------------------------------------------------------------------------
| Generate Fields Grid
|--------------------------------------------------------------------------
*/
function generateFieldsGrid(fields) {
    let html = "";

    fields.forEach((field, index) => {
        html += `
            <div class="col-md-4 col-sm-6">
                <div class="form-check">
                    <input class="form-check-input export-field" 
                           type="checkbox" 
                           value="${field.key}" 
                           id="field_${field.key}"
                           ${field.selected ? "checked" : ""}>
                    <label class="form-check-label" 
                           for="field_${field.key}">
                        ${field.label}
                    </label>
                </div>
            </div>
        `;
    });

    return html;
}

/*
|--------------------------------------------------------------------------
| Bind Export Modal Events
|--------------------------------------------------------------------------
*/
function bindExportModalEvents(filters, isLargeExport) {
    $("#selectAllFields")
        .off("click")
        .on("click", function () {
            $(".export-field").prop("checked", true);
            updateFieldsCount();
        });

    $("#deselectAllFields")
        .off("click")
        .on("click", function () {
            $(".export-field").prop("checked", false);
            updateFieldsCount();
        });

    $("#selectDefaultFields")
        .off("click")
        .on("click", function () {
            $(".export-field").prop("checked", false);
            const defaultFields = [
                "customer_name",
                "customer_account",
                "collected_amount",
                "payment_method",
                "status",
                "collection_date",
                "created_at",
            ];
            defaultFields.forEach((key) => {
                $(`#field_${key}`).prop("checked", true);
            });
            updateFieldsCount();
        });

    $(".export-field").on("change", function () {
        updateFieldsCount();
    });

    $("#confirmExport")
        .off("click")
        .on("click", function () {
            const selectedFields = [];
            $(".export-field:checked").each(function () {
                selectedFields.push($(this).val());
            });

            if (selectedFields.length === 0) {
                toastr.warning("يرجى اختيار حقل واحد على الأقل");
                return;
            }

            $("#exportFieldsModal").modal("hide");
            executeExportWithFields(filters, selectedFields, isLargeExport);
        });
}

/*
|--------------------------------------------------------------------------
| Update Fields Count
|--------------------------------------------------------------------------
*/
function updateFieldsCount() {
    const totalChecked = $(".export-field:checked").length;
    $("#selectedFieldsCount").text(totalChecked);

    const allTotal = $(".export-field").length;
    $("#allFieldsCount").text(`${totalChecked}/${allTotal}`);

    const btnText = totalChecked > 0 ? `تصدير (${totalChecked} حقل)` : "تصدير";
    $("#confirmExport").text(btnText);
}

/*
|--------------------------------------------------------------------------
| Execute Export With Fields
|--------------------------------------------------------------------------
*/
function executeExportWithFields(filters, selectedFields, isLargeExport) {
    if (isLargeExport) {
        showAsyncExportModal();
    } else {
        showLoadingModal("جاري إنشاء ملف Excel...");
    }

    $.ajax({
        url: window.routes.financialReportsExportExcel,
        type: "POST",
        data: {
            _token: window.csrfToken,
            selected_fields: selectedFields,
            ...filters,
        },
        success: function (response) {
            if (response.success) {
                if (response.is_async) {
                    handleAsyncExport(response.export_id, response.message);
                } else {
                    hideLoadingModal();
                    window.open(response.download_url, "_blank");
                    toastr.success(response.message);
                }
            }
        },
        error: function (xhr) {
            hideLoadingModal();
            hideAsyncExportModal();
            const errorMsg =
                xhr.responseJSON?.message || "حدث خطأ أثناء إنشاء الملف";
            toastr.error(errorMsg);
        },
    });
}

/*
|--------------------------------------------------------------------------
| Handle Async Export
|--------------------------------------------------------------------------
*/
function handleAsyncExport(exportId, message) {
    currentExportId = exportId;
    hideLoadingModal();

    updateAsyncExportStatus(0, message);

    exportProgressInterval = setInterval(() => {
        checkExportProgress(exportId);
    }, 2000);

    toastr.info(message, "", { timeOut: 8000 });
}

/*
|--------------------------------------------------------------------------
| Check Export Progress
|--------------------------------------------------------------------------
*/
function checkExportProgress(exportId) {
    $.ajax({
        url: window.routes.financialReportsCheckProgress,
        type: "POST",
        data: {
            _token: window.csrfToken,
            export_id: exportId,
        },
        success: function (response) {
            if (response.success && response.data) {
                const data = response.data;

                if (data.status === "completed") {
                    clearInterval(exportProgressInterval);
                    hideAsyncExportModal();

                    showCompletedExportModal(data);
                    toastr.success("تم إنشاء الملف بنجاح!");
                } else if (data.status === "failed") {
                    clearInterval(exportProgressInterval);
                    hideAsyncExportModal();
                    toastr.error(data.error || "فشل في إنشاء الملف");
                } else if (data.status === "processing") {
                    updateAsyncExportStatus(
                        data.percentage || 0,
                        data.message || "جاري المعالجة...",
                    );
                }
            }
        },
        error: function () {
            clearInterval(exportProgressInterval);
            hideAsyncExportModal();
            toastr.error("حدث خطأ أثناء تتبع التقدم");
        },
    });
}

/*
|--------------------------------------------------------------------------
| Update Async Export Status
|--------------------------------------------------------------------------
*/
function updateAsyncExportStatus(percentage, message) {
    const safePercentage = Math.min(Math.max(percentage, 0), 100);
    const roundedPercentage = Math.round(safePercentage);

    $("#exportProgress")
        .css("width", safePercentage + "%")
        .attr("aria-valuenow", safePercentage);

    $("#progressText").text(roundedPercentage + "%");
    $("#exportStatus").text(message);

    if (safePercentage >= 100) {
        $(".spinner-border")
            .removeClass("spinner-border")
            .addClass("ti ti-check-circle text-success");
    }
}

/*
|--------------------------------------------------------------------------
| Hide Async Export Modal
|--------------------------------------------------------------------------
*/
function hideAsyncExportModal() {
    $("#asyncExportModal").modal("hide");
}

/*
|--------------------------------------------------------------------------
| Show Async Export Modal
|--------------------------------------------------------------------------
*/
function showAsyncExportModal() {
    const modalHtml = `
        <div class="modal fade" id="asyncExportModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="ti ti-file-export me-2 text-primary"></i>
                            جاري التصدير
                        </h5>
                    </div>
                    <div class="modal-body text-center">
                        <div class="progress mb-3 position-relative" style="height: 25px; border-radius: 12px; overflow: hidden;">
                            <div id="exportProgress" 
                                 class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                 role="progressbar" 
                                 style="width: 0%; transition: width 0.6s ease;"
                                 aria-valuenow="0" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                            <div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center">
                                <span id="progressText" class="text-white fw-bold" style="font-size: 12px;">
                                    0%
                                </span>
                            </div>
                        </div>
                        
                        <div id="exportStatus" class="text-muted mb-2" style="font-size: 14px;">
                            بدء عملية التصدير...
                        </div>
                        
                        <div class="d-flex justify-content-center align-items-center mt-3">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            <small class="text-primary fw-medium">
                                الرجاء الانتظار حتى تحضير الملف
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    if ($("#asyncExportModal").length === 0) {
        $("body").append(modalHtml);
    }

    $("#asyncExportModal").modal("show");
}

/*
|--------------------------------------------------------------------------
| Show Completed Export Modal
|--------------------------------------------------------------------------
*/
function showCompletedExportModal(data) {
    const downloadUrl = data.download_url || "#";

    const modalHtml = `
        <div class="modal fade" id="completedExportModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-success">
                            <i class="ti ti-check-circle me-2"></i>تم إنشاء الملف بنجاح
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <i class="ti ti-file-spreadsheet text-success" style="font-size: 64px;"></i>
                                <div class="position-absolute top-0 end-0 bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;">
                                    <i class="ti ti-check text-white" style="font-size: 12px;"></i>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mb-3">
                            <div class="col-4 text-center">
                                <div class="p-3 rounded border">
                                    <i class="ti ti-database text-primary d-block mb-2" style="font-size: 24px;"></i>
                                    <small class="text-muted d-block">السجلات</small>
                                    <strong>${data.total_records?.toLocaleString() || "غير محدد"}</strong>
                                </div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="p-3 rounded border">
                                   <i class="ti ti-archive text-warning d-block mb-2" style="font-size: 24px;"></i>
                                    <small class="text-muted d-block">الحجم</small>
                                    <strong>${data.file_size || "غير محدد"}</strong>
                                </div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="p-3 rounded border">
                                    <i class="ti ti-clock text-success d-block mb-2" style="font-size: 24px;"></i>
                                    <small class="text-muted d-block">التاريخ</small>
                                    <strong class="small">${formatDateTime(data.created_at) || "الآن"}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                        <a href="${downloadUrl}" class="btn btn-success" target="_blank">
                            <i class="ti ti-download me-1"></i>تحميل الملف
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;

    if ($("#completedExportModal").length) {
        $("#completedExportModal").remove();
    }

    $("body").append(modalHtml);
    $("#completedExportModal").modal("show");
}

/*
|--------------------------------------------------------------------------
| Format DateTime Helper
|--------------------------------------------------------------------------
*/
function formatDateTime(dateTime) {
    if (!dateTime) return null;
    try {
        const date = new Date(dateTime);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        const hours = String(date.getHours()).padStart(2, "0");
        const minutes = String(date.getMinutes()).padStart(2, "0");

        return `${year}-${month}-${day} ${hours}:${minutes}`;
    } catch (e) {
        return dateTime;
    }
}

/*
|--------------------------------------------------------------------------
| Show Loading Modal
|--------------------------------------------------------------------------
*/
function showLoadingModal(message = "جاري المعالجة...") {
    $("#loadingMessage").text(message);
    $("#loadingModal").modal("show");
}

/*
|--------------------------------------------------------------------------
| Hide Loading Modal
|--------------------------------------------------------------------------
*/
function hideLoadingModal() {
    $("#loadingModal").modal("hide");
}

/*
|--------------------------------------------------------------------------
| Debounce Function
|--------------------------------------------------------------------------
*/
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

window.exportCustomReportToPDF = exportCustomReportToPDF;
window.exportFilteredToExcel = exportFilteredToExcel;
