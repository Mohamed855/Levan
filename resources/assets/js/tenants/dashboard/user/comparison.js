/*
|---------------------------------------------------------------------------
| User Comparison Charts — Final Polished (+ Highlight Current User Name)
|---------------------------------------------------------------------------
| - صور المستخدمين دائماً فوق قمم الأعمدة (خارج العمود شكلياً)
| - Headroom ديناميكي للمحور Y (suggestedMax)
| - عرض Top 20 دائماً
| - تصفية القيم الصفرية بذكاء:
|     * إن وُجدت قيم > 0: اعرض فقط من لديهم قيمة > 0
|     * إن كان المستخدم الحالي = 0: أضفه بآخر القائمة بعد آخر صاحب قيمة > 0
|     * إن لم توجد قيم > 0: لا يوجد مخطط (رسالة "لا توجد بيانات")
| - لون المتابعات #1D4F44 بتدرّج، والتحصيلات كما هي
| - لا نعرض "undefined" إطلاقًا
| - شارة الترتيب بأسفل المكوّن + إبراز اسم المستخدم المتصل
| - NEW: تمييز اسم المستخدم المتصل داخل المخطط (محور X) + تمييز العمود بحدّ خارجي
|---------------------------------------------------------------------------
*/

'use strict';

let userComparisonChart = null;
let currentUserDataType = 'collections'; // 'collections' | 'followups'
let currentUserPeriod = 'month';

document.addEventListener('DOMContentLoaded', function () {
  setTimeout(() => {
    if (typeof Chart !== 'undefined') {
      Chart.defaults.font.family = 'Cairo, sans-serif';
      initializeUserComparisonChart();
      initializeUserComparisonFilters();
    } else {
      showNoUserComparisonMessage('تعذّر تحميل مكتبة الرسوم');
    }
  }, 500);
});

/* ============================== Init ============================== */
function initializeUserComparisonChart() {
  if (!window.userDashboardData || !window.userDashboardData.comparisonData) {
    showNoUserComparisonMessage('لا توجد بيانات للمقارنة');
    return;
  }

  const comparisonData = window.userDashboardData.comparisonData;
  const base = comparisonData.top_collections || comparisonData.top_performers || [];
  if (!Array.isArray(base) || base.length === 0) {
    showNoUserComparisonMessage('لا توجد بيانات للمقارنة');
    return;
  }

  const container = document.getElementById('collectionsComparisonContainer');
  if (!container) return;

  container.classList.add('d-flex', 'flex-column');
  container.style.minHeight = '480px';
  container.innerHTML = '';

  const currentUserId = window.userDashboardData?.user?.id;

  // --------- تصفية القيم حسب نوع البيانات + منطق الصفر ----------
  const isCollections = currentUserDataType === 'collections';
  const getValue = (u) => isCollections
    ? (parseFloat(u.collection_amount) || 0)
    : (parseInt(u.followups_count) || 0);

  const positives = base.filter(u => getValue(u) > 0);

  if (positives.length === 0) {
    showNoUserComparisonMessage('لا توجد بيانات للمقارنة');
    return;
  }

  let sortedPositives = [...positives].sort((a, b) => getValue(b) - getValue(a));
  sortedPositives = sortedPositives.slice(0, 20);

  // لو المستخدم الحالي قيمته صفر ولم يكن ضمن الموجبين -> أضفه في النهاية
  const currentUserInPositives = sortedPositives.some(u => u.user_id === currentUserId);
  if (!currentUserInPositives) {
    const meFromBase = base.find(u => u.user_id === currentUserId);
    const myVal = meFromBase ? getValue(meFromBase) : 0;
    if (meFromBase && myVal === 0) {
      sortedPositives.push(meFromBase);
      if (sortedPositives.length > 20) {
        const lastIndex = sortedPositives.length - 1;
        const dropIndex = lastIndex - 1;
        if (dropIndex >= 0) sortedPositives.splice(dropIndex, 1);
      }
    }
  }

  // Canvas (Chart)
  const wrap = document.createElement('div');
  wrap.className = 'chart-wrap position-relative flex-grow-1';
  wrap.style.height = '420px';

  const canvas = document.createElement('canvas');
  canvas.id = 'userComparisonChart';
  wrap.appendChild(canvas);
  container.appendChild(wrap);

  // الشريط السفلي (الترتيب + إبراز اسم المستخدم المتصل)
  const rankContainer = document.createElement('div');
  rankContainer.className = 'text-center mt-auto pt-2';
  rankContainer.id = 'user-comparison-rank';
  rankContainer.textContent = '';
  container.appendChild(rankContainer);

  // الرتبة بأمان
  const rawRank = comparisonData?.current_user?.rank ?? comparisonData?.current_user?.collection_rank;
  const rankValue = Number.isFinite(+rawRank) ? +rawRank : null;

  // اسم وصورة المستخدم المتصل
  const currentUserName = window.userDashboardData?.user?.name || '';
  const currentUserImage = (window.userDashboardData?.user?.image_url)
    ? normalizeImageUrl(window.userDashboardData.user.image_url)
    : (findUserImage(sortedPositives, currentUserId) || '/static/brand/logos/logo.svg');

  displayUserRankComparison('user-comparison-rank', rankValue, comparisonData?.all_users_count, {
    userName: currentUserName,
    userImage: currentUserImage
  });

  createUserComparisonChart(canvas, sortedPositives);
}

