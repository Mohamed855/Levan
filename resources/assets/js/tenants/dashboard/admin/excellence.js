/*
|--------------------------------------------------------------------------
| Excellence Dashboard Charts
|--------------------------------------------------------------------------
| نظام احترافي لعرض أداء الموظفين المتميزين
| يدعم التبديل بين التحصيلات والمتابعات مع فلاتر زمنية متعددة
|--------------------------------------------------------------------------
*/

'use strict';

let excellenceCharts = {
    topPerformers: null,
    distribution: null
};

let currentDataType = 'collections';
let currentPeriod = 'month';

document.addEventListener('DOMContentLoaded', function () {
    setTimeout(() => {
        if (typeof Chart !== 'undefined') {
            Chart.defaults.font.family = 'Cairo, sans-serif';
            initializeExcellenceCharts();
            initializeFilters();
        }
    }, 500);
});

/*
|--------------------------------------------------------------------------
| Initialize Excellence Charts
|--------------------------------------------------------------------------
*/
function initializeExcellenceCharts() {
    if (!window.excellenceData) {
        showAllChartsNoData();
        return;
    }

    hideAllNoDataMessages();
    initializeTopPerformersChart();
}

/*
|--------------------------------------------------------------------------
| Destroy Single Chart
|--------------------------------------------------------------------------
*/
function destroySingleChart(chartKey) {
    if (excellenceCharts[chartKey]) {
        try {
            excellenceCharts[chartKey].destroy();
        } catch (e) {
            // Silent error handling
        }
        excellenceCharts[chartKey] = null;
    }
}

/*
|--------------------------------------------------------------------------
| Destroy All Charts
|--------------------------------------------------------------------------
*/
function destroyExcellenceCharts() {
    Object.keys(excellenceCharts).forEach(key => {
        destroySingleChart(key);
    });
}

/*
|--------------------------------------------------------------------------
| Hide No Data Messages - ✅ محسّنة
|--------------------------------------------------------------------------
*/
function hideAllNoDataMessages() {
    const chartIds = ['topPerformersChart', 'distributionChart'];
    chartIds.forEach(id => {
        const canvas = document.getElementById(id);
        if (canvas) {
            // إظهار الـ Canvas
            canvas.style.display = 'block';

            // حذف جميع رسائل "لا توجد بيانات"
            const container = canvas.closest('.chart-container') || canvas.parentElement;
            if (container) {
                const messages = container.querySelectorAll('.no-data-message');
                messages.forEach(msg => msg.remove());
            }
        }
    });
}

