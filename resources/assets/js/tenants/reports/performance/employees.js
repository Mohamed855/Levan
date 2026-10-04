'use strict';

// ── حالة عامة ──────────────────────────────────────────────────────────────
let employeePerformanceTable = null;
let exportProgressInterval = null;
let currentExportId = null;
let charts = {};           // يحتوي على مثيلات ApexCharts الأربعة

// ── ألوان موحدة ─────────────────────────────────────────────────────────────
const PALETTE = ['#696cff', '#03c3ec', '#71dd37', '#ffab00', '#ff3e1d', '#26c6da', '#8a2be2', '#fd7e14', '#20c997', '#e83e8c'];

// ════════════════════════════════════════════════════════════════════════════
//  Bootstrap
// ════════════════════════════════════════════════════════════════════════════
$(document).ready(function () {
    initSelect2();
    setDefaultDates();
    bindFilters();
    bindCascadeFilters();
    bindReset();
    bindExportButtons();
    waitForTableThenInit();

    // رسم المخططات من البيانات الأولية (server-side)
    renderAllCharts(window.initialChartData);
});

// ════════════════════════════════════════════════════════════════════════════
//  Select2
// ════════════════════════════════════════════════════════════════════════════
function initSelect2() {
    $('.select2').each(function () {
        $(this).select2({
            placeholder: $(this).data('placeholder'),
            allowClear: true,
            width: '100%',
            dir: 'rtl',
            language: { noResults: () => 'لا توجد نتائج', searching: () => 'جاري البحث...' },
        });
    });
}

function adjustStatLayout(filters) {
    const hasUser = !!filters.user_id;

    // الكاردات التي تُخفى عند تحديد موظف
    const showEmployees = !hasUser;
    const showAchieving = !hasUser;

    $('#stat-col-employees').toggle(showEmployees);
    $('#stat-col-achieving').toggle(showAchieving);

    // ── الصف الأول ──
    const row1Count = [showEmployees, true, true, true].filter(Boolean).length;
    const row1Class = row1Count === 4 ? 'col-sm-6 col-xl-3' : 'col-md-4';
    const row1Remove = 'col-sm-6 col-xl-3 col-md-4';

    ['#stat-col-employees', '#stat-col-customers', '#stat-col-collection', '#stat-col-portfolio']
        .forEach(id => $(id).removeClass(row1Remove).addClass(row1Class));

    // ── الصف الثاني ──
    const row2Count = [true, showAchieving, true].filter(Boolean).length;
    const row2Class = row2Count === 3 ? 'col-sm-6 col-xl-4' : 'col-md-6';
    const row2Remove = 'col-sm-6 col-xl-4 col-md-6';

    ['#stat-col-rate', '#stat-col-achieving', '#stat-col-avg']
        .forEach(id => $(id).removeClass(row2Remove).addClass(row2Class));
}


// ════════════════════════════════════════════════════════════════════════════
//  التواريخ الافتراضية
// ════════════════════════════════════════════════════════════════════════════
function setDefaultDates() {
    const d = new Date();
    if (!$('#filter-year').val()) $('#filter-year').val(d.getFullYear()).trigger('change.select2');
    if (!$('#filter-month').val()) $('#filter-month').val(d.getMonth() + 1).trigger('change.select2');
}

// ════════════════════════════════════════════════════════════════════════════
//  الفلاتر — AJAX بدون reload
// ════════════════════════════════════════════════════════════════════════════
function bindFilters() {
    const selectors = '#filter-year,#filter-month,#filter-company,#filter-portfolio,#filter-branch,#filter-team,#filter-user';

    $(selectors).on('change', debounce(function () {
        fetchStats();
    }, 600));
}

/**
 * جلب الإحصائيات والمخططات عبر AJAX — لا reload للصفحة
 */
function fetchStats() {
    const filters = getCurrentFilters();

    setLoadingState(true);

    $.ajax({
        url: window.routes.stats,
        type: 'POST',
        data: { _token: window.csrfToken, ...filters },
        success: function (res) {
            if (!res.success) { toastr.error('تعذر جلب البيانات'); return; }
            updateStatCards(res.statistics);
            renderAllCharts(res.chart_data);
            adjustStatLayout(getCurrentFilters());
        },
        error: function () { toastr.error('حدث خطأ أثناء جلب البيانات'); },
        complete: function () { setLoadingState(false); },
    });
}

