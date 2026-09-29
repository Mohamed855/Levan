document.addEventListener('DOMContentLoaded', function () {

    // ── الألوان الأساسية ──────────────────────────────────────────
    const colors = {
        primary:   '#4cc1bb',
        success:   '#28a745',
        warning:   '#ffc107',
        danger:    '#dc3545',
        secondary: '#6c757d',
        info:      '#17a2b8',
    };

    Chart.defaults.font.family = 'Cairo, sans-serif';
    Chart.defaults.color = '#666';

    // ════════════════════════════════════════════════════════
    // Chart 1: استخدام المنصة (Platform Activity)
    // ════════════════════════════════════════════════════════
    let platformChart = null;

    function buildPlatformChart(dataPoints) {
        const ctx = document.getElementById('platformActivityChart');
        if (!ctx) return;

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
                    backgroundColor: 'rgba(76, 193, 187, 0.12)',
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
    if (dashboardData.platformActivity && dashboardData.platformActivity.length > 0) {
        buildPlatformChart(dashboardData.platformActivity);
    }

    // تغيير المدة عبر الـ dropdown
    const rangeSelect = document.getElementById('platform-activity-range');
    if (rangeSelect) {
        rangeSelect.addEventListener('change', function () {
            const range = this.value;
            const url   = dashboardData.platformActivityUrl + '?range=' + range;

            fetch(url)
                .then(r => r.json())
                .then(json => {
                    if (json.data) buildPlatformChart(json.data);
                })
                .catch(err => console.error('Platform activity fetch error:', err));
        });
    }

    // ════════════════════════════════════════════════════════
    // Chart 2: توزيع حالات الاشتراكات (Doughnut)
    // ════════════════════════════════════════════════════════
    const statusCtx = document.getElementById('subscriptionStatusChart');
    if (statusCtx && dashboardData.subscriptionStatus && dashboardData.subscriptionStatus.length > 0) {

        const colorMap = {
            primary:   colors.primary,
            success:   colors.success,
            warning:   colors.warning,
            danger:    colors.danger,
            secondary: colors.secondary,
        };

        const chartColors = dashboardData.subscriptionStatus.map(s => colorMap[s.color] || '#999');

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels:   dashboardData.subscriptionStatus.map(s => s.label),
                datasets: [{
                    data:            dashboardData.subscriptionStatus.map(s => s.count),
                    backgroundColor: chartColors,
                    borderWidth:     0,
                    hoverOffset:     8,
                }]
            },
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                cutout:              '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#333',
                        titleColor:      '#fff',
                        bodyColor:       '#fff',
                        padding:         10,
                        cornerRadius:    6,
                        callbacks: {
                            label: function(ctx) {
                                const item = dashboardData.subscriptionStatus[ctx.dataIndex];
                                return ` ${item.label}: ${item.count} (${item.percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
});
