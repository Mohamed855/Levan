'use strict';

let companyPerformanceTable;
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
    $('.select2').each(function () {
        $(this).select2({
            placeholder: function () {
                return $(this).data('placeholder');
            },
            allowClear: true,
            width: '100%',
            language: {
                noResults: function () {
                    return "لا توجد نتائج";
                },
                searching: function () {
                    return "جاري البحث...";
                }
            },
            dir: 'rtl'
        });
    });

    setDefaultDates();
}

/*
|--------------------------------------------------------------------------
| Set Default Dates
|--------------------------------------------------------------------------
*/
function setDefaultDates() {
    const currentYear = new Date().getFullYear();
    const currentMonth = new Date().getMonth() + 1;

    $('#filter-year').val(currentYear).trigger('change');
    $('#filter-month').val(currentMonth).trigger('change');
}

/*
|--------------------------------------------------------------------------
| ⚡ OPTIMIZED - Initialize Table After Render
|--------------------------------------------------------------------------
*/
function initializeTableAfterRender() {
    const checkTable = setInterval(function () {
        if ($('#company-performance-table').length && $.fn.DataTable.isDataTable('#company-performance-table')) {
            companyPerformanceTable = $('#company-performance-table').DataTable();

            // ✅ ربط الجدول بمسار البيانات
            const dataRoute = window.routes.performanceReportsData;
            companyPerformanceTable.ajax.url(dataRoute).load();

            clearInterval(checkTable);
            console.log('✅ DataTable initialized successfully');
        }
    }, 500);
}

/*
|--------------------------------------------------------------------------
| Initialize Charts
|--------------------------------------------------------------------------
*/
function initializeCharts() {
    initializePaymentComparisonChart();
    initializeContactStatsChart();
}

/*
|--------------------------------------------------------------------------
| ✅ OPTIMIZED - Initialize Payment Comparison Chart
|--------------------------------------------------------------------------
*/
function initializePaymentComparisonChart() {
    const paymentData = window.chartData.paymentComparison;

    if (!paymentData || !paymentData.achieved_percentage) {
        $('#paymentComparisonChart').html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-chart-pie-off ti-24px mb-2 d-block"></i>
                <small>لا توجد بيانات</small>
            </div>
        `);
        return;
    }

    const series = [
        paymentData.achieved_percentage,
        paymentData.remaining_percentage
    ];

    const options = {
        series: series,
        chart: {
            type: 'donut',
            height: 220, // ✅ زيادة الارتفاع قليلاً
            fontFamily: 'Cairo, sans-serif',
            toolbar: { show: false }
        },
        labels: ['تم التحقيق', 'المتبقي'],
        colors: ['#4cc1bb', '#e0e0e0'],
        legend: {
            position: 'bottom',
            fontSize: '11px',
            fontWeight: '500',
            offsetY: 5
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: '12px',
                            color: '#666',
                            offsetY: -10
                        },
                        value: {
                            show: true,
                            fontSize: '28px',
                            // fontWeight: 'bold',
                            color: '#323e3e',
                            offsetY: 10,
                            formatter: function (val) {
                                return Math.round(val) + '%';
                            }
                        },
                        total: {
                            show: true,
                            label: 'الإنجاز',
                            fontSize: '16px',
                            fontWeight: '600',
                            color: '#666',
                            formatter: function (w) {
                                return Math.round(paymentData.achieved_percentage) + '%';
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: false // ✅ إخفاء الأرقام على القطع لتجنب الازدحام
        },
        tooltip: {
            enabled: true,
            y: {
                formatter: function (val) {
                    return Math.round(val) + '%';
                }
            }
        }
    };

    const chart = new ApexCharts(document.querySelector("#paymentComparisonChart"), options);
    chart.render();
}

/*
|--------------------------------------------------------------------------
| Initialize Contact Stats Chart
|--------------------------------------------------------------------------
*/
function initializeContactStatsChart() {
    const contactData = window.chartData.contactStats;

    if (!contactData) {
        $('#contactStatsChart').html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-chart-pie-off ti-24px mb-2 d-block"></i>
                <small>لا توجد بيانات</small>
            </div>
        `);
        return;
    }

    const series = [
        contactData.positive_count,
        contactData.negative_count,
        contactData.other_count
    ];

    const labels = ['إيجابية', 'سلبية', 'أخرى'];
    const colors = ['#4cc1bb', '#ff4d4f', '#ffa500'];

    const options = {
        series: series,
        chart: {
            type: 'pie',
            height: 250,
            fontFamily: 'Cairo, sans-serif',
            toolbar: { show: false }
        },
        labels: labels,
        colors: colors,
        legend: {
            position: 'bottom',
            fontSize: '11px',
            fontWeight: '500'
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return Math.round(val) + '%';
            },
            style: {
                fontSize: '10px',
                fontWeight: 'bold',
                colors: ['#fff']
            }
        },
        tooltip: {
            y: {
                formatter: function (val, opts) {
                    const label = opts.w.globals.labels[opts.seriesIndex];
                    return label + ': ' + val + ' عميل';
                }
            }
        }
    };

    const chart = new ApexCharts(document.querySelector("#contactStatsChart"), options);
    chart.render();
}

