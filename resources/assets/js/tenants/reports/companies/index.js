'use strict';

const reportData = window.reportData || {};
const routes = reportData.routes || {};
const G = reportData.global || {};
const charts = reportData.charts || {};
let activePeriod = reportData.currentPeriod || 'last_6months';
let activeCompanyMeta = null;
let loadingCount = 0;
let exportProgressInterval = null;

const chartColors = ['#696cff', '#03c3ec', '#71dd37', '#ffab00', '#ff3e1d', '#26c6da', '#8a2be2'];

$(function () {
    initSelect2($('#company-selector'));
    initSelect2($('#filter-status'));
    resetPortfolioSelector();
    bindToolbar();
    bindTableFiltersToggle();
    applyStats(G, 'global');
    renderCharts();
});

function bindToolbar() {
    $('#period-selector').on('change', function () {
        activePeriod = $(this).val() || 'last_6months';
        const url = new URL(window.location.href);
        url.searchParams.set('period', activePeriod);
        window.location.href = url.toString();
    });

    $('#company-selector').on('change', function () {
        const companyId = $(this).val();
        resetPortfolioSelector(companyId ? 'loading' : 'empty');

        if (companyId) {
            loadCompanyStats(companyId);
            loadPortfolioList(companyId);
        } else {
            activeCompanyMeta = null;
            applyStats(G, 'global');
            setLevelCrumb('global');
            getCompanyTable()?.draw(); // ← الإضافة (إعادة عرض الكل)
        }
    });

    $('#portfolio-selector').on('change', function () {
        const portfolioId = $(this).val();
        const companyId = $('#company-selector').val();

        if (portfolioId) {
            loadPortfolioStats(portfolioId);
        } else if (companyId) {
            loadCompanyStats(companyId);
        }
    });

    $('#btn-refresh').on('click', function () {
        const companyId = $('#company-selector').val();
        const portfolioId = $('#portfolio-selector').val();

        if (portfolioId) {
            loadPortfolioStats(portfolioId);
        } else if (companyId) {
            loadCompanyStats(companyId);
            loadPortfolioList(companyId);
        } else {
            window.location.reload();
        }
    });

    $('#btn-export').on('click', function () {
        exportToExcel();
    });
}

function bindTableFiltersToggle() {
    $('#toggle-table-filters').on('click', function () {
        const $filtersSection = $('#filtersSection');
        const isHidden = $filtersSection.hasClass('d-none');

        $filtersSection.toggleClass('d-none', !isHidden);

        $(this).find('span').text(isHidden ? 'إخفاء الفلاتر' : 'إظهار الفلاتر');
        $(this).find('i')
            .toggleClass('ti-filter', !isHidden)
            .toggleClass('ti-filter-off', isHidden);
    });
}



function loadCompanyStats(companyId) {
    spinner(true);

    $.ajax({
        url: buildRoute(routes.companyStats, '__COMPANY_ID__', companyId),
        data: { period: activePeriod },
        success(response) {
            if (!response.success) { showAjaxError('تعذر جلب بيانات الشركة'); return; }
            activeCompanyMeta = response.meta || {};
            applyStats(response.data, 'company', activeCompanyMeta);
            setLevelCrumb('company', activeCompanyMeta.company_name);
            getCompanyTable()?.draw(); // ← الإضافة
        },
        error() {
            showAjaxError('تعذر جلب بيانات الشركة');
        },
        complete() {
            spinner(false);
        },
    });
}

function loadPortfolioList(companyId) {
    spinner(true);

    $.ajax({
        url: buildRoute(routes.companyPortfolios, '__COMPANY_ID__', companyId),
        success(response) {
            if (!response.success) {
                showAjaxError('تعذر جلب قائمة المحافظ');
                return;
            }

            fillPortfolioSelector(response.data || []);
        },
        error() {
            showAjaxError('تعذر جلب قائمة المحافظ');
        },
        complete() {
            spinner(false);
        },
    });
}

