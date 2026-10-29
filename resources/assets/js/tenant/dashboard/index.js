document.addEventListener("DOMContentLoaded", function () {
    if (window.jQuery && typeof window.jQuery.fn.select2 === "function") {
        window.jQuery("#auction-activity-range").select2({
            dir: "rtl",
            width: "100%",
            minimumResultsForSearch: Infinity,
        });
    }

    // ── الألوان الأساسية الخاصة بهوية لوت─────────────────────────
    const colors = {
        primary: "#1D4F44",
        success: "#28a745",
        warning: "#ffc107",
        danger: "#dc3545",
        secondary: "#BF874A",
        info: "#17a2b8",
    };

    if (typeof Chart !== "undefined") {
        Chart.defaults.font.family = "Cairo, sans-serif";
        Chart.defaults.color = "#666";
    }

    const dashboardData = window.tenantDashboardData || {};

    // ════════════════════════════════════════════════════════
    // Chart 1: حركة المزادات والعمليات (Auction Activity)
    // ════════════════════════════════════════════════════════
    let auctionChart = null;

    function buildAuctionChart(dataPoints) {
        const ctx = document.getElementById("auctionActivityChart");
        if (!ctx || typeof Chart === "undefined") return;

        if (auctionChart) {
            auctionChart.destroy();
        }

        auctionChart = new Chart(ctx, {
            type: "line",
            data: {
                labels: dataPoints.map((d) => d.label),
                datasets: [
                    {
                        label: "عدد المزادات",
                        data: dataPoints.map((d) => d.count),
                        borderColor: colors.primary,
                        backgroundColor: "rgba(29, 79, 68, 0.12)",
                        tension: 0.35,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        borderWidth: 2.5,
                        yAxisID: "y",
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: "index",
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: colors.primary,
                        titleColor: "#fff",
                        bodyColor: "#fff",
                        padding: 10,
                        cornerRadius: 6,
                        callbacks: {
                            label: function (context) {
                                const index = context.dataIndex;
                                const point = dataPoints[index] || {};
                                return ` عدد المزادات: ${point.count || 0}`;
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: "#f0f0f0" },
                        ticks: { precision: 0 },
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxTicksLimit: 12,
                            maxRotation: 45,
                        },
                    },
                },
            },
        });
    }

    if (
        dashboardData.auctionActivity &&
        Array.isArray(dashboardData.auctionActivity) &&
        dashboardData.auctionActivity.length > 0
    ) {
        buildAuctionChart(dashboardData.auctionActivity);
    }

    // تغيير المدة عبر الـ dropdown (دعم Select2 والأحداث القياسية)
    const rangeSelect = document.getElementById("auction-activity-range");
    const chartWrap = document.querySelector(".auction-activity-chart-wrap");
    let currentRange = rangeSelect ? rangeSelect.value : null;

    function updateAuctionActivity(range) {
        if (!range) return;
        const baseUrl = dashboardData.auctionActivityUrl;
        if (!baseUrl) return;

        const separator = baseUrl.includes("?") ? "&" : "?";
        const url = baseUrl + separator + "range=" + encodeURIComponent(range);

        if (chartWrap) {
            chartWrap.style.transition = "opacity 0.2s ease";
            chartWrap.style.opacity = "0.5";
            chartWrap.style.pointerEvents = "none";
        }

        fetch(url, {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "same-origin",
        })
            .then((r) => {
                if (!r.ok) {
                    throw new Error(`HTTP error ${r.status}`);
                }
                return r.json();
            })
            .then((json) => {
                if (json && Array.isArray(json.data)) {
                    buildAuctionChart(json.data);
                }
            })
            .catch((err) => {
                console.error("Auction activity fetch error:", err);
                currentRange = null;
            })
            .finally(() => {
                if (chartWrap) {
                    chartWrap.style.opacity = "1";
                    chartWrap.style.pointerEvents = "";
                }
            });
    }

    function onRangeChanged(newRange) {
        const selected = newRange || (rangeSelect ? rangeSelect.value : null);
        if (!selected || selected === currentRange) return;
        currentRange = selected;
        updateAuctionActivity(selected);
    }

    if (rangeSelect) {
        rangeSelect.addEventListener("change", function () {
            onRangeChanged(this.value);
        });

        if (window.jQuery) {
            window
                .jQuery(rangeSelect)
                .on("change change.select2 select2:select", function () {
                    onRangeChanged(this.value);
                });
        }
    }

    // ════════════════════════════════════════════════════════
    // Chart 2: توزيع حالات العقارات (Doughnut)
    // ════════════════════════════════════════════════════════
    const statusCtx = document.getElementById("propertyStatusChart");
    if (statusCtx && typeof Chart !== "undefined") {
        const statusList = Array.isArray(dashboardData.propertyStatus)
            ? dashboardData.propertyStatus
            : [];
        const totalCount = statusList.reduce(
            (acc, curr) => acc + (Number(curr.count) || 0),
            0,
        );

        const colorMap = {
            primary: colors.primary,
            success: colors.success,
            warning: colors.warning,
            danger: colors.danger,
            secondary: colors.secondary,
            info: colors.info,
        };

        const isEmpty = totalCount === 0;

        const emptyTextPlugin = {
            id: "emptyStatusPlugin",
            afterDraw(chart) {
                if (chart.config.data._isEmpty) {
                    const {
                        ctx,
                        chartArea: { top, left, width, height },
                    } = chart;
                    ctx.save();
                    ctx.textAlign = "center";
                    ctx.textBaseline = "middle";
                    ctx.font = "600 13px Cairo, sans-serif";
                    ctx.fillStyle = "#a1acb8";
                    ctx.fillText(
                        "لا توجد بيانات",
                        left + width / 2,
                        top + height / 2,
                    );
                    ctx.restore();
                }
            },
        };

        const chartLabels = isEmpty
            ? ["لا توجد بيانات"]
            : statusList.map((s) => s.label);

        const chartData = isEmpty
            ? [1]
            : statusList.map((s) => Number(s.count) || 0);

        const chartColors = isEmpty
            ? ["rgba(67, 89, 113, 0.12)"]
            : statusList.map((s) => colorMap[s.color] || "#999");

        new Chart(statusCtx, {
            type: "doughnut",
            data: {
                _isEmpty: isEmpty,
                labels: chartLabels,
                datasets: [
                    {
                        data: chartData,
                        backgroundColor: chartColors,
                        borderWidth: 0,
                        hoverOffset: isEmpty ? 0 : 8,
                    },
                ],
            },
            plugins: [emptyTextPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "65%",
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: !isEmpty,
                        backgroundColor: "#333",
                        titleColor: "#fff",
                        bodyColor: "#fff",
                        padding: 10,
                        cornerRadius: 6,
                        callbacks: {
                            label: function (ctx) {
                                const item = statusList[ctx.dataIndex];
                                if (!item) return "";
                                return ` ${item.label}: ${item.count} (${item.percentage}%)`;
                            },
                        },
                    },
                },
            },
        });
    }

    window.addEventListener("resize", function () {
        if (auctionChart) {
            auctionChart.resize();
        }
    });
});