/*
|--------------------------------------------------------------------------
| ⚡ OPTIMIZED - Bind Events
|--------------------------------------------------------------------------
*/
function bindEvents() {
    const filterElements = [
        '#filter-year', '#filter-month', '#filter-company', '#filter-portfolio',
        '#filter-branch', '#filter-team', '#filter-user'
    ];

    // ✅ عند تغيير أي فلتر - نحدث الجدول فقط بدون إعادة تحميل الصفحة
    $(filterElements.join(', ')).on('change', debounce(function () {
        console.log('🔄 Filter changed');
        reloadTable();
    }, 800));

    // ✅ الفلاتر المتتابعة
    $('#filter-company').on('change', function () {
        filterPortfoliosByCompany($(this).val());
    });

    $('#filter-branch').on('change', function () {
        filterTeamsByBranch($(this).val());
    });

    $('#filter-team').on('change', function () {
        filterUsersByTeam($(this).val());
    });

    // ✅ إعادة تعيين الفلاتر
    $('#reset-filters').on('click', function () {
        resetFilters();
    });

    // ✅ زر التصدير PDF - منع التكرار النهائي
    let pdfExporting = false;

    $(document).off('click', '.btn-export-pdf').on('click', '.btn-export-pdf', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        // ✅ منع التنفيذ المتكرر
        if (pdfExporting) {
            console.log('⚠️ PDF export already in progress');
            return false;
        }

        pdfExporting = true;
        console.log('✅ Starting PDF export');

        exportCustomReportToPDF();

        // ✅ إعادة التفعيل بعد 2 ثانية
        setTimeout(() => {
            pdfExporting = false;
            console.log('✅ PDF export ready again');
        }, 2000);

        return false;
    });

    // ✅ تبديل أيقونة الفلاتر
    $('#filtersSection').on('show.bs.collapse', function () {
        $('[data-bs-target="#filtersSection"] i.ti-chevron-down').removeClass('ti-chevron-down').addClass('ti-chevron-up');
    });

    $('#filtersSection').on('hide.bs.collapse', function () {
        $('[data-bs-target="#filtersSection"] i.ti-chevron-up').removeClass('ti-chevron-up').addClass('ti-chevron-down');
    });
}

/*
|--------------------------------------------------------------------------
| ⚡ NEW - Reload Table Only (لا إعادة تحميل الصفحة)
|--------------------------------------------------------------------------
*/
function reloadTable() {
    if (!companyPerformanceTable) {
        console.warn('⚠️ Table not initialized yet');
        return;
    }

    const params = getCurrentFiltersAsParams();
    const dataRoute = window.routes.performanceReportsData;
    const newUrl = params ? dataRoute + '?' + params : dataRoute;

    console.log('📡 Reloading table with URL:', newUrl);

    // ✅ تحديث رابط AJAX وإعادة تحميل البيانات
    companyPerformanceTable.ajax.url(newUrl).load(function (json) {
        console.log('✅ Table data loaded:', json);
    }, function (xhr, error, code) {
        console.error('❌ Error loading table data:', error);
        toastr.error('حدث خطأ أثناء تحميل البيانات');
    });
}

/*
|--------------------------------------------------------------------------
| Filter Portfolios By Company
|--------------------------------------------------------------------------
*/
function filterPortfoliosByCompany(companyId) {
    const portfolioSelect = $('#filter-portfolio');

    portfolioSelect.find('option').each(function () {
        const option = $(this);
        if (option.val() === '') {
            option.show();
            return;
        }

        if (!companyId || option.data('company') == companyId) {
            option.show();
        } else {
            option.hide();
        }
    });

    const selectedPortfolio = portfolioSelect.val();
    if (selectedPortfolio && portfolioSelect.find('option:selected').data('company') != companyId) {
        portfolioSelect.val('').trigger('change');
    }
}