/* ============================ Create ============================== */
function createUserComparisonChart(canvas, users) {
  const ctx = canvas.getContext('2d');
  const currentUserId = window.userDashboardData?.user?.id;

  const isCollections = currentUserDataType === 'collections';
  const values = isCollections
    ? users.map(u => +u.collection_amount || 0)
    : users.map(u => +u.followups_count || 0);

  const chartColor = isCollections ? '#4cc1bb' : '#1D4F44';
  const tooltipLabel = isCollections ? 'مبلغ التحصيلات' : 'عدد المتابعات';

  // تدرج المتابعات
  const gradient = ctx.createLinearGradient(0, 0, 0, 400);
  gradient.addColorStop(0, chartColor);
  gradient.addColorStop(1, chartColor + '99');

  // تمييز عمود المستخدم الحالي:
  const datasetBackground = isCollections
    ? users.map(u => (u.user_id === currentUserId ? '#4cc1bb' : '#3ba99c'))
    : users.map(u => (u.user_id === currentUserId ? '#1D4F44' : gradient));

  // حدود مميِّزة لعمود المستخدم الحالي فقط
  const datasetBorderColor = users.map(u =>
    u.user_id === currentUserId ? (isCollections ? '#2b8f89' : '#1D4F44') : 'transparent'
  );
  const datasetBorderWidth = users.map(u => (u.user_id === currentUserId ? 2 : 0));

  const maxVal = Math.max(...values, 0);
  const suggestedMax = maxVal > 0 ? maxVal * 1.15 : 10;

  // صور فوق الأعمدة
  const AVATAR_R = 16;
  const OFFSET = 8;
  const userImagesPlugin = {
    id: 'userImagesPlugin',
    beforeInit(chart) { chart._userImgs = {}; },
    beforeDatasetsDraw(chart) {
      users.forEach(u => {
        const url = normalizeImageUrl(u.image_url);
        if (!chart._userImgs[url]) {
          const img = new Image();
          img.crossOrigin = 'anonymous';
          img.src = url;
          img.onload = () => chart.update('none');
          chart._userImgs[url] = img;
        }
      });
    },
    afterDatasetsDraw(chart) {
      const { ctx } = chart;
      const meta = chart.getDatasetMeta(0);
      if (!meta || !meta.data) return;

      meta.data.forEach((bar, i) => {
        const u = users[i];
        if (!u) return;

        const cx = bar.x;
        const topY = Math.min(bar.y, bar.base);
        const cy = topY - AVATAR_R - OFFSET;

        // إطار أبيض وحدّ بلون المخطط
        ctx.save();
        ctx.beginPath();
        ctx.arc(cx, cy, AVATAR_R + 2, 0, Math.PI * 2);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        ctx.lineWidth = 2.5;
        ctx.strokeStyle = chartColor;
        ctx.stroke();
        ctx.closePath();

        // الصورة
        const img = chart._userImgs[normalizeImageUrl(u.image_url)];
        if (img && img.complete && img.naturalHeight) {
          ctx.save();
          ctx.beginPath();
          ctx.arc(cx, cy, AVATAR_R, 0, Math.PI * 2);
          ctx.clip();
          ctx.drawImage(img, cx - AVATAR_R, cy - AVATAR_R, AVATAR_R * 2, AVATAR_R * 2);
          ctx.restore();
        }
        ctx.restore();

        // التاج للمركز الأول
        if (i === 0) {
          ctx.save();
          ctx.font = '22px Arial';
          ctx.textAlign = 'center';
          ctx.textBaseline = 'middle';
          ctx.fillText('👑', cx, cy - AVATAR_R - 10);
          ctx.restore();
        }
      });
    }
  };

  if (userComparisonChart) {
    try { userComparisonChart.destroy(); } catch (e) { }
    userComparisonChart = null;
  }

  const labels = users.map(u => u.user_name || '');

  userComparisonChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: tooltipLabel,
        data: values,
        backgroundColor: datasetBackground,
        borderColor: datasetBorderColor,
        borderWidth: datasetBorderWidth,
        borderRadius: 12,
        barThickness: 32,
        maxBarThickness: 36
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { top: 90, bottom: 16, left: 10, right: 10 } },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(21,29,51,0.95)',
          titleColor: '#fff',
          bodyColor: '#fff',
          borderColor: chartColor,
          borderWidth: 2,
          padding: 12,
          displayColors: false,
          callbacks: {
            title: (ctx) => `المركز ${ctx[0].dataIndex + 1}: ${ctx[0].label}`,
            label: (ctx) => isCollections ? `${formatNumber(ctx.parsed.y)} ريال` : `${ctx.parsed.y} متابعة`
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: {
            color: (ctx) => {
              const u = users[ctx.index];
              const me = window.userDashboardData?.user?.id;
              return (u && u.user_id === me)
                ? (currentUserDataType === 'collections' ? '#4cc1bb' : '#1D4F44')
                : '#666';
            },
            font: (ctx) => {
              const u = users[ctx.index];
              const me = window.userDashboardData?.user?.id;
              return {
                size: 9,               // ↓ كان 10 — صغّرنا قليلًا
                weight: (u && u.user_id === me) ? '700' : '400'
              };
            },
            autoSkip: false,
            maxRotation: 35,          // ↓ كان 45 — مساحة أفضل للأحرف الإضافية
            minRotation: 35,
            padding: 8,
            callback: function (v) {
              const label = this.getLabelForValue(v) || '';
              // ↑ زِدنا الحد: اظهر حتى 18 حرفًا، واقطع عند 16 + '..'
              return label.length > 18 ? label.slice(0, 16) + '..' : label;
            }
          }
        },
        y: {
          beginAtZero: true,
          suggestedMax,
          grid: { color: '#f0f0f0', drawBorder: false },
          ticks: {
            color: '#666',
            font: { size: 11 },
            padding: 8,
            callback: v => currentUserDataType === 'collections' ? `${formatNumber(v)}` : Math.floor(v)
          }
        }
      }
      ,
      animation: { duration: 700, easing: 'easeInOutQuart' }
    },
    plugins: [userImagesPlugin]
  });
}