/*
|--------------------------------------------------------------------------
| Top Performers Chart - ✅ محسّنة
|--------------------------------------------------------------------------
*/
function initializeTopPerformersChart() {
    // ✅ 1. حذف الرسائل القديمة أولاً
    hideAllNoDataMessages();

    // ✅ 2. حذف المخطط القديم
    destroySingleChart('topPerformers');

    const ctx = document.getElementById('topPerformersChart');
    if (!ctx) return;

    // ✅ 3. تحديد البيانات حسب النوع
    const data =
        currentDataType === 'collections'
            ? window.excellenceData.topPerformers || []
            : window.excellenceData.performanceDistribution || [];

    if (data.length === 0) {
        const message = currentDataType === 'collections'
            ? 'لا توجد تحصيلات للفترة المحددة'
            : 'لا توجد متابعات للفترة المحددة';
        showNoExcellenceData('topPerformersChart', message);
        return;
    }

    let chartData, sortedData, chartColor, yAxisLabel, tooltipLabel;

    if (currentDataType === 'collections') {
        sortedData = [...data]
            .filter(item => parseFloat(item.total_amount) > 0)
            .sort((a, b) => parseFloat(b.total_amount) - parseFloat(a.total_amount))
            .slice(0, 20);

        chartData = sortedData.map(item => parseFloat(item.total_amount) || 0);
        chartColor = '#4cc1bb';
        yAxisLabel = 'مبلغ التحصيلات (ريال)';
        tooltipLabel = 'المبلغ المحصل';
    } else {
        sortedData = [...data]
            .filter(item => parseInt(item.follows_count) > 0)
            .sort((a, b) => parseInt(b.follows_count) - parseInt(a.follows_count))
            .slice(0, 20);

        chartData = sortedData.map(item => parseInt(item.follows_count) || 0);
        chartColor = '#372632';
        yAxisLabel = 'عدد المتابعات';
        tooltipLabel = 'عدد المتابعات';
    }

    if (sortedData.length === 0) {
        const message = currentDataType === 'collections'
            ? 'لا توجد تحصيلات للفترة المحددة'
            : 'لا توجد متابعات للفترة المحددة';
        showNoExcellenceData('topPerformersChart', message);
        return;
    }

    try {
        // ✅ 4. إظهار الـ Canvas
        ctx.style.display = 'block';

        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, chartColor);
        gradient.addColorStop(1, chartColor + '99');

        excellenceCharts.topPerformers = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sortedData.map(item => item.user_name),
                datasets: [{
                    label: tooltipLabel,
                    data: chartData,
                    backgroundColor: gradient,
                    borderWidth: 0,
                    borderRadius: 12,
                    barThickness: 30,
                    maxBarThickness: 35,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        top: 85,
                        bottom: 10,
                        left: 10,
                        right: 10
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(21, 29, 51, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: chartColor,
                        borderWidth: 2,
                        padding: 15,
                        displayColors: false,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        callbacks: {
                            title: function (context) {
                                const index = context[0].dataIndex;
                                const rank = index + 1;
                                return `المركز ${rank}: ${context[0].label}`;
                            },
                            label: function (context) {
                                const index = context.dataIndex;
                                const employee = sortedData[index];

                                if (currentDataType === 'collections') {
                                    return [
                                        `${tooltipLabel}: ${formatNumber(context.parsed.y)} ريال`,
                                        `عدد التحصيلات: ${employee.collections_count || 0}`
                                    ];
                                } else {
                                    return [
                                        `${tooltipLabel}: ${context.parsed.y}`
                                    ];
                                }
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11, weight: '600' },
                            color: '#666',
                            maxRotation: 35,
                            minRotation: 35,
                            padding: 8,
                            autoSkip: false,
                            callback: function (value) {
                                const label = this.getLabelForValue(value) || '';
                                if (label.length > 16) return label.substring(0, 14) + '..';
                                return label;
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f0f0f0', drawBorder: false },
                        ticks: {
                            callback: function (value) {
                                if (currentDataType === 'collections') return formatNumber(value) + ' ريال';
                                return Math.floor(value);
                            },
                            font: { size: 11 },
                            color: '#666',
                            padding: 8
                        },
                        title: {
                            display: true,
                            text: yAxisLabel,
                            font: { size: 12, weight: 'bold' },
                            color: '#151d33'
                        }
                    }
                },
                animation: {
                    duration: 800,
                    easing: 'easeInOutQuart'
                }
            },
            plugins: [{
                id: 'employeeImages',
                beforeDatasetsDraw: function (chart) {
                    if (!chart.employeeImages) {
                        chart.employeeImages = {};
                    }

                    sortedData.forEach((employee) => {
                        const imageUrl = employee.image_url || '/assets/img/branding/logo.svg';
                        if (!chart.employeeImages[imageUrl]) {
                            const img = new Image();
                            img.crossOrigin = 'anonymous';
                            img.src = imageUrl;
                            img.onload = () => chart.update('none');
                            chart.employeeImages[imageUrl] = img;
                        }
                    });
                },
                afterDatasetsDraw: function (chart) {
                    const ctx = chart.ctx;
                    const meta = chart.getDatasetMeta(0);

                    meta.data.forEach((bar, index) => {
                        if (!sortedData[index]) return;

                        const employee = sortedData[index];
                        const x = bar.x;
                        const y = bar.y - 35;
                        const imageUrl = employee.image_url || '/assets/img/branding/logo.svg';
                        const img = chart.employeeImages?.[imageUrl];

                        ctx.save();

                        ctx.beginPath();
                        ctx.arc(x, y - 15, 18, 0, Math.PI * 2);
                        ctx.closePath();
                        ctx.fillStyle = '#ffffff';
                        ctx.fill();
                        ctx.lineWidth = 2.5;
                        ctx.strokeStyle = chartColor;
                        ctx.stroke();

                        if (img && img.complete && img.naturalHeight !== 0) {
                            ctx.save();
                            ctx.beginPath();
                            ctx.arc(x, y - 15, 17, 0, Math.PI * 2);
                            ctx.closePath();
                            ctx.clip();
                            ctx.drawImage(img, x - 17, y - 32, 34, 34);
                            ctx.restore();
                        }

                        ctx.restore();

                        if (index === 0) {
                            ctx.save();
                            ctx.font = '22px Arial';
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.fillText('👑', x, y - 38);
                            ctx.restore();
                        }
                    });
                }
            }]
        });

    } catch (error) {
        showNoExcellenceData('topPerformersChart', 'خطأ في إنشاء المخطط');
    }
}