function loadPortfolioStats(portfolioId) {
    spinner(true);

    $.ajax({
        url: buildRoute(routes.portfolioStats, '__PORTFOLIO_ID__', portfolioId),
        data: { period: activePeriod },
        success(response) {
            if (!response.success) {
                showAjaxError('تعذر جلب بيانات المحفظة');
                return;
            }

            applyStats(response.data, 'portfolio', response.meta || {});
            setLevelCrumb('portfolio', response.meta?.company_name, response.meta?.portfolio_name);
        },
        error() {
            showAjaxError('تعذر جلب بيانات المحفظة');
        },
        complete() {
            spinner(false);
        },
    });
}

// في applyStats — أضف هذا السطر في نهايتها
function applyStats(data, level, meta = {}) {
    if (!data) return;

    applyCompanyCard(data.companies || G.companies || {});
    applyPortfolioCard(data.portfolios || {}, data.customers || {}, level);
    applyCustomerCard(data.customers || {}, level);
    applyTargetCard(data.targets || null, level);
    applyFinancialCards(data.financials || {});

    if (level === 'company') activeCompanyMeta = meta;

    adjustCardLayout(level);
}

function adjustCardLayout(level) {
    const showKpi1 = level === 'global';
    const showKpi2 = level !== 'portfolio';
    const showKpi4 = level !== 'portfolio';

    $('#kpi1-col').toggle(showKpi1);
    $('#kpi2-col').toggle(showKpi2);
    $('#kpi4-col').toggle(showKpi4);

    const visibleCount = [showKpi1, showKpi2, true, showKpi4].filter(Boolean).length;
    const colClass = visibleCount === 4 ? 'col-sm-6 col-xl-3'
        : visibleCount === 3 ? 'col-md-4'
            : visibleCount === 2 ? 'col-sm-6'
                : 'col-12';

    const removeClasses = 'col-sm-6 col-xl-3 col-md-4 col-12';

    ['#kpi1-col', '#kpi2-col', '#kpi3-col', '#kpi4-col'].forEach(id => {
        $(id).removeClass(removeClasses).addClass(colClass);
    });
}
function applyCompanyCard(companies) {
    $('#kpi1-total').text(fmt(companies.total));
    $('#kpi1-active').text(fmt(companies.active));
    $('#kpi1-inactive').text(fmt(companies.inactive));
    $('#kpi1-archived').text(fmt(companies.archived));
    $('#kpi1-new').text(fmt(companies.new_this_month));
}

function applyPortfolioCard(portfolios, customers, level) {
    if (level === 'portfolio') {
        $('#kpi2-title').text('بيانات المحفظة');
        $('#kpi2-value').text(portfolios.name || 'غير محددة');
        $('#kpi2-sub').html(`<span>${fmt(portfolios.customers_count ?? customers.total)} عميل في هذه المحفظة</span>`);
        return;
    }

    $('#kpi2-title').text(level === 'company' ? 'محافظ الشركة' : 'عدد المحافظ');
    $('#kpi2-value').text(fmt(portfolios.total));
    $('#kpi2-meta').html(
        `<span class="text-success">نشطة <strong>${fmt(portfolios.active)}</strong></span>
         <span class="text-danger">غير نشطة <strong>${fmt(portfolios.inactive)}</strong></span>`
    );

    if (level === 'company') {
        $('#kpi2-sub').html(`متوسط <strong>${fmtDecimal(portfolios.avg_customers_per_portfolio, 1)}</strong> عميل / محفظة`);
    } else {
        $('#kpi2-sub').html(`متوسط <strong>${fmtDecimal(portfolios.avg_per_company, 1)}</strong> محفظة / شركة`);
    }
}

