document.addEventListener('DOMContentLoaded', function () {
    const colors = {
        primary: '#4cc1bb',
        success: '#28a745',
        warning: '#8cb8cfff',
        orange: '#70b7c7ff',
        danger: '#dc3545',
        info: '#17a2b8',
        cyan: '#00bcd4',
        purple: '#686868ff',
        secondary: '#6c757d'
    };



    Chart.defaults.font.family = 'Cairo, sans-serif';
    Chart.defaults.color = '#666';

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    padding: 12,
                    usePointStyle: true,
                    font: {
                        size: 11
                    }
                }
            },
            tooltip: {
                backgroundColor: colors.primary,
                titleColor: '#fff',
                bodyColor: '#fff',
                padding: 10,
                cornerRadius: 6,
                displayColors: true
            }
        }
    };

    // Chart 1: العملاء الجدد
    if (dashboardData.monthlyCustomers.length > 0) {
        new Chart(document.getElementById('monthlyCustomersChart'), {
            type: 'bar',
            data: {
                labels: dashboardData.monthlyCustomers.map(item => item.month),
                datasets: [{
                    label: 'عدد العملاء',
                    data: dashboardData.monthlyCustomers.map(item => item.count),
                    backgroundColor: colors.orange,
                    borderRadius: 6,
                    barThickness: 32
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Chart 2: أعلى 10 عملاء
    if (dashboardData.topCustomers.length > 0) {
        new Chart(document.getElementById('topCustomersChart'), {
            type: 'bar',
            data: {
                labels: dashboardData.topCustomers.map(item => item.name.substring(0, 20)),
                datasets: [{
                    label: 'عدد المستخدمين',
                    data: dashboardData.topCustomers.map(item => item.users),
                    backgroundColor: colors.warning,
                    borderRadius: 6
                }]
            },
            options: {
                ...commonOptions,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Chart 3: الإيرادات الشهرية
    if (dashboardData.monthlyRevenue.length > 0) {
        new Chart(document.getElementById('monthlyRevenueChart'), {
            type: 'line',
            data: {
                labels: dashboardData.monthlyRevenue.map(item => item.month),
                datasets: [{
                    label: 'الإيرادات (ر.س)',
                    data: dashboardData.monthlyRevenue.map(item => item.revenue),
                    borderColor: colors.success,
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        },
                        ticks: {
                            callback: value => value.toLocaleString() + ' ر.س'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Chart 4: توزيع الاشتراكات
    if (dashboardData.subscriptionStatus.length > 0) {
        new Chart(document.getElementById('subscriptionStatusChart'), {
            type: 'doughnut',
            data: {
                labels: dashboardData.subscriptionStatus.map(item => item.status),
                datasets: [{
                    data: dashboardData.subscriptionStatus.map(item => item.count),
                    backgroundColor: [colors.success, colors.warning, colors.danger, colors
                        .secondary
                    ],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                ...commonOptions,
                cutout: '60%',
                plugins: {
                    ...commonOptions.plugins,
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15
                        }
                    }
                }
            }
        });
    }

    // Chart 5: استخدام المستخدمين
    if (dashboardData.userUsage.length > 0) {
        new Chart(document.getElementById('userUsageChart'), {
            type: 'bar',
            data: {
                labels: dashboardData.userUsage.map(item => item.subscription),
                datasets: [{
                    label: 'مستخدَم',
                    data: dashboardData.userUsage.map(item => item.used),
                    backgroundColor: colors.primary,
                    borderRadius: 6
                },
                {
                    label: 'متاح',
                    data: dashboardData.userUsage.map(item => item.total - item.used),
                    backgroundColor: '#e0e0e0',
                    borderRadius: 6
                }
                ]
            },
            options: {
                ...commonOptions,
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        }
                    }
                }
            }
        });
    }

    // Chart 6: الكوبونات
    if (dashboardData.couponUsage.length > 0) {
        new Chart(document.getElementById('couponUsageChart'), {
            type: 'bar',
            data: {
                labels: dashboardData.couponUsage.map(item => item.code),
                datasets: [{
                    label: 'عدد مرات الاستخدام',
                    data: dashboardData.couponUsage.map(item => item.used),
                    backgroundColor: colors.purple,
                    borderRadius: 6
                }]
            },
            options: {
                ...commonOptions,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
});