/*
|--------------------------------------------------------------------------
| Initialize Filters - ✅ محسّنة
|--------------------------------------------------------------------------
*/
function initializeFilters() {
    const dataTypeTabs = document.querySelectorAll('input[name="dataTypeFilter"]');
    dataTypeTabs.forEach(tab => {
        tab.addEventListener('change', function () {
            currentDataType = this.value;

            // ✅ حذف الرسائل القديمة قبل إعادة الرسم
            hideAllNoDataMessages();

            setTimeout(() => initializeTopPerformersChart(), 100);
        });
    });

    const periodFilter = document.getElementById('excellencePeriodFilter');
    const loader = document.getElementById('excellenceLoader');

    if (!periodFilter) return;

    periodFilter.addEventListener('change', function () {
        currentPeriod = this.value;

        if (loader) loader.classList.remove('d-none');

        fetch(`${window.excellenceRoutes.getData}?period=${currentPeriod}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success && data.data) {
                    window.excellenceData = data.data;
                    setTimeout(() => {
                        destroyExcellenceCharts();
                        initializeExcellenceCharts();
                    }, 300);
                } else {
                    showAllChartsNoData();
                }
            })
            .catch(() => {
                showAllChartsError();
            })
            .finally(() => {
                if (loader) {
                    setTimeout(() => loader.classList.add('d-none'), 500);
                }
            });
    });
}

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/
function showNoExcellenceData(canvasId, message) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const container = canvas.closest('.chart-container') || canvas.parentElement;

    // إخفاء الـ Canvas
    canvas.style.display = 'none';

    // حذف الرسائل القديمة
    const oldMessages = container.querySelectorAll('.no-data-message');
    oldMessages.forEach(msg => msg.remove());

    // إضافة رسالة جديدة
    const messageDiv = document.createElement('div');
    messageDiv.className = 'no-data-message';
    messageDiv.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;';
    messageDiv.innerHTML = `
        <div class="text-center">
            <i class="ti ti-chart-bar-off" style="font-size: 3rem; color: #d9d9d9;"></i>
            <p class="mt-3 text-muted mb-0">${message}</p>
            <small class="text-muted">لا توجد بيانات للفترة المحددة</small>
        </div>
    `;

    container.appendChild(messageDiv);
}

function showAllChartsNoData() {
    showNoExcellenceData('topPerformersChart', 'لا توجد بيانات للموظفين');
    showNoExcellenceData('distributionChart', 'لا توجد بيانات توزيع الأداء');
}

function showAllChartsError() {
    showNoExcellenceData('topPerformersChart', 'حدث خطأ في تحميل البيانات');
    showNoExcellenceData('distributionChart', 'حدث خطأ في تحميل البيانات');
}

function formatNumber(number) {
    if (typeof number !== 'number') {
        number = parseFloat(number) || 0;
    }
    return new Intl.NumberFormat('en-US').format(number);
}