/**
 * تحديث بطاقات الإحصائيات
 */
function updateStatCards(stats) {
    $('#stat-total-employees').text(fmt(stats.total_employees));
    $('#stat-total-customers').text(fmt(stats.total_customers));
    $('#stat-total-collection').text(fmt(stats.total_collection));
    $('#stat-total-portfolio').text(fmt(stats.total_portfolio));
    $('#stat-collection-percentage').text(fmtDec(stats.collection_percentage, 2) + '%');
    $('#stat-achieving-target').text(fmt(stats.employees_achieving_target));
    $('#stat-total-emp-sub').text(fmt(stats.total_employees));
    $('#stat-avg-performance').text(fmtDec(stats.average_performance, 1) + '%');
}

/**
 * تعيين حالة التحميل (تعطيل الفلاتر مؤقتاً)
 */
function setLoadingState(loading) {
    $('#filters-card').toggleClass('filter-loading', loading);
    $('#charts-row, #stats-row-1, #stats-row-2').toggleClass('filter-loading', loading);
}

// ════════════════════════════════════════════════════════════════════════════
//  DataTable
// ════════════════════════════════════════════════════════════════════════════
function waitForTableThenInit() {
    const timer = setInterval(function () {
        if ($.fn.DataTable.isDataTable('#employee-performance-table')) {
            employeePerformanceTable = $('#employee-performance-table').DataTable();
            reloadDataTable();
            clearInterval(timer);
        }
    }, 400);
}

function reloadDataTable() {
    if (!employeePerformanceTable) return;
    const params = buildQueryString(getCurrentFilters());
    const url = params ? `${window.routes.index}?${params}` : window.routes.index;
    employeePerformanceTable.ajax.url(url).load();
}

// ════════════════════════════════════════════════════════════════════════════
//  المخططات الأربعة — ApexCharts
// ════════════════════════════════════════════════════════════════════════════

function renderAllCharts(data) {
    if (!data) return;

    renderAreaChart('chart-collections', data.collections, 'التحصيل', 'ريال', PALETTE[5]);
    renderColumnChart('chart-follows', data.follows, 'عدد المتابعات', 'متابعة', PALETTE[0]);
    renderDonutChart('chart-not-contacted', data.overdue_tasks || data.not_contacted, 'المهام المتأخرة', 'مهمة');
    renderRadialChart('chart-targets', data.targets, 'نسبة تحقيق الهدف', '%', PALETTE[2]);
}

function prepareChart(elId, chartData, limit = 8) {
    const el = document.getElementById(elId);
    if (!el) return null;

    if (charts[elId]) {
        charts[elId].destroy();
        delete charts[elId];
    }

    const labels = (chartData?.labels || []).slice(0, limit).map(l => truncate(l, 14));
    const data = (chartData?.data || []).slice(0, limit).map(v => Number(v || 0));



    return { el, labels, data };
}


function renderAreaChart(elId, chartData, seriesName, unit, color) {
    const prepared = prepareChart(elId, chartData, 8);
    if (!prepared) return;

    const { el, labels, data } = prepared;

    const options = {
        series: [{ name: seriesName, data }],
        chart: {
            type: 'bar',
            height: 280,
            fontFamily: 'Cairo, Tajawal, sans-serif',
            toolbar: { show: false },
            animations: { enabled: true, speed: 500 },
        },
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 8,
                columnWidth: '45%',
                distributed: true,
            },
        },
        colors: data.map((_, i) => PALETTE[i % PALETTE.length]),
        dataLabels: {
            enabled: true,
            formatter: val => fmtShort(val),
            style: { fontSize: '10px', fontWeight: '700' },
        },
        xaxis: {
            categories: labels,
            labels: {
                rotate: -35,
                style: { fontSize: '11px' },
            },
        },
        yaxis: {
            labels: {
                formatter: val => fmtShort(val),
                style: { fontSize: '11px' },
            },
        },
        legend: { show: false },
        grid: { borderColor: '#f0f0f0' },
        tooltip: {
            y: { formatter: val => `${fmtDec(val, 2)} ${unit}` },
        },
    };

    charts[elId] = new ApexCharts(el, options);
    charts[elId].render();
}