/* ============================ Filters ============================= */
function initializeUserComparisonFilters() {
  const dataTypeFilter = document.getElementById('userDataTypeFilter');
  if (dataTypeFilter) {
    dataTypeFilter.value = currentUserDataType;
    dataTypeFilter.addEventListener('change', function () {
      currentUserDataType = this.value;
      fetchUserComparisonData();
    });
  }

  const periodFilter = document.getElementById('userComparisonPeriodFilter');
  if (periodFilter) {
    periodFilter.addEventListener('change', function () {
      currentUserPeriod = this.value;
      fetchUserComparisonData();
    });
  }
}

/* ============================= Fetch ============================== */
function fetchUserComparisonData() {
  const loader = document.getElementById('userComparisonLoader');
  if (loader) loader.classList.remove('d-none');

  fetch(`${window.userRoutes.myPerformanceComparison}?period=${currentUserPeriod}&dataType=${currentUserDataType}`, {
    method: 'GET',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    }
  })
    .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
    .then(({ success, data }) => {
      if (success && data) {
        const normalized = { ...data };
        if (!normalized.top_collections && normalized.top_performers) normalized.top_collections = normalized.top_performers;
        if (normalized.current_user && normalized.current_user.rank != null && normalized.current_user.collection_rank == null) {
          normalized.current_user.collection_rank = normalized.current_user.rank;
        }
        window.userDashboardData = window.userDashboardData || {};
        window.userDashboardData.comparisonData = normalized;
        setTimeout(initializeUserComparisonChart, 150);
      } else {
        showNoUserComparisonMessage('لا توجد بيانات للمقارنة');
      }
    })
    .catch(() => showNoUserComparisonMessage('حدث خطأ في تحميل البيانات'))
    .finally(() => { if (loader) setTimeout(() => loader.classList.add('d-none'), 400); });
}

