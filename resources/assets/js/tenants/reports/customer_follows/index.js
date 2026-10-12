"use strict";

let customerFollowsReportsTable;
let exportProgressInterval = null;
let currentExportId = null;
let exportNotFoundCount = 0;
let exportTotalParts = 1;
let lastKnownCompletedFiles = [];

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
                if ($(this).closest("#filtersSection").length) {
                    return window.routes.customerFollowsSearchCustomers;
                }
                return window.routes.customerFollowsSearchCustomers;
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
            $("#customer-follows-reports-table").length &&
            $.fn.DataTable.isDataTable("#customer-follows-reports-table")
        ) {
            customerFollowsReportsTable = $(
                "#customer-follows-reports-table",
            ).DataTable();
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
    if (!customerFollowsReportsTable) return;

    const tableWrapper = $("#customer-follows-reports-table_wrapper");

    tableWrapper.on("click", "#select-all", function () {
        const rows = customerFollowsReportsTable
            .rows({ search: "applied" })
            .nodes();
        $("input.row-checkbox", rows).prop("checked", this.checked);
    });

    tableWrapper.on("change", ".row-checkbox", function () {
        if (!this.checked) {
            $("#select-all").prop("checked", false);
        }
    });

    customerFollowsReportsTable.on("draw", function () {
        $("#select-all").prop("checked", false);
    });
}

/*
|--------------------------------------------------------------------------
| Initialize Charts
|--------------------------------------------------------------------------
*/
function initializeCharts() {
    initializeContactMethodChart();
    initializeContactResultChart();
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
| Initialize Contact Method Chart
|--------------------------------------------------------------------------
*/
function initializeContactMethodChart() {
    const contactMethodData = window.chartData.contactMethodData;

    if (!contactMethodData || Object.keys(contactMethodData).length === 0) {
        $("#contactMethodChart").html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-phone-off ti-24px mb-2 d-block"></i>
                <small>لا توجد بيانات لطرق الاتصال</small>
            </div>
        `);
        return;
    }

    const labels = Object.keys(contactMethodData);
    const series = Object.values(contactMethodData);

    const totalValues = series.reduce(
        (sum, val) => sum + (parseFloat(val) || 0),
        0,
    );
    if (totalValues === 0) {
        $("#contactMethodChart").html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-phone-off ti-24px mb-2 d-block"></i>
                <small>لا توجد بيانات لطرق الاتصال</small>
            </div>
        `);
        return;
    }

    const colors = generateColorPalette("#4cc1bb", labels.length);

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
                    return val + " متابعة";
                },
            },
        },
    };

    const contactMethodChart = new ApexCharts(
        document.querySelector("#contactMethodChart"),
        options,
    );
    contactMethodChart.render();
}