function applyCustomerCard(customers, level) {
    const title = level === 'portfolio' ? 'عملاء المحفظة' : level === 'company' ? 'عملاء الشركة' : 'عدد العملاء';
    $('#kpi3-title').text(title);
    $('#kpi3-value').text(fmt(customers.total));

    if (customers.avg_per_portfolio === null || typeof customers.avg_per_portfolio === 'undefined') {
        $('#kpi3-avg-portfolio').show().html('<span class="text-muted">متوسط المحافظ غير مطبق</span>');
    } else {
        $('#kpi3-avg-portfolio').show().html(`متوسط <strong>${fmtDecimal(customers.avg_per_portfolio, 1)}</strong> عميل / محفظة`);
    }

    $('#kpi3-avg-debt').html(`متوسط الدين للعميل: <strong>${currency(customers.avg_debt)}</strong>`);
}

function applyTargetCard(targets, level) {
    if (level === 'portfolio') {
        $('#kpi4-title').text('هدف المحفظة');
        $('#kpi4-value').text('غير مطبق');
        $('#kpi4-meta').html('<span class="text-muted">الأهداف تقاس على مستوى الشركة</span>');
        $('#kpi4-bar').css('width', '0%').removeClass('bg-success bg-warning').addClass('bg-secondary');
        return;
    }

    $('#kpi4-col').show();

    if (level === 'company') {
        $('#kpi4-title').text('هدف الشركة');

        if (!targets || targets.target_percentage === null) {
            $('#kpi4-value').text('بدون هدف');
            $('#kpi4-meta').html('<span class="text-muted">لم يحدد هدف لهذه الشركة</span>');
            $('#kpi4-bar').css('width', '0%').removeClass('bg-success bg-warning').addClass('bg-secondary');
            return;
        }

        const achieved = Number(targets.achieved_rate || 0);
        const target = Number(targets.target_percentage || 0);
        const gap = Number(targets.gap || 0);
        const onTarget = gap >= 0;

        $('#kpi4-value').html(`${fmtDecimal(achieved, 2)}% <span class="text-muted kpi-inline-muted">/ ${fmtDecimal(target, 2)}%</span>`);
        $('#kpi4-meta').html(`<span class="${onTarget ? 'text-success' : 'text-danger'}">${onTarget ? 'فوق الهدف' : 'دون الهدف'} ${fmtDecimal(Math.abs(gap), 2)} نقطة</span>`);
        $('#kpi4-bar').css('width', clamp(achieved, 0, 100) + '%').removeClass('bg-success bg-warning bg-secondary').addClass(onTarget ? 'bg-success' : 'bg-warning');
        return;
    }

    const total = Number(targets?.total_with_target || targets?.total || 0);
    const onTarget = Number(targets?.on_target || 0);
    const percent = total > 0 ? (onTarget / total) * 100 : 0;

    $('#kpi4-title').text('الأداء مقابل الهدف');
    $('#kpi4-value').html(`${fmt(onTarget)} <span class="text-muted kpi-inline-muted">/ ${fmt(total)}</span>`);
    $('#kpi4-meta').html(`دون الهدف: <strong>${fmt(targets?.below_target)}</strong> <span class="ms-2">بلا هدف: ${fmt(targets?.no_target)}</span>`);
    $('#kpi4-bar').css('width', percent + '%').removeClass('bg-warning bg-secondary').addClass('bg-success');
}

function applyFinancialCards(financials) {
    const totalDebt = Number(financials.totalDebt || 0);
    const totalCredit = Number(financials.totalCredit || 0);
    const safi = Number(financials.safi || 0);
    const rate = Number(financials.rate || 0);

    $('#kpi5-value').html(`${currency(totalDebt)}`);

    $('#kpi6-credit').html(`${currency(totalCredit)}`);
    $('#kpi6-rate').text(`${fmtDecimal(rate, 2)}%`);

    $('#kpi7-safi')
        .html(`${currency(Math.abs(safi))}`)
        .toggleClass('text-danger', safi > 0)
        .toggleClass('text-success', safi <= 0);


}

