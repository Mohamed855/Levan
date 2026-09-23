/*
|--------------------------------------------------------------------------
| Admin Dashboard Charts
|--------------------------------------------------------------------------
| إدارة جميع المخططات والرسوم البيانية في لوحة الإدارة
*/

'use strict';

// متغيرات عامة لمنع التهيئة المتكررة
let statisticsInitialized = false;
let approvalsInitialized = false;

document.addEventListener('DOMContentLoaded', function () {
    initializeAllCharts();
});

/*
|--------------------------------------------------------------------------
| تهيئة جميع المخططات
|--------------------------------------------------------------------------
*/
function initializeAllCharts() {
    initializeStatisticsChart();
    initializeApprovalsChart();
}

/*
|--------------------------------------------------------------------------
| شريط الإحصائيات
|--------------------------------------------------------------------------
*/
function initializeStatisticsChart() {
    const period = document.getElementById('statisticsPeriodFilter')?.value || 'month';

    fetch(`${window.routes.statistics}?period=${period}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.data && data.data.userPerformance) {
                const performanceData = data.data.userPerformance;

                let totalCollections = 0;
                let totalFollowups = 0;
                let totalCollectionsAmount = 0;

                performanceData.forEach(item => {
                    totalCollections += (item.total_collections || 0);
                    totalFollowups += (item.follows_count || 0);
                    totalCollectionsAmount += (item.total_amount || 0);
                });

                const grandTotal = totalCollections + totalFollowups;
                let collectionsPercentage = 0;
                let followupsPercentage = 0;

                if (grandTotal > 0) {
                    collectionsPercentage = Math.round((totalCollections / grandTotal) * 100);
                    followupsPercentage = Math.round((totalFollowups / grandTotal) * 100);
                }

                // ✅ تحديث DOM مرة واحدة فقط
                updateStatisticsDOM({
                    totalCollections,
                    totalFollowups,
                    totalCollectionsAmount,
                    collectionsPercentage,
                    followupsPercentage
                });

                // ✅ تهيئة Tooltips مرة واحدة فقط
                if (!statisticsInitialized) {
                    initializeStatisticsTooltips();
                    statisticsInitialized = true;
                }
            }
        })
        .catch(error => {
            console.error('Error fetching statistics data:', error);
        });

    // ✅ إضافة Event Listener مرة واحدة فقط
    const periodFilter = document.getElementById('statisticsPeriodFilter');
    if (periodFilter && !periodFilter._listenerAttached) {
        periodFilter.addEventListener('change', function () {
            initializeStatisticsChart();
        });
        periodFilter._listenerAttached = true;
    }
}

/*
|--------------------------------------------------------------------------
| تحديث DOM للإحصائيات
|--------------------------------------------------------------------------
*/
function updateStatisticsDOM(data) {
    document.getElementById('collectionsCountTop').textContent = formatNumber(data.totalCollections);
    document.getElementById('followupsCountTop').textContent = formatNumber(data.totalFollowups);
    document.getElementById('collectionsSmallNumber').textContent = formatNumber(data.totalCollectionsAmount) + ' ريال';

    document.getElementById('collectionsProgressSegment').style.width = data.collectionsPercentage + '%';
    document.getElementById('followupsProgressSegment').style.width = data.followupsPercentage + '%';

    document.getElementById('collectionsBarText').textContent = data.collectionsPercentage + '%';
    document.getElementById('followupsBarText').textContent = data.followupsPercentage + '%';

    document.getElementById('collectionsProgressSegment').setAttribute('title', `مبلغ التحصيلات: ${formatNumber(data.totalCollectionsAmount)} ريال`);
    document.getElementById('followupsProgressSegment').setAttribute('title', `عدد المتابعات: ${formatNumber(data.totalFollowups)}`);
}

/*
|--------------------------------------------------------------------------
| تهيئة Tooltips للإحصائيات
|--------------------------------------------------------------------------
*/
function initializeStatisticsTooltips() {
    const collectionsTooltip = document.getElementById('collectionsProgressSegment');
    const followupsTooltip = document.getElementById('followupsProgressSegment');

    if (collectionsTooltip && !collectionsTooltip._bsTooltip) {
        collectionsTooltip._bsTooltip = new bootstrap.Tooltip(collectionsTooltip);
    }

    if (followupsTooltip && !followupsTooltip._bsTooltip) {
        followupsTooltip._bsTooltip = new bootstrap.Tooltip(followupsTooltip);
    }
}

/*
|--------------------------------------------------------------------------
| شريط الاعتمادات
|--------------------------------------------------------------------------
*/
function initializeApprovalsChart() {
    const period = document.getElementById('approvalsPeriodFilter')?.value || 'month';

    fetch(`${window.routes.approvals}?period=${period}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.csrfToken
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.data && data.data.userPerformance) {
                const performanceData = data.data.userPerformance;

                let totalApproved = 0;
                let totalPending = 0;

                performanceData.forEach(item => {
                    totalApproved += (item.approved_collections || 0);
                    totalPending += (item.pending_collections || 0);
                });

                const grandTotal = totalApproved + totalPending;
                let approvedPercentage = 0;
                let pendingPercentage = 0;

                if (grandTotal > 0) {
                    approvedPercentage = Math.round((totalApproved / grandTotal) * 100);
                    pendingPercentage = Math.round((totalPending / grandTotal) * 100);
                }

                // ✅ تحديث DOM مرة واحدة فقط
                updateApprovalsDOM({
                    totalApproved,
                    totalPending,
                    approvedPercentage,
                    pendingPercentage
                });

                // ✅ تهيئة Tooltips مرة واحدة فقط
                if (!approvalsInitialized) {
                    initializeApprovalsTooltips();
                    approvalsInitialized = true;
                }
            }
        })
        .catch(error => {
            console.error('Error fetching approvals data:', error);
        });

    // ✅ إضافة Event Listener مرة واحدة فقط
    const periodFilter = document.getElementById('approvalsPeriodFilter');
    if (periodFilter && !periodFilter._listenerAttached) {
        periodFilter.addEventListener('change', function () {
            initializeApprovalsChart();
        });
        periodFilter._listenerAttached = true;
    }
}