function renderColumnChart(elId, chartData, seriesName, unit, color) {
    const prepared = prepareChart(elId, chartData, 8);
    if (!prepared) return;

    const { el, labels, data } = prepared;

    const options = {
        series: [{ name: seriesName, data }],
        chart: {
            type: 'bar',
            height: 280,
            fontFamily: 'Cairo, Tajawal, sans-serif',
            toolbar: { show: false },
            animations: { enabled: true, speed: 500 },
        },
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 8,
                columnWidth: '45%',
                distributed: true,
            },
        },
        colors: data.map((_, i) => PALETTE[i % PALETTE.length]),
        dataLabels: {
            enabled: true,
            formatter: val => fmtShort(val),
            style: { fontSize: '10px', fontWeight: '700' },
        },
        xaxis: {
            categories: labels,
            labels: {
                rotate: -35,
                style: { fontSize: '11px' },
            },
        },
        yaxis: {
            labels: {
                formatter: val => fmtShort(val),
                style: { fontSize: '11px' },
            },
        },
        legend: { show: false },
        grid: { borderColor: '#f0f0f0' },
        tooltip: {
            y: { formatter: val => `${fmtDec(val, 2)} ${unit}` },
        },
    };

    charts[elId] = new ApexCharts(el, options);
    charts[elId].render();
}

function renderDonutChart(elId, chartData, seriesName, unit, color) {
    const prepared = prepareChart(elId, chartData, 8);
    if (!prepared) return;

    const { el, labels, data } = prepared;

    const options = {
        series: [{ name: seriesName, data }],
        chart: {
            type: 'bar',
            height: 280,
            fontFamily: 'Cairo, Tajawal, sans-serif',
            toolbar: { show: false },
            animations: { enabled: true, speed: 500 },
        },
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 8,
                columnWidth: '45%',
                distributed: true,
            },
        },
        colors: data.map((_, i) => PALETTE[i % PALETTE.length]),
        dataLabels: {
            enabled: true,
            formatter: val => fmtShort(val),
            style: { fontSize: '10px', fontWeight: '700' },
        },
        xaxis: {
            categories: labels,
            labels: {
                rotate: -35,
                style: { fontSize: '11px' },
            },
        },
        yaxis: {
            labels: {
                formatter: val => fmtShort(val),
                style: { fontSize: '11px' },
            },
        },
        legend: { show: false },
        grid: { borderColor: '#f0f0f0' },
        tooltip: {
            y: { formatter: val => `${fmtDec(val, 2)} ${unit}` },
        },
    };

    charts[elId] = new ApexCharts(el, options);
    charts[elId].render();
}

function renderRadialChart(elId, chartData, seriesName, unit, color) {
    const prepared = prepareChart(elId, chartData, 8);
    if (!prepared) return;

    const { el, labels, data } = prepared;

    const options = {
        series: [{ name: seriesName, data }],
        chart: {
            type: 'bar',
            height: 280,
            fontFamily: 'Cairo, Tajawal, sans-serif',
            toolbar: { show: false },
            animations: { enabled: true, speed: 500 },
        },
        plotOptions: {
            bar: {
                horizontal: false,
                borderRadius: 8,
                columnWidth: '45%',
                distributed: true,
            },
        },
        colors: data.map((_, i) => PALETTE[i % PALETTE.length]),
        dataLabels: {
            enabled: true,
            formatter: val => fmtShort(val),
            style: { fontSize: '10px', fontWeight: '700' },
        },
        xaxis: {
            categories: labels,
            labels: {
                rotate: -35,
                style: { fontSize: '11px' },
            },
        },
        yaxis: {
            labels: {
                formatter: val => fmtShort(val),
                style: { fontSize: '11px' },
            },
        },
        legend: { show: false },
        grid: { borderColor: '#f0f0f0' },
        tooltip: {
            y: { formatter: val => `${fmtDec(val, 2)} ${unit}` },
        },
    };

    charts[elId] = new ApexCharts(el, options);
    charts[elId].render();
}