function fillPortfolioSelector(portfolios) {
    const $selector = $('#portfolio-selector');

    if ($selector.hasClass('select2-hidden-accessible')) {
        $selector.select2('destroy');
    }

    $selector.prop('disabled', false).data('placeholder', 'جميع محافظ الشركة');
    $selector.empty().append($('<option>', { value: '', text: 'جميع المحافظ' }));

    portfolios.forEach(portfolio => {
        $selector.append($('<option>', {
            value: portfolio.id,
            text: ` ${portfolio.name || ''}`,
        }));
    });

    $('#portfolio-wrap').removeClass('is-disabled');
    initSelect2($selector);
}

function resetPortfolioSelector(state = 'empty') {
    const $selector = $('#portfolio-selector');
    const placeholder = state === 'loading' ? 'جاري تحميل المحافظ' : 'اختر شركة أولاً';

    if ($selector.hasClass('select2-hidden-accessible')) {
        $selector.select2('destroy');
    }

    $selector
        .prop('disabled', true)
        .data('placeholder', placeholder)
        .empty()
        .append($('<option>', { value: '', text: placeholder }));

    $('#portfolio-wrap').addClass('is-disabled');
    initSelect2($selector);
    $('#kpi4-col').show();
}

function exportToExcel() {
    const filters = getExportFilters();

    $.ajax({
        url: routes.showExportModal,
        type: 'POST',
        data: { _token: reportData.csrfToken, ...filters },
        success(response) {
            if (response.success) {
                showExportModal(response.fields, response.count, filters, response.is_large_export);
            }
        },
        error() {
            showAjaxError('حدث خطأ أثناء جلب بيانات التصدير');
        },
    });
}

function showExportModal(fields, count, filters, isLarge) {
    const items = fields.fields || [];
    const grid = items.map(field => `
        <div class="col-md-4 col-sm-6">
            <div class="form-check">
                <input class="form-check-input export-field" type="checkbox" value="${escHtml(field.key)}" id="ef_${escHtml(field.key)}" ${field.selected ? 'checked' : ''}>
                <label class="form-check-label" for="ef_${escHtml(field.key)}">${escHtml(field.label)}</label>
            </div>
        </div>
    `).join('');

    $('#fieldsContainer').html(`
        <div class="alert alert-info py-2 mb-3">
            <small><strong>السجلات:</strong> ${fmt(count)} | <span id="sel-count">0</span> حقل محدد</small>
        </div>
        <div class="text-center mb-3">
            <button type="button" class="btn btn-outline-primary btn-sm px-2 mx-1" id="selAll">الكل</button>
            <button type="button" class="btn btn-outline-secondary btn-sm px-2 mx-1" id="selNone">إلغاء</button>
        </div>
        <div class="row g-2">${grid}</div>
    `);

    updateSelectedFieldsCount();
    $('#selAll').on('click', () => { $('.export-field').prop('checked', true); updateSelectedFieldsCount(); });
    $('#selNone').on('click', () => { $('.export-field').prop('checked', false); updateSelectedFieldsCount(); });
    $('.export-field').on('change', updateSelectedFieldsCount);

    $('#confirmExport').off('click').on('click', function () {
        const selected = $('.export-field:checked').map((_, element) => element.value).get();

        if (!selected.length) {
            showAjaxError('اختر حقلاً واحداً على الأقل');
            return;
        }

        $('#exportFieldsModal').modal('hide');
        doExport(filters, selected, isLarge);
    });

    $('#exportFieldsModal').modal('show');
}

function doExport(filters, fields, isLarge) {
    showLoadingModal(isLarge ? 'جاري بدء التصدير...' : 'جاري إنشاء Excel...');

    $.ajax({
        url: routes.exportExcel,
        type: 'POST',
        data: { _token: reportData.csrfToken, selected_fields: fields, ...filters },
        success(response) {
            if (!response.success) {
                hideLoadingModal();
                showAjaxError(response.message || 'تعذر إنشاء ملف التصدير');
                return;
            }

            if (response.is_async) {
                $('#loadingProgress').show();
                exportProgressInterval = setInterval(() => pollExport(response.export_id), 2000);
                return;
            }

            hideLoadingModal();
            window.open(response.download_url, '_blank');

            if (window.toastr && typeof window.toastr.success === 'function') {
                window.toastr.success(response.message || 'تم إنشاء الملف بنجاح');
            }
        },
        error() {
            hideLoadingModal();
            showAjaxError('حدث خطأ أثناء التصدير');
        },
    });
}

