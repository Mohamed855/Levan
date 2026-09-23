'use strict';

let customerReportsTable;
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
            language: 'ar',
            dir: 'rtl'
        });
    });

    $('.select2-multiple').each(function () {
        const $element = $(this);
        const placeholder = $element.data('placeholder');

        $element.select2({
            placeholder: placeholder,
            allowClear: true,
            width: '100%',
            closeOnSelect: false,
            language: 'ar',
            dir: 'rtl'
        });

        initializePlaceholder($element, placeholder);
    });

    $('.select2-multiple').on('select2:select select2:unselect select2:clear change', function () {
        const $element = $(this);
        const placeholder = $element.data('placeholder');
        updatePlaceholder($element, placeholder);
    });
}

/*
|--------------------------------------------------------------------------
| Update Placeholder
|--------------------------------------------------------------------------
*/
function updatePlaceholder($element, placeholder) {
    setTimeout(function () {
        const $container = $element.siblings('.select2-container');
        const $rendered = $container.find('.select2-selection__rendered');

        if ($rendered.length === 0) return;

        const hasValues = $element.val() && $element.val().length > 0;

        $rendered.find('.select2-selection__placeholder').remove();

        if (!hasValues) {
            if ($rendered.find('li').length === 0) {
                $rendered.prepend(`<li class="select2-selection__placeholder">${placeholder}</li>`);
            }
        }
    }, 10);
}

/*
|--------------------------------------------------------------------------
| Initialize Placeholder
|--------------------------------------------------------------------------
*/
function initializePlaceholder($element, placeholder) {
    updatePlaceholder($element, placeholder);

    const intervals = [50, 100, 200, 300, 500];

    intervals.forEach(delay => {
        setTimeout(function () {
            const $container = $element.siblings('.select2-container');
            const $rendered = $container.find('.select2-selection__rendered');

            if ($container.length > 0 && $rendered.length > 0) {
                const hasValues = $element.val() && $element.val().length > 0;
                if (!hasValues) {
                    $rendered.find('.select2-selection__placeholder').remove();

                    if ($rendered.find('li').length === 0) {
                        $rendered.prepend(`<li class="select2-selection__placeholder">${placeholder}</li>`);
                    }
                }
            }
        }, delay);
    });

    setTimeout(function () {
        updatePlaceholder($element, placeholder);
    }, 1000);
}

/*
|--------------------------------------------------------------------------
| Initialize Table After Render
|--------------------------------------------------------------------------
*/
function initializeTableAfterRender() {
    const checkTable = setInterval(function () {
        if ($('#customer-reports-table').length && $.fn.DataTable.isDataTable('#customer-reports-table')) {
            customerReportsTable = $('#customer-reports-table').DataTable();
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
    if (!customerReportsTable) return;

    const tableWrapper = $('#customer-reports-table_wrapper');

    tableWrapper.on('click', '#select-all', function () {
        const rows = customerReportsTable.rows({ search: 'applied' }).nodes();
        $('input.row-checkbox', rows).prop('checked', this.checked);
    });

    tableWrapper.on('change', '.row-checkbox', function () {
        if (!this.checked) {
            $('#select-all').prop('checked', false);
        }
    });

    customerReportsTable.on('draw', function () {
        $('#select-all').prop('checked', false);
    });
}

/*
|--------------------------------------------------------------------------
| Initialize Charts
|--------------------------------------------------------------------------
*/
function initializeCharts() {
    initializeContactResultChart();
    initializePortfolioAmountChart();
}

/*
|--------------------------------------------------------------------------
| Generate Color Palette
|--------------------------------------------------------------------------
*/
function generateColorPalette(baseColor, count) {
    const colors = [];
    for (let i = 0; i < count; i++) {
        const alpha = 1 - (i * 0.15);
        colors.push(baseColor + Math.round(alpha * 255).toString(16).padStart(2, '0'));
    }
    return colors;
}

/*
|--------------------------------------------------------------------------
| Initialize Contact Result Chart
|--------------------------------------------------------------------------
*/
function initializeContactResultChart() {
    const contactResultData = window.chartData.contactResultData;

    if (!contactResultData || Object.keys(contactResultData).length === 0) {
        $('#contactResultChart').html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-phone-off ti-24px mb-2 d-block"></i>
                <small>لا توجد نتائج اتصال محددة</small>
            </div>
        `);
        return;
    }

    const labels = Object.keys(contactResultData);
    const series = Object.values(contactResultData);

    const totalValues = series.reduce((sum, val) => sum + (parseFloat(val) || 0), 0);
    if (totalValues === 0) {
        $('#contactResultChart').html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-phone-off ti-24px mb-2 d-block"></i>
                <small>لا توجد نتائج اتصال محددة</small>
            </div>
        `);
        return;
    }

    const colors = generateColorPalette('#372632', labels.length);

    const options = {
        series: series,
        chart: {
            type: 'donut',
            height: 280,
            fontFamily: 'Cairo, sans-serif',
            toolbar: { show: false }
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
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'المجموع',
                            fontSize: '14px',
                            fontWeight: 600,
                            color: '#372632'
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
                    return val + ' عميل';
                }
            }
        }
    };

    const contactResultChart = new ApexCharts(document.querySelector("#contactResultChart"), options);
    contactResultChart.render();
}

