/**
 * تقارير المستخدمين - JavaScript محسن ومتوافق
 */

'use strict';

// ============================================================================
// متغيرات عامة
// ============================================================================
let usersReportTable;

// ============================================================================
// تهيئة التطبيق
// ============================================================================
$(document).ready(function () {
    initializeComponents();
    initializeDataTable();
    initializeCharts();
    bindEvents();
});

/*
|--------------------------------------------------------------------------
| تهيئة المكونات الأساسية
|--------------------------------------------------------------------------
*/
function initializeComponents() {
    $('.select2').each(function () {
        $(this).select2({
            placeholder: function () {
                return $(this).data('placeholder');
            },
            allowClear: true,
            width: '100%',
            language: 'ar',
            dir: 'rtl',
            dropdownParent: $(this).closest('#card-users-report')
        });
    });

    // تهيئة Flatpickr للتواريخ
    $('.flatpickr-date').flatpickr({
        dateFormat: 'Y-m-d',
        locale: 'ar',
        allowInput: true
    });
}

/*
|--------------------------------------------------------------------------
| تهيئة جدول البيانات
|--------------------------------------------------------------------------
*/
function initializeDataTable() {
    usersReportTable = $('#users-report-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: window.routes.usersReportIndex,
            data: function (d) {
                // الفلاتر الخاصة بتقارير المستخدمين
                d.branch_id = $('#branch_id').val();
                d.status = $('#status').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
            }
        },
        columns: getTableColumns(),
        order: [[4, 'desc']],
        dom: getTableDom(),
        buttons: getTableButtons(),
        language: {
            url: window.assets.arabicDataTable
        },
        orderCellsTop: true,
        columnDefs: getColumnDefs(),
        responsive: getResponsiveConfig(),
        drawCallback: function () {
            updateRecordCount();
        }
    });
}

// ============================================================================
// إعدادات الجدول
// ============================================================================

function getTableColumns() {
    return [
        {
            data: "DT_RowIndex",
            name: "DT_RowIndex",
            orderable: false,
            searchable: false,
            className: 'control'
        },
        {
            data: 'user_info',
            name: 'user_info',
            orderable: false
        },
        {
            data: 'contact_info',
            name: 'contact_info',
            orderable: false
        },
        {
            data: 'branch_info',
            name: 'branch_info',
            orderable: false
        },
        {
            data: 'performance_info',
            name: 'performance_info',
            orderable: false
        },
        {
            data: 'monthly_performance',
            name: 'monthly_performance',
            orderable: false
        },
        {
            data: 'last_activity',
            name: 'last_activity',
            orderable: false
        },
        {
            data: 'status_info',
            name: 'status',
            orderable: false
        },
        {
            data: 'join_date',
            name: 'created_at',
            className: 'text-center'
        }
    ];
}

function getTableButtons() {
    return [
        {
            extend: 'collection',
            className: 'btn btn-export',
            text: 'الإجراءات',
            buttons: [
                {
                    extend: 'copy',
                    text: 'نسخ'
                },
                {
                    extend: 'excel',
                    text: 'إكسل'
                },
                {
                    text: 'تصدير PDF',
                    action: function (e, dt, node, config) {
                        exportUsersReportToPDF();
                    }
                }
            ]
        }
    ];
}

function getTableDom() {
    return '<"dt-toolbar mb-3"' +
        '<"dt-toolbar-left d-flex flex-column flex-sm-row align-items-start align-items-sm-center"' +
        'f' +
        'l' +
        '>' +
        '<"dt-toolbar-right d-flex flex-column flex-sm-row align-items-end align-items-sm-center justify-content-between"' +
        'B' +
        '>' +
        '>' +
        'rt' +
        '<"row mt-3"' +
        '<"d-flex flex-column flex-sm-row align-items-center justify-content-between w-100"' +
        'i' +
        '<"pagination-wrapper overflow-auto w-100"' +
        'p' +
        '>' +
        '>' +
        '>';
}

function getColumnDefs() {
    return [{
        className: "control",
        orderable: false,
        targets: 0,
        render: function (data, type, full, meta) {
            return "";
        }
    }];
}

function getResponsiveConfig() {
    return {
        details: {
            display: $.fn.dataTable.Responsive.display.modal({
                header: function () {
                    return "التفاصيل";
                }
            }),
            type: "column",
            renderer: function (api, rowIdx, columns) {
                const data = $.map(columns, function (col) {
                    return col.title !== "" ?
                        '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
                        '<td>' + col.title + ':</td>' +
                        '<td>' + col.data + '</td>' +
                        '</tr>' : "";
                }).join("");

                return data ? $('<table class="table"/><tbody />').append(data) : false;
            }
        }
    };
}

/*
|--------------------------------------------------------------------------
| تهيئة المخططات البيانية
|--------------------------------------------------------------------------
*/
function initializeCharts() {
    initializeBranchChart();
    initializeUsersPerformanceChart();
}