function pollExport(exportId) {
    $.ajax({
        url: routes.checkProgress,
        type: 'POST',
        data: { _token: reportData.csrfToken, export_id: exportId },
        success(response) {
            if (!response.success) return;

            const data = response.data || {};

            if (data.status === 'completed') {
                clearInterval(exportProgressInterval);
                hideLoadingModal();

                if (data.file_name) {
                    window.open(routes.download.replace('FILE_PLACEHOLDER', encodeURIComponent(data.file_name)), '_blank');
                }

                if (window.toastr && typeof window.toastr.success === 'function') {
                    window.toastr.success('تم إنشاء الملف بنجاح');
                }
                return;
            }

            if (data.status === 'failed') {
                clearInterval(exportProgressInterval);
                hideLoadingModal();
                showAjaxError('فشل التصدير: ' + (data.error || 'خطأ غير معروف'));
                return;
            }

            $('#loadingMessage').text(data.message || 'جاري المعالجة...');
            $('#loadingProgress .progress-bar').css('width', (data.percentage || 0) + '%');
        },
    });
}

function getExportFilters() {
    const table = getCompanyTable();

    return {
        period: activePeriod,
        company_id: $('#company-selector').val() || '',
        portfolio_id: $('#portfolio-selector').val() || '',
        status: $('#filter-status').val() || '',
        date_from: $('#filter-date-from').val() || '',
        date_to: $('#filter-date-to').val() || '',
        search_query: $('#filter-search').val() || (table ? table.search() : ''),
    };
}

function updateSelectedFieldsCount() {
    $('#sel-count').text($('.export-field:checked').length);
}

function showLoadingModal(message = 'جاري المعالجة...') {
    $('#loadingMessage').text(message);
    $('#loadingProgress').hide().find('.progress-bar').css('width', '0%');
    $('#loadingModal').modal('show');
}

function hideLoadingModal() {
    $('#loadingModal').modal('hide');
}

function setLevelCrumb(level, companyName = '', portfolioName = '') {
    const sep = '<span class="mx-1 text-muted">/</span>';
    let html = '<i class="ti ti-world me-1"></i>جميع الشركات';

    if (level === 'company') {
        html += `${sep}<i class="ti ti-building me-1"></i>${escHtml(companyName)}`;
    }

    if (level === 'portfolio') {
        html += `${sep}<i class="ti ti-building me-1"></i>${escHtml(companyName || activeCompanyMeta?.company_name || '')}`;
        html += `${sep}<i class="ti ti-briefcase me-1"></i>${escHtml(portfolioName)}`;
    }

}