/*
|--------------------------------------------------------------------------
| Filter Teams By Branch
|--------------------------------------------------------------------------
*/
function filterTeamsByBranch(branchId) {
    const teamSelect = $('#filter-team');

    teamSelect.find('option').each(function () {
        const option = $(this);
        if (option.val() === '') {
            option.show();
            return;
        }

        if (!branchId || option.data('branch') == branchId) {
            option.show();
        } else {
            option.hide();
        }
    });

    const selectedTeam = teamSelect.val();
    if (selectedTeam && teamSelect.find('option:selected').data('branch') != branchId) {
        teamSelect.val('').trigger('change');
    }
}

/*
|--------------------------------------------------------------------------
| Filter Users By Team
|--------------------------------------------------------------------------
*/
function filterUsersByTeam(teamId) {
    const userSelect = $('#filter-user');

    userSelect.find('option').each(function () {
        const option = $(this);
        if (option.val() === '') {
            option.show();
            return;
        }

        if (!teamId || option.data('team') == teamId) {
            option.show();
        } else {
            option.hide();
        }
    });

    const selectedUser = userSelect.val();
    if (selectedUser && userSelect.find('option:selected').data('team') != teamId) {
        userSelect.val('').trigger('change');
    }
}

/*
|--------------------------------------------------------------------------
| ⚡ OPTIMIZED - Reset Filters
|--------------------------------------------------------------------------
*/
function resetFilters() {
    $('#filter-company, #filter-portfolio, #filter-branch, #filter-team, #filter-user').val('').trigger('change');
    setDefaultDates();

    // ✅ إعادة تحميل الجدول فقط
    reloadTable();
}