/*
|--------------------------------------------------------------------------
| Initialize Contact Result Chart
|--------------------------------------------------------------------------
*/
function initializeContactResultChart() {
    const contactResultData = window.chartData.contactResultData;

    if (!contactResultData || Object.keys(contactResultData).length === 0) {
        $("#contactResultChart").html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-phone-x ti-24px mb-2 d-block"></i>
                <small>لا توجد بيانات لنتائج الاتصال</small>
            </div>
        `);
        return;
    }

    const labels = Object.keys(contactResultData);
    const series = Object.values(contactResultData);

    const totalValues = series.reduce(
        (sum, val) => sum + (parseFloat(val) || 0),
        0,
    );
    if (totalValues === 0) {
        $("#contactResultChart").html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-phone-x ti-24px mb-2 d-block"></i>
                <small>لا توجد بيانات لنتائج الاتصال</small>
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
                    return val + " متابعة";
                },
            },
        },
    };

    const contactResultChart = new ApexCharts(
        document.querySelector("#contactResultChart"),
        options,
    );
    contactResultChart.render();
}

/*
|--------------------------------------------------------------------------
| Initialize User Chart
|--------------------------------------------------------------------------
*/
function initializeUserChart() {
    const userData = window.chartData.userData;

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
                    return val + " متابعة";
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
| Initialize Monthly Chart
|--------------------------------------------------------------------------
*/
function initializeMonthlyChart() {
    const monthlyData = window.chartData.monthlyData;

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
                name: "عدد المتابعات",
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
                        <div class="apexcharts-tooltip-value" style="font-family: Cairo; font-size: 12px; color: #fff; background: #4cc1bb; padding: 5px 10px; border-radius: 4px; margin-top: 2px;">${value} متابعة</div>`;
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
                url: window.routes.customerFollowsPortfoliosByCompany,
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
        if (data && data.tableSelector === "#customer-follows-reports-table") {
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
        next_contact_from: $("#filter-next-contact-from").val(),
        next_contact_to: $("#filter-next-contact-to").val(),
        customer_id: $("#filter-customer").val(),
        company_id: $("#filter-company").val(),
        portfolio_id: $("#filter-portfolio").val(),
        contact_method_id: $("#filter-contact-method").val(),
        customer_status_id: $("#filter-customer-status").val(),
        contact_result_id: $("#filter-contact-result").val(),
        created_by: $("#filter-created-by").val(),
        search_query: $("#filter-search-query").val(),
        // تم حذف has_upcoming
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
        action: window.routes.customerFollowsReportsExportPDF,
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
//         url: window.routes.customerFollowsReportsShowExportModal,
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
| Export Filtered To Excel
|--------------------------------------------------------------------------
*/
function exportFilteredToExcel() {
    const filters = getCurrentFilters();

    if (!filters.date_from || !filters.date_to) {
        toastr.error("يرجى تحديد تاريخ المتابعة (من - إلى) قبل التصدير");
        return;
    }

    const dateFrom = new Date(filters.date_from);
    const dateTo = new Date(filters.date_to);
    const diffTime = Math.abs(dateTo - dateFrom);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays > 31) {
        toastr.error(
            "لا يمكن تصدير أكثر من شهر واحد. الرجاء تحديد فترة أقل من 31 يوم",
        );
        return;
    }

    $.ajax({
        url: window.routes.customerFollowsReportsShowExportModal,
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
                <strong>عدد السجلات:</strong> ${count.toLocaleString()} متابعة
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
                "customer_account_number",
                "customer_identity_number",
                "company_name",
                "portfolio_name",
                "contact_method",
                "contact_result",
                "customer_status",
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
    if (!isLargeExport) {
        showLoadingModal("جاري إنشاء ملف Excel...");
    }

    $.ajax({
        url: window.routes.customerFollowsReportsExportExcel,
        type: "POST",
        data: {
            _token: window.csrfToken,
            selected_fields: selectedFields,
            ...filters,
        },
        success: function (response) {
            if (response.success) {
                if (response.is_async) {
                    handleAsyncExport(
                        response.export_id,
                        response.total_parts || 1,
                        response.count || 0,
                        response.message,
                    );
                } else {
                    hideLoadingModal();
                    window.open(response.download_url, "_blank");
                    toastr.success(response.message);
                }
            } else {
                hideLoadingModal();
                toastr.error(response.message || "حدث خطأ أثناء إنشاء الملف");
            }
        },
        error: function (xhr) {
            hideLoadingModal();
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
function handleAsyncExport(exportId, totalParts, totalCount, message) {
    currentExportId = exportId;
    exportTotalParts = totalParts || 1;
    exportNotFoundCount = 0;
    lastKnownCompletedFiles = [];

    hideLoadingModal();
    showAsyncExportModal(exportTotalParts, totalCount);

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
        url: window.routes.customerFollowsReportsCheckProgress,
        type: "POST",
        data: { _token: window.csrfToken, export_id: exportId },
        success: function (response) {
            if (!response.success || !response.data) return;
            const data = response.data;

            if (data.status === "completed") {
                clearInterval(exportProgressInterval);
                handleExportCompleted(data);
            } else if (data.status === "failed") {
                clearInterval(exportProgressInterval);
                hideAsyncExportModal();
                toastr.error(data.error || "فشل في إنشاء الملف");
            } else if (data.status === "cancelled") {
                clearInterval(exportProgressInterval);
                hideAsyncExportModal();
                toastr.info("تم إلغاء التصدير");
            } else if (data.status === "processing") {
                exportNotFoundCount = 0;
                const currentPart = data.current_part || 1;
                const percentage = data.percentage || 0;
                const message = data.message || "جاري المعالجة...";
                const completedFiles = data.completed_files || [];
                const downloadUrls = data.download_urls || [];

                // استخدم part_percentage (نسبة الجزء الحالي 0-100) إن وُجد،
                // وإلا احسبها من النسبة الكلية
                let partPct = data.part_percentage;
                if (partPct === undefined || partPct === null) {
                    const totalParts =
                        data.total_parts || exportTotalParts || 1;
                    const partRange = 85 / totalParts;
                    const partBase = (currentPart - 1) * partRange;
                    partPct = Math.min(
                        100,
                        Math.round(((percentage - partBase) / partRange) * 100),
                    );
                }

                // حدّث شريط الجزء الحالي بنسبته الخاصة
                updatePartProgress(currentPart, partPct, message, false, null);

                // اعرض الملفات المكتملة حديثاً وحمّلها تلقائياً
                handleNewlyCompletedFiles(completedFiles, downloadUrls);
            } else if (data.status === "not_found") {
                exportNotFoundCount++;
                if (exportNotFoundCount >= 150) {
                    clearInterval(exportProgressInterval);
                    hideAsyncExportModal();
                    toastr.error("انتهت مهلة التصدير، يرجى المحاولة مرة أخرى");
                }
            }
        },
        error: function () {
            exportNotFoundCount++;
            if (exportNotFoundCount >= 10) {
                clearInterval(exportProgressInterval);
                hideAsyncExportModal();
                toastr.error("حدث خطأ في الاتصال أثناء تتبع التقدم");
            }
        },
    });
}

/*
|--------------------------------------------------------------------------
| Handle Newly Completed Files (auto-download)
|--------------------------------------------------------------------------
*/
function handleNewlyCompletedFiles(fileNames, downloadUrls) {
    fileNames.forEach(function (fileName, index) {
        if (lastKnownCompletedFiles.indexOf(fileName) === -1) {
            lastKnownCompletedFiles.push(fileName);
            const partNum = index + 1;
            const url = downloadUrls[index] || null;

            updatePartProgress(partNum, 100, null, true, url);

            if (url) {
                triggerDownload(url);
                toastr.success(
                    "تم إنشاء الجزء " + partNum + " — جاري التحميل تلقائياً",
                    "",
                    { timeOut: 5000 },
                );
                // وضّح للمستخدم أن الملف نزل تلقائياً مع إبقاء زر التحميل المجدد
                $("#part-status-" + partNum)
                    .html(
                        '<a href="' +
                            url +
                            '" class="btn btn-outline-success btn-sm mt-1" target="_blank">' +
                            '<i class="ti ti-download me-1"></i>تحميل الجزء ' +
                            partNum +
                            " مجدداً" +
                            "</a>",
                    )
                    .show();
            }
        }
    });
}

/*
|--------------------------------------------------------------------------
| Handle Export Completed
|--------------------------------------------------------------------------
*/
function handleExportCompleted(data) {
    const files = data.files || (data.file_name ? [data.file_name] : []);
    const urls = data.download_urls || [];

    files.forEach(function (file, index) {
        if (lastKnownCompletedFiles.indexOf(file) === -1) {
            lastKnownCompletedFiles.push(file);
            updatePartProgress(index + 1, 100, null, true, urls[index] || null);

            if (urls[index]) {
                triggerDownload(urls[index]);
            }
        }
    });

    // غيّر زر الإلغاء إلى إغلاق
    $("#cancelExportBtn")
        .removeClass("btn-outline-danger")
        .addClass("btn-secondary")
        .html('<i class="ti ti-check me-1"></i>إغلاق')
        .off("click")
        .on("click", function () {
            hideAsyncExportModal();
        });

    toastr.success(data.message || "تم التصدير بنجاح!", "", { timeOut: 8000 });
}

/*
|--------------------------------------------------------------------------
| Update Part Progress Bar
|--------------------------------------------------------------------------
*/
function updatePartProgress(
    partNum,
    percentage,
    message,
    isComplete,
    downloadUrl,
) {
    const bar = $("#progress-bar-part-" + partNum);
    const textEl = $("#progress-text-part-" + partNum);
    const badge = $("#badge-part-" + partNum);
    const statusEl = $("#part-status-" + partNum);

    if (!bar.length) return;

    // حارس: لا تعدّل شريطاً اكتمل بالفعل بتحديث غير مكتمل
    // (يمنع الـ polling التالي من الكتابة فوق الحالة المكتملة)
    if (!isComplete && bar.hasClass("bg-success")) return;

    if (isComplete) {
        bar.css("width", "100%")
            .attr("aria-valuenow", 100)
            .removeClass(
                "bg-secondary bg-primary progress-bar-animated progress-bar-striped",
            )
            .addClass("bg-success");
        textEl.text("100%");
        badge
            .removeClass("bg-secondary bg-primary bg-warning")
            .addClass("bg-success")
            .text("اكتمل ✓");

        if (downloadUrl) {
            statusEl
                .html(
                    '<a href="' +
                        downloadUrl +
                        '" class="btn btn-success btn-sm mt-1" target="_blank">' +
                        '<i class="ti ti-download me-1"></i>تحميل الجزء ' +
                        partNum +
                        "</a>",
                )
                .show();
        }
    } else {
        const pct = Math.min(Math.max(percentage || 0), 100);
        bar.css("width", pct + "%").attr("aria-valuenow", pct);
        textEl.text(Math.round(pct) + "%");

        if (pct > 0) {
            bar.removeClass("bg-secondary").addClass(
                "bg-primary progress-bar-animated progress-bar-striped",
            );
            badge
                .removeClass("bg-secondary")
                .addClass("bg-primary")
                .text("جاري...");
        }

        if (message) {
            statusEl.text(message).show();
        }
    }
}

/*
|--------------------------------------------------------------------------
| Trigger File Download
|--------------------------------------------------------------------------
*/
function triggerDownload(url) {
    const link = document.createElement("a");
    link.href = url;
    link.target = "_blank";
    document.body.appendChild(link);
    link.click();
    setTimeout(function () {
        document.body.removeChild(link);
    }, 200);
}

/*
|--------------------------------------------------------------------------
| Cancel Export
|--------------------------------------------------------------------------
*/
function cancelExport() {
    if (!currentExportId) return;

    clearInterval(exportProgressInterval);

    $.ajax({
        url: window.routes.customerFollowsReportsCancelExport,
        type: "POST",
        data: { _token: window.csrfToken, export_id: currentExportId },
        success: function () {
            hideAsyncExportModal();
            toastr.info("تم إلغاء التصدير");
        },
        error: function () {
            hideAsyncExportModal();
        },
    });

    currentExportId = null;
}

/*
|--------------------------------------------------------------------------
| Hide Async Export Modal
|--------------------------------------------------------------------------
*/
function hideAsyncExportModal() {
    const modal = $("#asyncExportModal");
    modal.off("hidden.bs.modal").on("hidden.bs.modal", function () {
        modal.remove();
    });
    modal.modal("hide");
}

/*
|--------------------------------------------------------------------------
| Show Async Export Modal (multi-part support)
|--------------------------------------------------------------------------
*/
function showAsyncExportModal(totalParts, totalCount) {
    totalParts = totalParts || 1;

    let partsHtml = "";
    for (let i = 1; i <= totalParts; i++) {
        partsHtml += `
            <div class="export-part mb-3" id="export-part-${i}">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="fw-medium">الجزء ${i} من ${totalParts}</small>
                    <span class="badge bg-secondary" id="badge-part-${i}">في الانتظار</span>
                </div>
                <div class="progress position-relative" style="height: 22px; border-radius: 10px; overflow: hidden;">
                    <div class="progress-bar bg-secondary"
                         id="progress-bar-part-${i}"
                         role="progressbar"
                         style="width: 0%; transition: width 0.5s ease;"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                    </div>
                    <div class="position-absolute w-100 h-100 d-flex align-items-center justify-content-center">
                        <span class="text-white fw-bold" id="progress-text-part-${i}" style="font-size: 11px; text-shadow: 0 0 3px rgba(0,0,0,.5);">0%</span>
                    </div>
                </div>
                <div id="part-status-${i}" class="text-muted mt-1" style="display:none; font-size: 12px;"></div>
            </div>
        `;
    }

    const maxRowsLabel =
        window.exportConfig && window.exportConfig.maxRowsPerFile
            ? window.exportConfig.maxRowsPerFile.toLocaleString()
            : "50,000";

    const infoHtml =
        totalParts > 1 && totalCount > 0
            ? `<div class="alert alert-info py-2 mb-3" style="font-size:13px;">
               <i class="ti ti-info-circle me-1"></i>
               <strong>${totalCount.toLocaleString()}</strong> سجل سيتم تقسيمها إلى <strong>${totalParts}</strong> ملفات
               (الحد الأقصى ${maxRowsLabel} لكل ملف)
           </div>`
            : "";

    const modalHtml = `
        <div class="modal fade" id="asyncExportModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="ti ti-file-export me-2 text-primary"></i>
                            جاري تصدير المتابعات
                        </h5>
                    </div>
                    <div class="modal-body">
                        ${infoHtml}
                        ${partsHtml}
                        <div class="text-center mt-3 border-top pt-3">
                            <button type="button" id="cancelExportBtn" class="btn btn-outline-danger btn-sm">
                                <i class="ti ti-x me-1"></i>إلغاء التصدير
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    $("#asyncExportModal").remove();
    $("body").append(modalHtml);

    $("#cancelExportBtn").on("click", function () {
        cancelExport();
    });

    $("#asyncExportModal").modal("show");

    // ابدأ الجزء الأول مباشرة بتحريك الشريط
    updatePartProgress(1, 0, "جاري الإعداد...", false, null);
    $("#badge-part-1")
        .removeClass("bg-secondary")
        .addClass("bg-primary")
        .text("جاري...");
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