/* ============================ Helpers ============================= */
function displayUserRankComparison(elementId, rank, totalCount, opts = {}) {
  const el = document.getElementById(elementId);
  if (!el) return;

  // لا نعرض شيئا إن لم تكن الرتبة رقمية — منع أي وميض
  if (!Number.isFinite(+rank)) {
    el.innerHTML = '';
    return;
  }

  const safeRank = +rank;
  const icon = safeRank === 1 ? '🏆' : safeRank === 2 ? '🥈' : safeRank === 3 ? '🥉' : '';
  const userName = (opts.userName || '').trim();
  const userImage = normalizeImageUrl(opts.userImage || '/static/brand/logos/logo.svg');
  const total = Number.isFinite(+totalCount) ? +totalCount : '-';

  el.innerHTML = `
    <div class="inline-flex items-center justify-center" style="display:inline-flex;align-items:center;gap:8px;padding:8px 0.75rem;border:1px dashed #4cc1bb;border-radius:10px;background:#f7fffe;">
      <img src="${userImage}" alt="me" style="width:26px;height:26px;border-radius:50%;object-fit:cover;border:2px solid #4cc1bb;">
      <span style="font-size:13px;color:#333;">
        ${icon}
        <strong style="color:#4cc1bb;">${userName ? userName : 'المستخدم الحالي'}</strong>
        — ترتيبك:
        <strong style="color:#4cc1bb;">${safeRank}</strong>
        من
        <strong>${total}</strong>
      </span>
    </div>
  `;
}

function findUserImage(list, userId) {
  const me = Array.isArray(list) ? list.find(u => u.user_id === userId) : null;
  return me && me.image_url ? normalizeImageUrl(me.image_url) : null;
}

function showNoUserComparisonMessage(message) {
  const container = document.getElementById('collectionsComparisonContainer');
  if (!container) return;
  if (userComparisonChart) { try { userComparisonChart.destroy(); } catch (e) { } userComparisonChart = null; }
  container.classList.add('d-flex', 'flex-column');
  container.style.minHeight = '320px';
  container.innerHTML = `
    <div class="text-center py-5 w-100" style="min-height:320px">
      <i class="ti ti-chart-bar" style="font-size:3rem;color:#ddd;"></i>
      <p class="mt-3 text-muted">${message}</p>
    </div>`;
}

function formatNumber(n) {
  if (typeof n !== 'number') n = parseFloat(n) || 0;
  return new Intl.NumberFormat('en-US').format(n);
}

function normalizeImageUrl(url) {
  let imageUrl = url || '/static/brand/logos/logo.svg';
  if (imageUrl && !imageUrl.startsWith('http') && !imageUrl.startsWith('/')) imageUrl = '/' + imageUrl;
  if (imageUrl && imageUrl.startsWith('/storage/')) imageUrl = window.location.origin + imageUrl;
  return imageUrl;
}