/*
|--------------------------------------------------------------------------
| Initialize Portfolio Amount Chart
|--------------------------------------------------------------------------
*/
function initializePortfolioAmountChart() {
    const portfoliosAmountData = window.chartData.portfoliosAmountData;

    if (!portfoliosAmountData || Object.keys(portfoliosAmountData).length === 0) {
        $('#portfolioAmountChart').html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-wallet-off ti-24px mb-2 d-block"></i>
                <small>لا توجد بيانات للمحافظ</small>
            </div>
        `);
        return;
    }

    const categories = Object.keys(portfoliosAmountData);
    const series = Object.values(portfoliosAmountData).map(val => parseFloat(val) || 0);

    const totalAmount = series.reduce((sum, val) => sum + val, 0);
    if (totalAmount === 0) {
        $('#portfolioAmountChart').html(`
            <div class="text-center text-muted p-4">
                <i class="ti ti-wallet-off ti-24px mb-2 d-block"></i>
                <small>لا توجد مبالغ للمحافظ</small>
            </div>
        `);
        return;
    }

    const colors = generateColorPalette('#4cc1bb', categories.length);

    const options = {
        series: [{
            name: 'إجمالي المبالغ',
            data: series
        }],
        chart: {
            type: 'bar',
            height: 280,
            fontFamily: 'Cairo, sans-serif',
            toolbar: { show: false }
        },
        xaxis: {
            categories: categories
        },
        colors: colors,
        plotOptions: {
            bar: {
                borderRadius: 6,
                columnWidth: '60%',
                horizontal: true,
                distributed: true
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return new Intl.NumberFormat('en-US', {
                    notation: 'compact',
                    maximumFractionDigits: 1
                }).format(val) + ' ر.س';
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return new Intl.NumberFormat('en-US').format(val) + ' ريال';
                }
            }
        },
        legend: { show: false }
    };

    const portfolioAmountChart = new ApexCharts(document.querySelector("#portfolioAmountChart"), options);
    portfolioAmountChart.render();
}

/*
|--------------------------------------------------------------------------
| Bind Events
|--------------------------------------------------------------------------
*/
function bindEvents() {
    const filterElements = [
        '#filter-date-from', '#filter-date-to', '#filter-customer-status',
        '#filter-customer-type', '#filter-company', '#filter-portfolio',
        '#filter-nationality', '#filter-manager', '#filter-contact-result',
        '#filter-debt-from', '#filter-debt-to'
    ];

    $(filterElements.join(', ')).on('change input', debounce(function () {
        if (customerReportsTable) {
            const baseUrl = window.routes.customerReportsIndex;
            const params = getCurrentFiltersAsParams();
            const newUrl = params ? baseUrl + '?' + params : baseUrl;
            customerReportsTable.ajax.url(newUrl).load();
        }
    }, 800));

    $('#reset-filters').on('click', function () {
        resetFilters();
    });

    $('#filter-company').on('change', function () {
        filterPortfoliosByCompany();
    });

    $(document).on('click', '.btn-export-pdf', function () {
        exportCustomReportToPDF();
    });

    $(document).on('click', '.btn-export-excel', function () {
        exportFilteredToExcel();
    });

    $('#filtersSection').on('show.bs.collapse', function () {
        $('[data-bs-target="#filtersSection"] i.ti-chevron-down').removeClass('ti-chevron-down').addClass('ti-chevron-up');
    });

    $('#filtersSection').on('hide.bs.collapse', function () {
        $('[data-bs-target="#filtersSection"] i.ti-chevron-up').removeClass('ti-chevron-up').addClass('ti-chevron-down');
    });
}

/*
|--------------------------------------------------------------------------
| Filter Portfolios By Company
|--------------------------------------------------------------------------
*/
function filterPortfoliosByCompany() {
    const selectedCompany = $('#filter-company').val();
    const portfolioSelect = $('#filter-portfolio');

    portfolioSelect.val('').trigger('change');

    portfolioSelect.find('option').each(function () {
        const option = $(this);
        const companyId = option.data('company');

        if (!selectedCompany || !companyId || companyId == selectedCompany) {
            option.show();
        } else {
            option.hide();
        }
    });

    portfolioSelect.select2();
}

/*
|--------------------------------------------------------------------------
| Reset Filters
|--------------------------------------------------------------------------
*/
function resetFilters() {
    $('#filter-date-from, #filter-date-to, #filter-debt-from, #filter-debt-to').val('');
    $('.select2').val('').trigger('change');
    $('.select2-multiple').val(null).trigger('change');

    if (customerReportsTable) {
        customerReportsTable.ajax.url(window.routes.customerReportsIndex).load();
    }

    toastr.info('تم إعادة تعيين جميع الفلاتر');
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
        const value = filters[key];

        if (Array.isArray(value) && value.length > 0) {
            value.forEach(val => {
                if (val && val !== '') {
                    params.append(key + '[]', val);
                }
            });
        } else if (value && value !== '' && !Array.isArray(value)) {
            params.append(key, value);
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
        date_from: $('#filter-date-from').val(),
        date_to: $('#filter-date-to').val(),
        customer_status_id: $('#filter-customer-status').val(),
        customer_type: $('#filter-customer-type').val(),
        company_id: $('#filter-company').val(),
        portfolio_id: $('#filter-portfolio').val(),
        nationality: $('#filter-nationality').val(),
        relationship_manager_id: $('#filter-manager').val() || [],
        contact_result_id: $('#filter-contact-result').val() || [],
        debt_from: $('#filter-debt-from').val(),
        debt_to: $('#filter-debt-to').val(),
    };
}

/*
|--------------------------------------------------------------------------
| Export Custom Report To PDF
|--------------------------------------------------------------------------
*/
function exportCustomReportToPDF() {
    const filters = getCurrentFilters();

    toastr.info('جاري تحضير تقرير PDF...');

    const form = $('<form>', {
        'method': 'POST',
        'action': window.routes.customerReportsExportPDF,
        'target': '_blank'
    });

    form.append($('<input>', {
        'type': 'hidden',
        'name': '_token',
        'value': window.csrfToken
    }));

    $.each(filters, function (key, value) {
        if (value && value !== '') {
            form.append($('<input>', {
                'type': 'hidden',
                'name': key,
                'value': value
            }));
        }
    });

    form.appendTo('body').submit().remove();
}

/*
|--------------------------------------------------------------------------
| Export Filtered To Excel
|--------------------------------------------------------------------------
*/
function exportFilteredToExcel() {
    const filters = getCurrentFilters();

    $.ajax({
        url: window.routes.customerReportsShowExportModal,
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
                <strong>عدد السجلات:</strong> ${count.toLocaleString()} عميل
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
            'name',
            'customer_type',
            'identity_number',
            'phone',
            'account_number',
            'company_name',
            'portfolio_name',
            'manager_name',
            'customer_status',
            'debt_amount',
            'total_credit',
            'remaining_debt',
            'created_at'
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
        url: window.routes.customerReportsExportExcel,
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

    toastr.info(message, '', { timeOut: 8000 });
}

/*
|--------------------------------------------------------------------------
| Check Export Progress
|--------------------------------------------------------------------------
*/
function checkExportProgress(exportId) {
    $.ajax({
        url: window.routes.customerReportsCheckProgress || '/reports/customers/check-progress',
        type: 'POST',
        data: {
            _token: window.csrfToken,
            export_id: exportId
        },
        success: function (response) {
            if (response.success && response.data) {
                const data = response.data;

                if (data.status === 'completed') {
                    clearInterval(exportProgressInterval);
                    hideAsyncExportModal();

                    showCompletedExportModal(data);
                    toastr.success('تم إنشاء الملف بنجاح!');

                } else if (data.status === 'failed') {
                    clearInterval(exportProgressInterval);
                    hideAsyncExportModal();
                    toastr.error(data.error || 'فشل في إنشاء الملف');

                } else if (data.status === 'processing') {
                    updateAsyncExportStatus(data.percentage || 0, data.message || 'جاري المعالجة...');
                }
            }
        },
        error: function () {
            clearInterval(exportProgressInterval);
            hideAsyncExportModal();
            toastr.error('حدث خطأ أثناء تتبع التقدم');
        }
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

    $('#exportProgress')
        .css('width', safePercentage + '%')
        .attr('aria-valuenow', safePercentage);

    $('#progressText').text(roundedPercentage + '%');
    $('#exportStatus').text(message);

    if (safePercentage >= 100) {
        $('.spinner-border').removeClass('spinner-border').addClass('ti ti-check-circle text-success');
    }
}

/*
|--------------------------------------------------------------------------
| Hide Async Export Modal
|--------------------------------------------------------------------------
*/
function hideAsyncExportModal() {
    $('#asyncExportModal').modal('hide');
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

    if ($('#asyncExportModal').length === 0) {
        $('body').append(modalHtml);
    }

    $('#asyncExportModal').modal('show');
}


/*
|--------------------------------------------------------------------------
| Show Completed Export Modal
|--------------------------------------------------------------------------
*/
function showCompletedExportModal(data) {
    const fileName = (data && data.file_name) ? String(data.file_name) : '';
    const baseDownload = (window.routes && window.routes.customerReportsDownload) ? window.routes.customerReportsDownload : '';
    const downloadUrl = (fileName && baseDownload)
        ? baseDownload.replace('FILE_PLACEHOLDER', encodeURIComponent(fileName))
        : '#';

    const canDownload = downloadUrl !== '#';

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

            <div class="row g-3 mb-3">
              <div class="col-4 text-center">
                <div class="p-3 rounded border">
                  <i class="ti ti-database text-primary d-block mb-2" style="font-size: 24px;"></i>
                  <small class="text-muted d-block">السجلات</small>
                  <strong>${data?.total_records?.toLocaleString?.() || 'غير محدد'}</strong>
                </div>
              </div>
              <div class="col-4 text-center">
                <div class="p-3 rounded border">
                  <i class="ti ti-archive text-warning d-block mb-2" style="font-size: 24px;"></i>
                  <small class="text-muted d-block">الحجم</small>
                  <strong>${data?.file_size || 'غير محدد'}</strong>
                </div>
              </div>
              <div class="col-4 text-center">
                <div class="p-3 rounded border">
                  <i class="ti ti-clock text-success d-block mb-2" style="font-size: 24px;"></i>
                  <small class="text-muted d-block">التاريخ</small>
                  <strong class="small">${formatDateTime(data?.created_at) || 'الآن'}</strong>
                </div>
              </div>
            </div>

            ${!canDownload ? `
              <div class="alert alert-warning mb-0">
                تعذّر تجهيز رابط التحميل. الرجاء المحاولة مجددًا.
              </div>` : ``}
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            <a href="${downloadUrl}" class="btn btn-success ${canDownload ? '' : 'disabled'}" ${canDownload ? 'target="_blank"' : 'aria-disabled="true"'} >
              <i class="ti ti-download me-1"></i>تحميل الملف
            </a>
          </div>
        </div>
      </div>
    </div>
  `;

    if ($('#completedExportModal').length) {
        $('#completedExportModal').remove();
    }

    $('body').append(modalHtml);
    $('#completedExportModal').modal('show');
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
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');

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
function showLoadingModal(message = 'جاري المعالجة...') {
    $('#loadingMessage').text(message);
    $('#loadingModal').modal('show');
}

/*
|--------------------------------------------------------------------------
| Hide Loading Modal
|--------------------------------------------------------------------------
*/
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

window.exportCustomReportToPDF = exportCustomReportToPDF;
window.exportFilteredToExcel = exportFilteredToExcel;