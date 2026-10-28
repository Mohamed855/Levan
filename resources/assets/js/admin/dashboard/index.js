document.addEventListener('DOMContentLoaded', function () {

    if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
        window.jQuery('#platform-activity-range').select2({
            dir: 'rtl',
            width: '100%',
            minimumResultsForSearch: Infinity,
        });
    }

    // ── الألوان الأساسية ──────────────────────────────────────────
    const colors = {
        primary:   '#1D4F44',
        success:   '#28a745',
        warning:   '#ffc107',
        danger:    '#dc3545',
        secondary: '#6c757d',
        info:      '#17a2b8',
        dark:      '#2f3349',
    };

    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = 'Cairo, sans-serif';
        Chart.defaults.color = '#666';
    }

    // ════════════════════════════════════════════════════════
    // Chart 1: استخدام المنصة (Platform Activity)
    // ════════════════════════════════════════════════════════
    let platformChart = null;

    function buildPlatformChart(dataPoints) {
        const ctx = document.getElementById('platformActivityChart');
        if (!ctx || typeof Chart === 'undefined') return;

        if (platformChart) {
            platformChart.destroy();
        }

        platformChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels:   dataPoints.map(d => d.label),
                datasets: [{
                    label:           'النشاط الإجمالي',
                    data:            dataPoints.map(d => d.count),
                    borderColor:     colors.primary,
                    backgroundColor: 'rgba(29, 79, 68, 0.12)',
                    tension:         0.4,
                    fill:            true,
                    pointRadius:     4,
                    pointHoverRadius: 7,
                    borderWidth:     2.5,
                }]
            },
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: colors.primary,
                        titleColor:      '#fff',
                        bodyColor:       '#fff',
                        padding:         10,
                        cornerRadius:    6,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f0f0f0' },
                        ticks: { precision: 0 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxTicksLimit: 12,
                            maxRotation: 45,
                        }
                    }
                }
            }
        });
    }

    // الرسم الأولي بالبيانات المُمرَّرة من الـ backend
    if (dashboardData.platformActivity && Array.isArray(dashboardData.platformActivity) && dashboardData.platformActivity.length > 0) {
        buildPlatformChart(dashboardData.platformActivity);
    }

    // تغيير المدة عبر الـ dropdown (دعم Select2 والأحداث القياسية)
    const rangeSelect = document.getElementById('platform-activity-range');
    const chartWrap = document.querySelector('.platform-activity-chart-wrap');
    let currentRange = rangeSelect ? rangeSelect.value : null;

    function updatePlatformActivity(range) {
        if (!range) return;
        const baseUrl = dashboardData.platformActivityUrl;
        if (!baseUrl) return;

        const separator = baseUrl.includes('?') ? '&' : '?';
        const url = baseUrl + separator + 'range=' + encodeURIComponent(range);

        if (chartWrap) {
            chartWrap.style.transition = 'opacity 0.2s ease';
            chartWrap.style.opacity = '0.5';
            chartWrap.style.pointerEvents = 'none';
        }

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(r => {
                if (!r.ok) {
                    throw new Error(`HTTP error ${r.status}`);
                }
                return r.json();
            })
            .then(json => {
                if (json && Array.isArray(json.data)) {
                    buildPlatformChart(json.data);
                }
            })
            .catch(err => {
                console.error('Platform activity fetch error:', err);
                currentRange = null;
            })
            .finally(() => {
                if (chartWrap) {
                    chartWrap.style.opacity = '1';
                    chartWrap.style.pointerEvents = '';
                }
            });
    }

    function onRangeChanged(newRange) {
        const selected = newRange || (rangeSelect ? rangeSelect.value : null);
        if (!selected || selected === currentRange) return;
        currentRange = selected;
        updatePlatformActivity(selected);
    }

    if (rangeSelect) {
        rangeSelect.addEventListener('change', function () {
            onRangeChanged(this.value);
        });

        if (window.jQuery) {
            window.jQuery(rangeSelect).on('change change.select2 select2:select', function () {
                onRangeChanged(this.value);
            });
        }
    }

    // ════════════════════════════════════════════════════════
    // Chart 2: توزيع حالات الاشتراكات (Doughnut)
    // ════════════════════════════════════════════════════════
    const statusCtx = document.getElementById('subscriptionStatusChart');
    if (statusCtx && typeof Chart !== 'undefined') {
        const statusList = Array.isArray(dashboardData.subscriptionStatus) ? dashboardData.subscriptionStatus : [];
        const totalCount = statusList.reduce((acc, curr) => acc + (Number(curr.count) || 0), 0);

        const colorMap = {
            primary:   colors.primary,
            success:   colors.success,
            warning:   colors.warning,
            danger:    colors.danger,
            secondary: colors.secondary,
            dark:      colors.dark,
        };

        const isEmpty = totalCount === 0;

        const emptyTextPlugin = {
            id: 'emptyStatusPlugin',
            afterDraw(chart) {
                if (chart.config.data._isEmpty) {
                    const { ctx, chartArea: { top, left, width, height } } = chart;
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = '600 13px Cairo, sans-serif';
                    ctx.fillStyle = '#a1acb8';
                    ctx.fillText('لا توجد بيانات', left + width / 2, top + height / 2);
                    ctx.restore();
                }
            }
        };

        const chartLabels = isEmpty ? ['لا توجد بيانات'] : statusList.map(s => s.label);
        const chartData   = isEmpty ? [1] : statusList.map(s => Number(s.count) || 0);
        const chartColors = isEmpty ? ['rgba(67, 89, 113, 0.12)'] : statusList.map(s => colorMap[s.color] || '#999');

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                _isEmpty: isEmpty,
                labels:   chartLabels,
                datasets: [{
                    data:            chartData,
                    backgroundColor: chartColors,
                    borderWidth:     0,
                    hoverOffset:     isEmpty ? 0 : 8,
                }]
            },
            plugins: [emptyTextPlugin],
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                cutout:              '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled:         !isEmpty,
                        backgroundColor: '#333',
                        titleColor:      '#fff',
                        bodyColor:       '#fff',
                        padding:         10,
                        cornerRadius:    6,
                        callbacks: {
                            label: function(ctx) {
                                const item = statusList[ctx.dataIndex];
                                if (!item) return '';
                                return ` ${item.label}: ${item.count} (${item.percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // ── ضمان تحديث مقاس الرسم البياني عند تصغير أو تكبير النافذة ──
    window.addEventListener('resize', function () {
        if (platformChart) {
            platformChart.resize();
        }
    });
});