// ════════════════════════════════════════════════════════════════════════════
//  Cascade Filters
// ════════════════════════════════════════════════════════════════════════════
function bindCascadeFilters() {
    $('#filter-company').on('change', function () {
        const id = $(this).val();
        cascadeOptions('#filter-portfolio', 'company', id);
    });

    $('#filter-branch').on('change', function () {
        const id = $(this).val();
        cascadeOptions('#filter-team', 'branch', id);
    });

    $('#filter-team').on('change', function () {
        const id = $(this).val();
        cascadeOptions('#filter-user', 'team', id);
    });
}

function cascadeOptions(selectId, dataAttr, parentId) {
    const $sel = $(selectId);
    $sel.find('option').each(function () {
        const opt = $(this);
        if (!opt.val()) { opt.show(); return; }
        opt.toggle(!parentId || opt.data(dataAttr) == parentId);
    });

    const selected = $sel.val();
    if (selected && $sel.find('option:selected').data(dataAttr) != parentId) {
        $sel.val('').trigger('change');
    }
}

// ════════════════════════════════════════════════════════════════════════════
//  إعادة تعيين
// ════════════════════════════════════════════════════════════════════════════
function bindReset() {
    // داخل bindReset — بعد fetchStats
    $('#reset-filters').on('click', function () {
        $('#filter-company,#filter-portfolio,#filter-branch,#filter-team,#filter-user')
            .val('').trigger('change');
        setDefaultDates();
        adjustStatLayout(getCurrentFilters()); // ← الإضافة
        fetchStats();
        reloadDataTable();
    });
}

// ════════════════════════════════════════════════════════════════════════════
//  الفلاتر الحالية
// ════════════════════════════════════════════════════════════════════════════
function getCurrentFilters() {
    const year = $('#filter-year').val();
    const month = $('#filter-month').val();
    let dateFrom = '', dateTo = '';

    if (year && month) {
        const m = month.toString().padStart(2, '0');
        const lastDay = new Date(year, month, 0).getDate();
        dateFrom = `${year}-${m}-01`;
        dateTo = `${year}-${m}-${lastDay}`;
    }

    return {
        date_from: dateFrom,
        date_to: dateTo,
        company_id: $('#filter-company').val() || '',
        portfolio_id: $('#filter-portfolio').val() || '',
        branch_id: $('#filter-branch').val() || '',
        team_id: $('#filter-team').val() || '',
        user_id: $('#filter-user').val() || '',
    };
}

function buildQueryString(filters) {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([k, v]) => { if (v) params.append(k, v); });
    return params.toString();
}

// ════════════════════════════════════════════════════════════════════════════
//  التصدير
// ════════════════════════════════════════════════════════════════════════════
function bindExportButtons() {
    $(document).on('click', '.btn-export-pdf', exportToPDF);
    $(document).on('click', '.btn-export-excel', exportToExcel);
}

function exportToPDF() {
    const filters = getCurrentFilters();
    const form = $('<form method="POST" target="_blank">').attr('action', window.routes.exportPDF);
    form.append(`<input name="_token" value="${window.csrfToken}" type="hidden">`);
    Object.entries(filters).forEach(([k, v]) => {
        if (v) form.append(`<input name="${k}" value="${v}" type="hidden">`);
    });
    $('body').append(form);
    form.submit().remove();
}

function exportToExcel() {
    const filters = getCurrentFilters();
    $.ajax({
        url: window.routes.showExportModal,
        type: 'POST',
        data: { _token: window.csrfToken, ...filters },
        success: function (res) {
            if (res.success) showExportModal(res.fields, res.count, filters, res.is_large_export);
        },
        error: () => toastr.error('حدث خطأ أثناء جلب بيانات التصدير'),
    });
}