/*
|--------------------------------------------------------------------------
| تحديث DOM للاعتمادات
|--------------------------------------------------------------------------
*/
function updateApprovalsDOM(data) {
    document.getElementById('approvedCountTop').textContent = formatNumber(data.totalApproved);
    document.getElementById('pendingCountTop').textContent = formatNumber(data.totalPending);

    document.getElementById('approvedProgressSegment').style.width = data.approvedPercentage + '%';
    document.getElementById('pendingProgressSegment').style.width = data.pendingPercentage + '%';

    document.getElementById('approvedBarText').textContent = data.approvedPercentage + '%';
    document.getElementById('pendingBarText').textContent = data.pendingPercentage + '%';

    document.getElementById('approvedProgressSegment').setAttribute('title', `المعتمد: ${formatNumber(data.totalApproved)}`);
    document.getElementById('pendingProgressSegment').setAttribute('title', `قيد الانتظار: ${formatNumber(data.totalPending)}`);
}

/*
|--------------------------------------------------------------------------
| تهيئة Tooltips للاعتمادات
|--------------------------------------------------------------------------
*/
function initializeApprovalsTooltips() {
    const approvedTooltip = document.getElementById('approvedProgressSegment');
    const pendingTooltip = document.getElementById('pendingProgressSegment');

    if (approvedTooltip && !approvedTooltip._bsTooltip) {
        approvedTooltip._bsTooltip = new bootstrap.Tooltip(approvedTooltip);
    }

    if (pendingTooltip && !pendingTooltip._bsTooltip) {
        pendingTooltip._bsTooltip = new bootstrap.Tooltip(pendingTooltip);
    }
}

/*
|--------------------------------------------------------------------------
| دالة تنسيق الأرقام
|--------------------------------------------------------------------------
*/
function formatNumber(number) {
    if (typeof number !== 'number') {
        number = parseFloat(number) || 0;
    }
    return new Intl.NumberFormat('en-US').format(number);
}