function renderCharts() {
    if (typeof Chart === 'undefined') return;

    const debtCanvas = document.getElementById('debtBarChart');
    const distributionCanvas = document.getElementById('customerDoughnutChart');
    const trendCanvas = document.getElementById('collectionLineChart');

    if (debtCanvas) {
        new Chart(debtCanvas, {
            type: 'bar',
            data: {
                labels: charts.topDebt?.labels || [],
                datasets: [{
                    label: 'المديونية',
                    data: charts.topDebt?.data || [],
                    backgroundColor: '#696cff',
                    borderRadius: 5,
                    barThickness: 22,
                }],
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { callback: value => compact(value) } },
                    y: { grid: { display: false } },
                },
            },
        });
    }

    if (distributionCanvas) {
        new Chart(distributionCanvas, {
            type: 'doughnut',
            data: {
                labels: charts.distribution?.labels || [],
                datasets: [{
                    data: charts.distribution?.data || [],
                    backgroundColor: chartColors,
                    borderWidth: 2,
                }],
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 11, font: { size: 11 } } },
                },
            },
        });
    }

    if (trendCanvas) {
        const trendData = charts.collectionTrend || {};
        const isWeekly = trendData.granularity === 'weekly';
        const chartType = isWeekly ? 'bar' : 'line';
        const datasets = (trendData.datasets || []).map((dataset, index) => ({
            label: dataset.label,
            data: dataset.data,
            borderColor: chartColors[index % chartColors.length],
            backgroundColor: isWeekly
                ? chartColors[index % chartColors.length] + '99'   // 60% opacity للأعمدة
                : 'transparent',
            borderWidth: isWeekly ? 1 : 2,
            pointRadius: isWeekly ? 0 : 3,
            tension: .35,
            borderRadius: isWeekly ? 4 : 0,
        }));

        new Chart(trendCanvas, {
            type: chartType,
            data: {
                labels: trendData.labels || [],
                datasets,
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 11, font: { size: 10 } },
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)}%`,
                        },
                    },
                },
                scales: {
                    y: {
                        min: 0,
                        max: 100,
                        ticks: { callback: value => value + '%' },
                        grid: { color: 'rgba(0,0,0,.05)' },
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxRotation: isWeekly ? 20 : 0,
                            font: { size: isWeekly ? 10 : 11 },
                        },
                    },
                },
            },
        });
    }
}

function initSelect2($element) {
    if ($element.length && typeof $element.select2 === 'function') {
        $element.select2({
            dir: 'rtl',
            width: '100%',
            allowClear: true,
            placeholder: $element.data('placeholder') || 'الكل',
        });
    }
}


function changePill(value, invert, suffix) {
    if (value === null || typeof value === 'undefined') {
        return '<span class="text-muted">لا توجد فترة سابقة للمقارنة</span>';
    }

    const numeric = Number(value);
    const isUp = numeric > 0;
    const isDown = numeric < 0;
    const good = invert ? isDown : isUp;
    const cls = numeric === 0 ? 'chg-flat' : good ? 'chg-up' : 'chg-down';
    const icon = isUp ? '↑' : isDown ? '↓' : '•';

    return `<span class="chg ${cls}">${icon} ${fmtDecimal(Math.abs(numeric), 2)} ${suffix}</span>`;
}

function spinner(show) {
    loadingCount += show ? 1 : -1;
    loadingCount = Math.max(loadingCount, 0);
    $('#loading-spinner').toggleClass('d-none', loadingCount === 0);
}

function showAjaxError(message) {
    if (window.toastr && typeof window.toastr.error === 'function') {
        window.toastr.error(message);
    }
}

function buildRoute(template, token, value) {
    return String(template || '').replace(token, encodeURIComponent(value));
}

function getCompanyTable() {
    return $('#companies-table').length && $.fn.DataTable.isDataTable('#companies-table')
        ? $('#companies-table').DataTable()
        : null;
}

function fmt(value) {
    return Number(value || 0).toLocaleString({ maximumFractionDigits: 0 });
}

function fmtDecimal(value, digits = 2) {
    return Number(value || 0).toLocaleString({
        minimumFractionDigits: digits,
        maximumFractionDigits: digits,
    });
}

function currency(value) {
    return `${Number(value || 0).toLocaleString({
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} ريال`;
}

function compact(value) {
    const number = Number(value || 0);
    if (number >= 1000000) return (number / 1000000).toLocaleString({ maximumFractionDigits: 1 }) + 'M';
    if (number >= 1000) return (number / 1000).toLocaleString({ maximumFractionDigits: 0 }) + 'K';
    return number.toLocaleString('ar-SA');
}

function clamp(value, min, max) {
    return Math.min(Math.max(Number(value || 0), min), max);
}

function escHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function debounce(fn, wait) {
    let timeout = null;
    return function (...args) {
        window.clearTimeout(timeout);
        timeout = window.setTimeout(() => fn.apply(this, args), wait);
    };
}

window.exportCompaniesToExcel = exportToExcel;
