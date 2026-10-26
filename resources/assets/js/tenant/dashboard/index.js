document.addEventListener('DOMContentLoaded', function () {
    if (window.jQuery && typeof window.jQuery.fn.select2 === 'function') {
        window.jQuery('#auction-activity-range').select2({
            dir: 'rtl',
            width: '100%',
            minimumResultsForSearch: Infinity,
        });
    }

    // ── الألوان الأساسية الخاصة بهوية ليفان ─────────────────────────
    const colors = {
        primary:   '#1D4F44',
        success:   '#28a745',
        warning:   '#ffc107',
        danger:    '#dc3545',
        secondary: '#6c757d',
        info:      '#17a2b8',
    };

    if (typeof Chart !== 'undefined') {
        Chart.defaults.font.family = 'Cairo, sans-serif';
        Chart.defaults.color = '#666';
    }

    const dashboardData = window.tenantDashboardData || {};

    // ════════════════════════════════════════════════════════
    // Chart 1: حركة المزادات والعمليات (Auction Activity)
    // ════════════════════════════════════════════════════════
    let auctionChart = null;

    function buildAuctionChart(dataPoints) {
        const ctx = document.getElementById('auctionActivityChart');
        if (!ctx || typeof Chart === 'undefined') return;

        if (auctionChart) {
            auctionChart.destroy();
        }

        auctionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dataPoints.map(d => d.label),
                datasets: [
                    {
                        label: 'عدد المزادات والصفقات',
                        data: dataPoints.map(d => d.count),
                        borderColor: colors.primary,
                        backgroundColor: 'rgba(29, 79, 68, 0.12)',
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        borderWidth: 2.5,
                        yAxisID: 'y',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: colors.primary,
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 10,
                        cornerRadius: 6,
                        callbacks: {
                            label: function (context) {
                                const index = context.dataIndex;
                                const point = dataPoints[index] || {};
                                const sales = point.sales ? new Intl.NumberFormat('ar-SA').format(point.sales) + ' ر.س' : '0 ر.س';
                                return [
                                    ` المزادات المنفذة: ${point.count || 0}`,
                                    ` المبيعات: ${sales}`
                                ];
                            }
                        }
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

    if (dashboardData.auctionActivity && dashboardData.auctionActivity.length > 0) {
        buildAuctionChart(dashboardData.auctionActivity);
    }

    const rangeSelect = document.getElementById('auction-activity-range');
    if (rangeSelect) {
        rangeSelect.addEventListener('change', function () {
            const range = this.value;
            const url = (dashboardData.auctionActivityUrl || '') + '?range=' + encodeURIComponent(range);

            fetch(url, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(json => {
                    if (json.data) {
                        buildAuctionChart(json.data);
                    }
                })
                .catch(err => console.error('Auction activity fetch error:', err));
        });
    }

    // ════════════════════════════════════════════════════════
    // Chart 2: توزيع حالات المحفظة العقارية (Doughnut)
    // ════════════════════════════════════════════════════════
    const statusCtx = document.getElementById('propertyStatusChart');
    if (statusCtx && dashboardData.propertyStatus && dashboardData.propertyStatus.length > 0 && typeof Chart !== 'undefined') {
        const colorMap = {
            primary:   colors.primary,
            success:   colors.success,
            warning:   colors.warning,
            danger:    colors.danger,
            secondary: colors.secondary,
            info:      colors.info,
        };

        const chartColors = dashboardData.propertyStatus.map(s => colorMap[s.color] || '#999');

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: dashboardData.propertyStatus.map(s => s.label),
                datasets: [{
                    data: dashboardData.propertyStatus.map(s => s.count),
                    backgroundColor: chartColors,
                    borderWidth: 0,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#333',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 10,
                        cornerRadius: 6,
                        callbacks: {
                            label: function (ctx) {
                                const item = dashboardData.propertyStatus[ctx.dataIndex];
                                return ` ${item.label}: ${item.count} (${item.percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    window.addEventListener('resize', function () {
        if (auctionChart) {
            auctionChart.resize();
        }
    });
});