/*
|--------------------------------------------------------------------------
| Get Current Filters As Params
|--------------------------------------------------------------------------
*/
function getCurrentFiltersAsParams() {
    const filters = getCurrentFilters();
    const params = new URLSearchParams();

    Object.keys(filters).forEach(key => {
        if (filters[key] && filters[key] !== '') {
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
    const year = $('#filter-year').val();
    const month = $('#filter-month').val();

    let dateFrom = '';
    let dateTo = '';

    if (year && month) {
        const paddedMonth = month.toString().padStart(2, '0');
        const lastDay = new Date(year, month, 0).getDate();
        dateFrom = `${year}-${paddedMonth}-01`;
        dateTo = `${year}-${paddedMonth}-${lastDay}`;
    }

    return {
        date_from: dateFrom,
        date_to: dateTo,
        company_id: $('#filter-company').val(),
        portfolio_id: $('#filter-portfolio').val(),
        branch_id: $('#filter-branch').val(),
        team_id: $('#filter-team').val(),
        user_id: $('#filter-user').val()
    };
}

/*
|--------------------------------------------------------------------------
| ✅ Export Custom Report To PDF - FIXED
|--------------------------------------------------------------------------
*/
function exportCustomReportToPDF() {
    const filters = getCurrentFilters();

    // ✅ إنشاء نموذج مخفي
    const form = $('<form>', {
        'method': 'POST',
        'action': window.routes.performanceReportsExportPDF,
        'target': '_self' // ✅ نفس التاب، ليس _blank
    });

    // ✅ CSRF Token
    form.append($('<input>', {
        'type': 'hidden',
        'name': '_token',
        'value': window.csrfToken
    }));

    // ✅ إضافة الفلاتر
    $.each(filters, function (key, value) {
        if (value && value !== '') {
            form.append($('<input>', {
                'type': 'hidden',
                'name': key,
                'value': value
            }));
        }
    });

    // ✅ إرسال النموذج وحذفه فوراً
    form.appendTo('body').submit();

    // ✅ حذف النموذج بعد 100ms
    setTimeout(function () {
        form.remove();
    }, 100);
}

/*
|--------------------------------------------------------------------------
| Export Filtered To Excel
|--------------------------------------------------------------------------
*/
function exportFilteredToExcel() {
    const filters = getCurrentFilters();

    $.ajax({
        url: window.routes.performanceReportsShowExportModal,
        type: 'POST',
        data: {
            _token: window.csrfToken,
            ...filters
        },
        success: function (response) {
            if (response.success) {
                showExportFieldsModal(response.fields, response.count, filters, response.is_large_export);
            }
        },
        error: function () {
            toastr.error('حدث خطأ أثناء جلب بيانات التصدير');
        }
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
                <strong>عدد السجلات:</strong> ${count.toLocaleString()} سجل
                <span class="mx-2">|</span>
                <span id="selectedFieldsCount">0</span> حقل محدد
            </small>
        </div>
        
        <div class="text-center mb-3">
            <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2 mx-1" id="selectAllFields">
                تحديد الكل
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 mx-1" id="deselectAllFields">
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

    $('#fieldsContainer').html(modalContent);
    $('#exportFieldsModal').modal('show');

    updateFieldsCount();
    bindExportModalEvents(filters, isLargeExport);

    $('#fieldsCollapse').on('show.bs.collapse', function () {
        $('.ti-chevron-down').removeClass('ti-chevron-down').addClass('ti-chevron-up');
    });

    $('#fieldsCollapse').on('hide.bs.collapse', function () {
        $('.ti-chevron-up').removeClass('ti-chevron-up').addClass('ti-chevron-down');
    });
}

/*
|--------------------------------------------------------------------------
| Generate Fields Grid
|--------------------------------------------------------------------------
*/
function generateFieldsGrid(fields) {
    let html = '';

    fields.forEach((field, index) => {
        html += `
            <div class="col-md-4 col-sm-6">
                <div class="form-check">
                    <input class="form-check-input export-field" 
                           type="checkbox" 
                           value="${field.key}" 
                           id="field_${field.key}"
                           ${field.selected ? 'checked' : ''}>
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
    $('#selectAllFields').off('click').on('click', function () {
        $('.export-field').prop('checked', true);
        updateFieldsCount();
    });

    $('#deselectAllFields').off('click').on('click', function () {
        $('.export-field').prop('checked', false);
        updateFieldsCount();
    });

    $('#selectDefaultFields').off('click').on('click', function () {
        $('.export-field').prop('checked', false);
        const defaultFields = [
            'company_name',
            'customers_count',
            'total_portfolio',
            'total_payment',
            'achievement_rate'
        ];
        defaultFields.forEach(key => {
            $(`#field_${key}`).prop('checked', true);
        });
        updateFieldsCount();
    });

    $('.export-field').on('change', function () {
        updateFieldsCount();
    });

    $('#confirmExport').off('click').on('click', function () {
        const selectedFields = [];
        $('.export-field:checked').each(function () {
            selectedFields.push($(this).val());
        });

        if (selectedFields.length === 0) {
            toastr.warning('يرجى اختيار حقل واحد على الأقل');
            return;
        }

        $('#exportFieldsModal').modal('hide');
        executeExportWithFields(filters, selectedFields, isLargeExport);
    });
}

/*
|--------------------------------------------------------------------------
| Update Fields Count
|--------------------------------------------------------------------------
*/
function updateFieldsCount() {
    const totalChecked = $('.export-field:checked').length;
    $('#selectedFieldsCount').text(totalChecked);

    const allTotal = $('.export-field').length;
    $('#allFieldsCount').text(`${totalChecked}/${allTotal}`);

    const btnText = totalChecked > 0 ? `تصدير (${totalChecked} حقل)` : 'تصدير';
    $('#confirmExport').text(btnText);
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
        showLoadingModal('جاري إنشاء ملف Excel...');
    }

    $.ajax({
        url: window.routes.performanceReportsExportExcel,
        type: 'POST',
        data: {
            _token: window.csrfToken,
            selected_fields: selectedFields,
            ...filters
        },
        success: function (response) {
            if (response.success) {
                if (response.is_async) {
                    handleAsyncExport(response.export_id, response.message);
                } else {
                    hideLoadingModal();
                    window.open(response.download_url, '_blank');
                    toastr.success(response.message);
                }
            }
        },
        error: function (xhr) {
            hideLoadingModal();
            hideAsyncExportModal();
            const errorMsg = xhr.responseJSON?.message || 'حدث خطأ أثناء إنشاء الملف';
            toastr.error(errorMsg);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Show/Hide Loading Modal
|--------------------------------------------------------------------------
*/
function showLoadingModal(message = 'جاري المعالجة...') {
    $('#loadingMessage').text(message);
    $('#loadingModal').modal('show');
}

function hideLoadingModal() {
    $('#loadingModal').modal('hide');
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

// ✅ تصدير الدوال العامة
window.exportCustomReportToPDF = exportCustomReportToPDF;
window.exportFilteredToExcel = exportFilteredToExcel;