function showExportModal(fields, count, filters, isLarge) {
    const items = fields.fields;
    const grid = items.map(f => `
        <div class="col-md-4 col-sm-6">
            <div class="form-check">
                <input class="form-check-input export-field" type="checkbox" value="${f.key}" id="ef_${f.key}" ${f.selected ? 'checked' : ''}>
                <label class="form-check-label" for="ef_${f.key}">${f.label}</label>
            </div>
        </div>`).join('');

    $('#fieldsContainer').html(`
        <div class="alert alert-info py-2 mb-3">
            <small><strong>السجلات:</strong> ${fmt(count)} | <span id="sel-count">0</span> حقل محدد</small>
        </div>
        <div class="text-center mb-3">
            <button type="button" class="btn btn-outline-primary btn-sm px-2 mx-1"  id="selAll">الكل</button>
            <button type="button" class="btn btn-outline-secondary btn-sm px-2 mx-1" id="selNone">إلغاء</button>
        </div>
        <div class="row g-2">${grid}</div>
    `);

    updateSelCount();
    $('#selAll').on('click', () => { $('.export-field').prop('checked', true); updateSelCount(); });
    $('#selNone').on('click', () => { $('.export-field').prop('checked', false); updateSelCount(); });
    $('.export-field').on('change', updateSelCount);

    $('#confirmExport').off('click').on('click', function () {
        const selected = $('.export-field:checked').map((_, el) => el.value).get();
        if (!selected.length) { toastr.warning('اختر حقلاً واحداً على الأقل'); return; }
        $('#exportFieldsModal').modal('hide');
        doExport(filters, selected, isLarge);
    });

    $('#exportFieldsModal').modal('show');
}

function updateSelCount() {
    $('#sel-count').text($('.export-field:checked').length);
}

function doExport(filters, fields, isLarge) {
    isLarge ? showLoadingModal('جاري بدء التصدير...') : showLoadingModal('جاري إنشاء Excel...');

    $.ajax({
        url: window.routes.exportExcel,
        type: 'POST',
        data: { _token: window.csrfToken, selected_fields: fields, ...filters },
        success: function (res) {
            if (!res.success) { hideLoadingModal(); toastr.error(res.message || 'خطأ'); return; }
            if (res.is_async) {
                currentExportId = res.export_id;
                $('#loadingProgress').show();
                exportProgressInterval = setInterval(() => pollExport(res.export_id), 2000);
            } else {
                hideLoadingModal();
                window.open(res.download_url, '_blank');
                toastr.success(res.message);
            }
        },
        error: () => { hideLoadingModal(); toastr.error('حدث خطأ أثناء التصدير'); },
    });
}

function pollExport(id) {
    $.ajax({
        url: window.routes.checkProgress,
        type: 'POST',
        data: { _token: window.csrfToken, export_id: id },
        success: function (res) {
            if (!res.success) return;
            const d = res.data;
            if (d.status === 'completed') {
                clearInterval(exportProgressInterval);
                hideLoadingModal();
                window.open(window.routes.download.replace('FILE_PLACEHOLDER', d.file_name), '_blank');
                toastr.success('تم إنشاء الملف بنجاح');
            } else if (d.status === 'failed') {
                clearInterval(exportProgressInterval);
                hideLoadingModal();
                toastr.error('فشل التصدير: ' + (d.error || 'خطأ غير معروف'));
            } else {
                $('#loadingMessage').text(d.message || 'جاري المعالجة...');
                $('#loadingProgress .progress-bar').css('width', (d.percentage || 0) + '%');
            }
        },
    });
}

function showLoadingModal(msg = 'جاري المعالجة...') {
    $('#loadingMessage').text(msg);
    $('#loadingProgress').hide().find('.progress-bar').css('width', '0%');
    $('#loadingModal').modal('show');
}
function hideLoadingModal() { $('#loadingModal').modal('hide'); }

// ════════════════════════════════════════════════════════════════════════════
//  Helpers
// ════════════════════════════════════════════════════════════════════════════
function fmt(n) { return Number(n || 0).toLocaleString(); }
function fmtDec(n, d = 2) { return Number(n || 0).toLocaleString({ minimumFractionDigits: d, maximumFractionDigits: d }); }
function fmtShort(n) {
    const v = Number(n || 0);
    if (v >= 1_000_000) return (v / 1_000_000).toFixed(1) + 'M';
    if (v >= 1_000) return (v / 1_000).toFixed(0) + 'K';
    return v.toLocaleString();
}
function truncate(str, len) { return str && str.length > len ? str.slice(0, len) + '…' : (str || ''); }
function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

// ── تصدير عام (يُستخدم من زر خارج الـ scope) ───────────────────────────────
window.exportEmployeesToPDF = exportToPDF;
window.exportEmployeesToExcel = exportToExcel;