/*
|--------------------------------------------------------------------------
| مخطط التحصيلات حسب الفرع
|--------------------------------------------------------------------------
*/
function initializeBranchChart() {
    const branchData = window.chartData.branchData;

    if (!branchData || branchData.length === 0) {
        $('#branchChart').html('<div class="text-center text-muted p-4"><i class="ti ti-chart-pie-2 ti-24px mb-2"></i><br>لا توجد بيانات للعرض</div>');
        return;
    }

    const labels = branchData.map(item => item.name);
    const series = branchData.map(item => item.amount);

    // ألوان من درجات #372632
    const colors = [
        '#372632', '#4fb8c4', '#6fc8d3', '#90d8e2', '#1e8895', '#0e6875'
    ];

    const options = {
        series: series,
        chart: {
            type: 'donut',
            height: 280,
            fontFamily: 'Cairo, sans-serif'
        },
        labels: labels,
        colors: colors,
        legend: {
            position: 'bottom',
            fontSize: '12px'
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '60%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'المجموع',
                            formatter: function (w) {
                                return Number(w.globals.seriesTotals.reduce((a, b) => a + b, 0)).toLocaleString('ar-SA', {
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                }) + ' ريال';
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return Math.round(val) + '%';
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return Number(val).toLocaleString('ar-SA', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }) + ' ريال';
                }
            }
        }
    };

    const branchChart = new ApexCharts(document.querySelector("#branchChart"), options);
    branchChart.render();
}

/*
|--------------------------------------------------------------------------
| مخطط أداء المستخدمين
|--------------------------------------------------------------------------
*/
function initializeUsersPerformanceChart() {
    const usersData = window.chartData.usersPerformance;

    if (!usersData || usersData.length === 0) {
        $('#usersPerformanceChart').html('<div class="text-center text-muted p-4"><i class="ti ti-chart-bar ti-24px mb-2"></i><br>لا توجد بيانات للعرض</div>');
        return;
    }

    const categories = usersData.map(item => item.name);
    const amounts = usersData.map(item => item.amount);

    const options = {
        series: [{
            name: 'المبلغ المحصل',
            data: amounts
        }],
        chart: {
            type: 'bar',
            height: 280,
            fontFamily: 'Cairo, sans-serif',
            toolbar: { show: false }
        },
        colors: ['#372632'],
        plotOptions: {
            bar: {
                borderRadius: 6,
                horizontal: true,
                dataLabels: { position: 'top' }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return Number(val).toLocaleString('ar-SA', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            },
            style: {
                fontSize: '10px',
                colors: ['#372632']
            }
        },
        xaxis: {
            categories: categories,
            labels: {
                style: {
                    fontSize: '10px'
                }
            }
        },
        yaxis: {
            labels: {
                style: {
                    fontSize: '10px'
                }
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return Number(val).toLocaleString('ar-SA', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }) + ' ريال';
                }
            }
        }
    };

    const usersChart = new ApexCharts(document.querySelector("#usersPerformanceChart"), options);
    usersChart.render();
}


/*
|--------------------------------------------------------------------------
| ربط الأحداث
|--------------------------------------------------------------------------
*/
function bindEvents() {
    // تحديث الجدول تلقائياً عند تغيير الفلاتر
    $('#branch_id, #status, #date_from, #date_to').on('change input', debounce(function () {
        usersReportTable.ajax.reload(null, false);
    }, 800));

    // إعادة تعيين الفلاتر
    $('#reset-filters').on('click', function () {
        resetFilters();
    });

    // تحسين UX للفلاتر
    $('.select2, .flatpickr-date').on('focus', function () {
        $(this).closest('.card').addClass('filter-active');
    }).on('blur', function () {
        $(this).closest('.card').removeClass('filter-active');
    });
}

/*
|--------------------------------------------------------------------------
| إعادة تعيين جميع الفلاتر
|--------------------------------------------------------------------------
*/
function resetFilters() {
    $('#date_from, #date_to').val('');
    $('.select2').val('').trigger('change');

    usersReportTable.ajax.reload();

    toastr.info('تم إعادة تعيين جميع الفلاتر');
}

/*
|--------------------------------------------------------------------------
| تصدير تقرير PDF مخصص
|--------------------------------------------------------------------------
*/
function exportUsersReportToPDF() {
    const filters = getCurrentFilters();

    // عرض رسالة التحضير
    toastr.info('جاري تحضير تقرير المستخدمين...');

    // إنشاء نموذج وإرساله
    const form = $('<form>', {
        'method': 'POST',
        'action': window.routes.usersReportExportPDF,
        'target': '_blank'
    });

    // إضافة CSRF token
    form.append($('<input>', {
        'type': 'hidden',
        'name': '_token',
        'value': window.csrfToken
    }));

    // إضافة الفلاتر
    $.each(filters, function (key, value) {
        if (value && value !== '') {
            form.append($('<input>', {
                'type': 'hidden',
                'name': key,
                'value': value
            }));
        }
    });

    // إرسال النموذج
    form.appendTo('body').submit().remove();

    // رسالة نجاح مؤجلة
    setTimeout(() => {
        toastr.success('تم إنشاء تقرير المستخدمين بنجاح');
    }, 1500);
}

/*
|--------------------------------------------------------------------------
| الحصول على الفلاتر الحالية
|--------------------------------------------------------------------------
*/
function getCurrentFilters() {
    return {
        branch_id: $('#branch_id').val(),
        status: $('#status').val(),
        date_from: $('#date_from').val(),
        date_to: $('#date_to').val()
    };
}

/*
|--------------------------------------------------------------------------
| تحديث عداد السجلات
|--------------------------------------------------------------------------
*/
function updateRecordCount() {
    // لا نحتاج لتحديث عداد هنا لأن DataTables يتولى ذلك
}

/*
|--------------------------------------------------------------------------
| دالة التأخير للأحداث
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

/*
|--------------------------------------------------------------------------
| دالة عامة لإظهار رسائل التحميل
|--------------------------------------------------------------------------
*/
function showLoadingToast(message) {
    return toastr.info(message, '', {
        timeOut: 0,
        extendedTimeOut: 0,
        closeButton: false,
        tapToDismiss: false
    });
}
