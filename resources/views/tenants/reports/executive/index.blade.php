@extends('tenants.layouts.layoutMaster')

@section('title', 'التقارير التنفيذية')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('content')

    <style>
        .executive-reports-page {
            direction: rtl;
        }

        .executive-reports-page .page-title {
            font-weight: 800;
            color: #183153;
        }

        .report-toolbar {
            background: #fff;
            border: 1px solid #e6edf5;
            border-radius: 6px;
            padding: 16px;
            box-shadow: 0 8px 8px rgba(18, 38, 63, 0.04);
        }

        .filter-control {
            min-height: 42px;
            border-radius: 6px;
            border-color: #dbe4ef;
            font-size: 14px;
        }

        .kpi-card {
            background: #fff;
            border: 1px solid #e6edf5;
            border-radius: 18px;
            padding: 18px;
            min-height: 150px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(18, 38, 63, 0.04);
            transition: 0.25s ease;
        }

        .kpi-title {
            color: #6b7a90;
            margin-bottom: 10px;
        }

        .kpi-value {
            color: #12263f;
            font-size: 16px;
            font-weight: 400;
            margin-bottom: 8px;
        }

        .kpi-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            position: absolute;
            left: 18px;
            top: 18px;
            box-shadow: 0 8px 18px rgba(18, 38, 63, 0.12);
        }

        .bg-kpi-blue {
            background: linear-gradient(135deg, #0d6efd, #4ca3ff);
        }

        .bg-kpi-green {
            background: linear-gradient(135deg, #13a76b, #3ad29f);
        }

        .bg-kpi-red {
            background: linear-gradient(135deg, #e64545, #ff7b7b);
        }

        .bg-kpi-purple {
            background: linear-gradient(135deg, #7e42d9, #a66bff);
        }

        .bg-kpi-orange {
            background: linear-gradient(135deg, #f59f00, #ffc14d);
        }

        .mini-insight-card {
            background: #fff;
            border: 1px solid #e6edf5;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 8px 24px rgba(18, 38, 63, 0.04);
        }

        .mini-insight-label {
            color: #6b7a90;
            font-size: 13px;
            font-weight: 600;
        }

        .mini-insight-value {
            color: #12263f;
            font-size: 15px;
            font-weight: 500;
            margin-top: 6px;
        }

        .table.executive-table {
            margin-bottom: 0;
        }

        .table.executive-table thead th {
            background: #f7f9fc;
            color: #5f6f85;
            font-size: 12px;
            border-bottom: 1px solid #e6edf5;
            white-space: nowrap;
        }

        .table.executive-table tbody td {
            color: #253858;
            font-size: 13px;
            vertical-align: middle;
            white-space: nowrap;
        }

        .status-dot {
            margin-left: 6px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-inline-start: 6px;
        }

        .status-dot.success {
            background: #13a76b;
        }

        .status-dot.danger {
            background: #e64545;
        }

        .status-dot.warning {
            background: #f59f00;
        }

        .export-option {
            border: 1px solid #e6edf5;
            border-radius: 14px;
            padding: 14px;
            cursor: pointer;
            transition: 0.2s;
        }

        .export-icon {
            width: 34px;
            height: 34px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 22px;
        }

        /* Loading overlay */
        #dashboardLoader {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.6);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }

        #dashboardLoader.active {
            display: flex;
        }
    </style>

    <div id="dashboardLoader">
        <div class="spinner-border text-primary" style="width:3rem;height:3rem;" role="status"></div>
    </div>

    <div class="executive-reports-page">

        <div class="report-toolbar mb-3">
            <div class="row g-4 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">الفترة</label>
                    <input type="text" id="dateRange" class="form-control filter-control">
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label">الشركة</label>
                    <select id="companyFilter" class="form-select filter-control">
                            <x-form.controls.enum-options :enum="\App\Enums\Shared\AllScope::class" />
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3 col-md-6">
                    <label class="form-label">المحفظة</label>
                    <select id="portfolioFilter" class="form-select filter-control">
                            <x-form.controls.enum-options :enum="\App\Enums\Shared\AllScope::class" />
                        @foreach ($portfolios as $portfolio)
                            <option value="{{ $portfolio->id }}" data-company="{{ $portfolio->company_id }}">
                                {{ $portfolio->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="d-flex gap-2 justify-content-md-start w-100">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                            تصدير
                        </button>

                        <button type="button" class="btn btn-secondary" id="resetFilters">
                            <i class="ti ti-refresh me-1"></i>
                            إعادة تعيين
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!--- الاحصائيات --->
        <div class="row g-4 mb-4" id="kpiCards">
            @for ($i = 0; $i < 5; $i++)
                <div class="col-sm-6 col-xl">
                    <div class="card">
                        <div class="card-body">
                            <div class="kpi-icon bg-kpi-blue" id="kpiIcon{{ $i }}"><i class="ti ti-loader"></i>
                            </div>
                            <div class="kpi-title" id="kpiTitle{{ $i }}">—</div>
                            <div class="kpi-value" id="kpiValue{{ $i }}">—</div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>

        <!--- الرسومات --->
        <div class="row">
            <div class="col-xl-12">
                <div class="row g-4 mb-3 align-items-stretch">
                    <div class="col-lg-6 d-flex">
                        <div class="card h-100 w-100">
                            <div class="card-header">
                                <h6 class="mb-0 card-title" id="chartTitle1">—</h6>
                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>
                            <div class="card-body">
                                <div id="performanceChart"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 d-flex">
                        <div class="card h-100 w-100">
                            <div class="card-header">
                                <h6 class="mb-0 card-title" id="chartTitle3">—</h6>
                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>
                            <div class="card-body">
                                <div id="amountsChart"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 d-flex">
                        <div class="card h-100 w-100">
                            <div class="card-header">
                                <h6 class="mb-0 card-title" id="chartTitle2">—</h6>
                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>
                            <div class="card-body">
                                <div id="distributionChart"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 d-flex">
                        <div class="card h-100 w-100">
                            <div class="card-header">
                                <h6 class="mb-0 card-title mb-0">الاتجاه الشهري للتحصيل</h6>
                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>
                            <div class="card-body">
                                <div id="monthlyTrendChart"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 align-items-stretch">
                    <div class="col-lg-6 d-flex">
                        <div class="card h-100 w-100">
                            <div class="card-header">
                                <h6 class="mb-0 card-title" id="tableTitle1">—</h6>
                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table executive-table">
                                        <thead id="summaryTableHead"></thead>
                                        <tbody id="summaryTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 d-flex">
                        <div class="card h-100 w-100">
                            <div class="card-header">
                                <h6 class="mb-0 card-title" id="tableTitle2">—</h6>
                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table executive-table">
                                        <thead id="detailsTableHead"></thead>
                                        <tbody id="detailsTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- الأربع مخططات الإضافية --}}
                <div class="row g-4 mt-1 align-items-stretch">
                    <div class="col-lg-6 d-flex">
                        <div class="card h-100 w-100">
                            <div class="card-header">
                                <h6 class="mb-0 card-title mb-0">أداء الموظفين: المتابعات مقابل السدادات</h6>
                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>
                            <div class="card-body">
                                <div id="employeePerfChart"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 d-flex">
                        <div class="card h-100 w-100">
                            <div class="card-header">
                                <h6 class="mb-0 card-title mb-0">تحصيلات المستخدمين (أعلى 20)</h6>
                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>
                            <div class="card-body">
                                <div id="userCollectionsChart"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 d-flex">
                        <div class="card h-100 w-100">
                            <div class="card-header">
                                <h6 class="mb-0 card-title mb-0">العملاء الأعلى مديونية (أعلى 20)</h6>
                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>
                            <div class="card-body">
                                <div id="topDebtorsChart"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 d-flex">
                        <div class="card h-100 w-100">
                            <div class="card-header">
                                <h6 class="mb-0 card-title mb-0">مقارنة المديونية بالمتبقي (أعلى 20)</h6>
                            </div>
                            <div class="border-1 border-light border-dashed mb-2"></div>
                            <div class="card-body">
                                <div id="debtVsRemainingChart"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    {{-- مودال خيارات التصدير --}}
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="exportModalLabel">
                        <i class="ti ti-file-spreadsheet me-1 text-success"></i>
                        خيارات تصدير Excel
                    </h5>
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p class="text-muted small mb-3">اختر الأقسام التي تريد تضمينها في ملف Excel:</p>

                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sec_kpis" value="kpis" checked>
                                <label class="form-check-label" for="sec_kpis">
                                    المؤشرات الرئيسية
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sec_summary" value="summary"
                                    checked>
                                <label class="form-check-label" for="sec_summary">
                                    الملخص (شركات/محافظ)
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sec_details" value="details"
                                    checked>
                                <label class="form-check-label" for="sec_details">
                                    التفاصيل
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sec_employee_perf"
                                    value="employee_perf" checked>
                                <label class="form-check-label" for="sec_employee_perf">
                                    أداء الموظفين
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sec_user_collections"
                                    value="user_collections" checked>
                                <label class="form-check-label" for="sec_user_collections">
                                    تحصيلات المستخدمين
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sec_top_debtors" value="top_debtors"
                                    checked>
                                <label class="form-check-label" for="sec_top_debtors">
                                    أعلى العملاء مديونية
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="sec_debt_vs_remaining"
                                    value="debt_vs_remaining" checked>
                                <label class="form-check-label" for="sec_debt_vs_remaining">
                                    مقارنة المديونية بالمتبقي
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <p class="text-muted small mb-2">عدد السجلات في كل قسم:</p>
                    <div class="d-flex gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportLimit" id="limit20"
                                value="20" checked>
                            <label class="form-check-label fw-semibold" for="limit20">
                                أعلى 20 سجل
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="exportLimit" id="limitAll"
                                value="0">
                            <label class="form-check-label fw-semibold" for="limitAll">
                                الكل (جميع السجلات)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" id="confirmExportBtn">
                        <i class="ti ti-download me-1"></i>
                        تصدير الآن
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dataUrl = '{{ tenant_route_name('reports.executive.data') }}';
            const exportUrl = '{{ tenant_route_name('reports.executive.export-excel') }}';
            const companyFilter = document.getElementById('companyFilter');
            const portfolioFilter = document.getElementById('portfolioFilter');
            const loader = document.getElementById('dashboardLoader');

            let charts = {};
            let currentDateFrom = '';
            let currentDateTo = '';

            // ── Flatpickr ────────────────────────────────────────────────────
            const fp = flatpickr('#dateRange', {
                mode: 'range',
                dateFormat: 'd/m/Y',
                defaultDate: [
                    new Date(new Date().getFullYear(), new Date().getMonth(), 1),
                    new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0)
                ],
                locale: {
                    rangeSeparator: ' - '
                },
                onChange(selectedDates) {
                    if (selectedDates.length === 2) {
                        const fmt = d => d.toLocaleDateString('en-GB').split('/').join('/');
                        currentDateFrom = fmt(selectedDates[0]);
                        currentDateTo = fmt(selectedDates[1]);
                        fetchDashboard();
                    }
                }
            });

            // Init default dates from flatpickr
            const today = new Date();
            const startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            const endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            const padDate = d => String(d).padStart(2, '0');
            currentDateFrom =
                `${padDate(startDate.getDate())}/${padDate(startDate.getMonth()+1)}/${startDate.getFullYear()}`;
            currentDateTo =
                `${padDate(endDate.getDate())}/${padDate(endDate.getMonth()+1)}/${endDate.getFullYear()}`;

            // ── Portfolio filter cascade ──────────────────────────────────────
            function updatePortfolioOptions() {
                const selected = companyFilter.value;
                portfolioFilter.querySelectorAll('option').forEach(opt => {
                    if (opt.value === 'all') {
                        opt.hidden = false;
                        return;
                    }
                    opt.hidden = selected !== 'all' && opt.dataset.company !== selected;
                });
                const cur = portfolioFilter.options[portfolioFilter.selectedIndex];
                if (cur && cur.hidden) portfolioFilter.value = 'all';
            }

            // ── AJAX fetch ────────────────────────────────────────────────────
            function fetchDashboard() {
                loader.classList.add('active');

                const params = new URLSearchParams({
                    company_id: companyFilter.value,
                    portfolio_id: portfolioFilter.value,
                    date_from: currentDateFrom,
                    date_to: currentDateTo,
                });

                fetch(`${dataUrl}?${params}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        renderTitles(data);
                        renderKpis(data);
                        renderCharts(data);
                        renderAdditionalCharts(data);
                        renderMonthlyTrend(data);
                        renderTables(data);
                    })
                    .catch((err) => {
                        console.error('Executive report load error:', err);
                        toastr.error('تعذّر تحميل البيانات، يرجى المحاولة مرة أخرى.');
                    })
                    .finally(() => loader.classList.remove('active'));
            }

            // ── Render helpers ────────────────────────────────────────────────
            function renderKpis(data) {
                data.kpis.forEach((kpi, i) => {
                    document.getElementById(`kpiTitle${i}`).innerText = kpi.title;
                    document.getElementById(`kpiValue${i}`).innerText = kpi.value;
                    const icon = document.getElementById(`kpiIcon${i}`);
                    icon.className = `kpi-icon ${kpi.color}`;
                    icon.innerHTML = `<i class="ti ${kpi.icon}"></i>`;
                });
            }

            function destroyCharts() {
                Object.values(charts).forEach(c => c?.destroy());
                charts = {};
            }

            // تنسيق الأرقام بفواصل آلاف
            function numFmt(v) {
                if (!v && v !== 0) return '0';
                return Number(v).toLocaleString('en-US');
            }

            function renderCharts(data) {
                destroyCharts();

                charts.performance = new ApexCharts(document.querySelector('#performanceChart'), {
                    chart: {
                        height: 320,
                        type: 'line',
                        toolbar: {
                            show: false
                        },
                        fontFamily: 'inherit'
                    },
                    series: data.performanceSeries,
                    stroke: {
                        width: [0, 3],
                        curve: 'smooth'
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '42%',
                            borderRadius: 6
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        enabledOnSeries: [0],
                        formatter: numFmt,
                        style: {
                            fontSize: '10px',
                            colors: ['#fff']
                        }
                    },
                    xaxis: {
                        categories: data.performanceCategories,
                        labels: {
                            style: {
                                fontSize: '11px'
                            }
                        }
                    },
                    yaxis: [{
                            labels: {
                                formatter: numFmt
                            }
                        },
                        {
                            opposite: true,
                            labels: {
                                formatter: v => v + '%'
                            }
                        }
                    ],
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: [{
                            formatter: numFmt
                        }, {
                            formatter: v => v + '%'
                        }]
                    },
                    legend: {
                        position: 'top'
                    }
                });

                charts.amounts = new ApexCharts(document.querySelector('#amountsChart'), {
                    chart: {
                        height: 320,
                        type: 'bar',
                        toolbar: {
                            show: false
                        },
                        fontFamily: 'inherit'
                    },
                    series: [{
                            name: 'المستحق',
                            data: data.dueSeries
                        },
                        {
                            name: 'المحصل',
                            data: data.collectedSeries
                        }
                    ],
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '45%',
                            borderRadius: 6
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: numFmt,
                        style: {
                            fontSize: '10px',
                            colors: ['#fff']
                        },
                        offsetY: -2
                    },
                    xaxis: {
                        categories: data.amountCategories,
                        labels: {
                            style: {
                                fontSize: '11px'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: numFmt
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: numFmt
                        }
                    },
                    legend: {
                        position: 'top'
                    }
                });


                charts.distribution = new ApexCharts(document.querySelector('#distributionChart'), {
                    chart: {
                        height: 285,
                        type: 'donut',
                        fontFamily: 'inherit'
                    },
                    series: data.distributionSeries,
                    labels: data.distributionLabels,
                    legend: {
                        position: 'bottom'
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '67%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: portfolioFilter.value !== 'all' ? 'عميل' : 'محفظة',
                                        formatter: () => ((data.distributionSeries || []).reduce((a, b) =>
                                                a + b, 0) || 0)
                                            .toLocaleString()
                                    }
                                }
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: (val, opts) => numFmt(opts.w.config.series[opts.seriesIndex]),
                        style: {
                            fontSize: '11px'
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: numFmt
                        }
                    }
                });


                charts.performance.render();
                charts.distribution.render();
                charts.amounts.render();
            }

            function renderAdditionalCharts(data) {
                ['employeePerf', 'userCollections', 'topDebtors', 'debtVsRemaining'].forEach(k => {
                    charts[k]?.destroy();
                    delete charts[k];
                });

                const empData = data.employeePerf || {};
                const userColl = data.userCollections || {};
                const debtors = data.topDebtors || {};
                const dvr = data.debtVsRemaining || {};

                const barHeight = cats => Math.max(300, (cats?.length || 0) * 34 + 80);

                const hBarDataLabels = {
                    enabled: true,
                    formatter: numFmt,
                    style: {
                        fontSize: '10px'
                    },
                    offsetX: 4
                };

                // 1. أداء الموظفين
                if ((empData.categories || []).length) {
                    charts.employeePerf = new ApexCharts(document.querySelector('#employeePerfChart'), {
                        chart: {
                            type: 'bar',
                            height: barHeight(empData.categories),
                            toolbar: {
                                show: false
                            },
                            fontFamily: 'inherit'
                        },
                        series: [{
                                name: 'المتابعات',
                                data: empData.followsSeries || []
                            },
                            {
                                name: 'السدادات',
                                data: empData.paymentsSeries || []
                            }
                        ],
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                borderRadius: 4,
                                barHeight: '60%',
                                dataLabels: {
                                    position: 'top'
                                }
                            }
                        },
                        dataLabels: {
                            ...hBarDataLabels
                        },
                        xaxis: {
                            categories: empData.categories,
                            labels: {
                                formatter: v => String(Math.round(v)),
                                style: {
                                    fontSize: '11px'
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    fontSize: '11px',
                                    fontWeight: 600
                                }
                            }
                        },
                        legend: {
                            position: 'top'
                        },
                        colors: ['#0d6efd', '#13a76b'],
                        tooltip: {
                            shared: true,
                            intersect: false,
                            x: {
                                show: true
                            }
                        }
                    });
                    charts.employeePerf.render();
                }

                // 2. تحصيلات المستخدمين
                if ((userColl.categories || []).length) {
                    charts.userCollections = new ApexCharts(document.querySelector('#userCollectionsChart'), {
                        chart: {
                            type: 'bar',
                            height: barHeight(userColl.categories),
                            toolbar: {
                                show: false
                            },
                            fontFamily: 'inherit'
                        },
                        series: [{
                            name: 'المحصّل',
                            data: userColl.series || []
                        }],
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                borderRadius: 4,
                                barHeight: '55%',
                                dataLabels: {
                                    position: 'top'
                                }
                            }
                        },
                        dataLabels: {
                            ...hBarDataLabels
                        },
                        xaxis: {
                            categories: userColl.categories,
                            labels: {
                                formatter: numFmt,
                                style: {
                                    fontSize: '11px'
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    fontSize: '11px',
                                    fontWeight: 600
                                }
                            }
                        },
                        colors: ['#7e42d9'],
                        tooltip: {
                            y: {
                                formatter: v => numFmt(v) + ' ر.س'
                            }
                        }
                    });
                    charts.userCollections.render();
                }

                // 3. العملاء الأعلى مديونية
                if ((debtors.categories || []).length) {
                    charts.topDebtors = new ApexCharts(document.querySelector('#topDebtorsChart'), {
                        chart: {
                            type: 'bar',
                            height: barHeight(debtors.categories),
                            toolbar: {
                                show: false
                            },
                            fontFamily: 'inherit'
                        },
                        series: [{
                            name: 'المتبقي',
                            data: debtors.series || []
                        }],
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                borderRadius: 4,
                                barHeight: '55%',
                                dataLabels: {
                                    position: 'top'
                                }
                            }
                        },
                        dataLabels: {
                            ...hBarDataLabels
                        },
                        xaxis: {
                            categories: debtors.categories,
                            labels: {
                                formatter: numFmt,
                                style: {
                                    fontSize: '11px'
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    fontSize: '11px',
                                    fontWeight: 600
                                }
                            }
                        },
                        colors: ['#e64545'],
                        tooltip: {
                            y: {
                                formatter: v => numFmt(v) + ' ر.س'
                            }
                        }
                    });
                    charts.topDebtors.render();
                }

                // 4. مقارنة المديونية بالمتبقي
                if ((dvr.categories || []).length) {
                    charts.debtVsRemaining = new ApexCharts(document.querySelector('#debtVsRemainingChart'), {
                        chart: {
                            type: 'bar',
                            height: barHeight(dvr.categories),
                            toolbar: {
                                show: false
                            },
                            fontFamily: 'inherit'
                        },
                        series: [{
                                name: 'إجمالي المديونية',
                                data: dvr.debitSeries || []
                            },
                            {
                                name: 'المتبقي',
                                data: dvr.remainingSeries || []
                            }
                        ],
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                borderRadius: 4,
                                barHeight: '60%',
                                dataLabels: {
                                    position: 'top'
                                }
                            }
                        },
                        dataLabels: {
                            ...hBarDataLabels
                        },
                        xaxis: {
                            categories: dvr.categories,
                            labels: {
                                formatter: numFmt,
                                style: {
                                    fontSize: '11px'
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    fontSize: '11px',
                                    fontWeight: 600
                                }
                            }
                        },
                        legend: {
                            position: 'top'
                        },
                        colors: ['#f59f00', '#e64545'],
                        tooltip: {
                            shared: true,
                            intersect: false,
                            y: {
                                formatter: v => numFmt(v) + ' ر.س'
                            }
                        }
                    });
                    charts.debtVsRemaining.render();
                }
            }

            function renderTables(data) {
                document.getElementById('summaryTableHead').innerHTML =
                    `<tr>${data.summaryHead.map(h => `<th>${h}</th>`).join('')}</tr>`;

                document.getElementById('summaryTableBody').innerHTML = data.summaryRows.map(row =>
                    `<tr>${row.map((cell, i) => `<td>${i === row.length - 1 ? statusDot(cell) : ''}${cell}</td>`).join('')}</tr>`
                ).join('');

                document.getElementById('detailsTableHead').innerHTML =
                    `<tr>${data.detailsHead.map(h => `<th>${h}</th>`).join('')}</tr>`;

                document.getElementById('detailsTableBody').innerHTML = data.detailsRows.map(row =>
                    `<tr>${row.map((cell, i) => `<td>${i === row.length - 1 ? statusDot(cell) : ''}${cell}</td>`).join('')}</tr>`
                ).join('');
            }

            function statusDot(value) {
                const num = parseFloat(String(value).replace('%', ''));
                if (isNaN(num)) return '';
                if (num >= 65) return '<span class="status-dot success"></span>';
                if (num >= 55) return '<span class="status-dot warning"></span>';
                return '<span class="status-dot danger"></span>';
            }

            function renderMonthlyTrend(data) {
                charts.monthlyTrend?.destroy();
                delete charts.monthlyTrend;

                const trend = data.monthlyTrend || {};
                if (!(trend.categories || []).length) return;

                charts.monthlyTrend = new ApexCharts(document.querySelector('#monthlyTrendChart'), {
                    chart: {
                        type: 'area',
                        height: 285,
                        toolbar: {
                            show: false
                        },
                        fontFamily: 'inherit',
                        zoom: {
                            enabled: false
                        }
                    },
                    series: [{
                            name: 'المستحق',
                            data: trend.dueSeries || []
                        },
                        {
                            name: 'المحصّل',
                            data: trend.collectedSeries || []
                        }
                    ],
                    stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            opacityFrom: 0.25,
                            opacityTo: 0.02,
                            shadeIntensity: 1
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    xaxis: {
                        categories: trend.categories,
                        labels: {
                            style: {
                                fontSize: '11px'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: numFmt
                        }
                    },
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: {
                            formatter: v => numFmt(v) + ' ر.س'
                        }
                    },
                    legend: {
                        position: 'top'
                    },
                    colors: ['#e64545', '#13a76b'],
                    markers: {
                        size: 3
                    }
                });
                charts.monthlyTrend.render();
            }

            function renderTitles(data) {
                document.getElementById('chartTitle1').innerText = data.chartTitles[0];
                document.getElementById('chartTitle2').innerText = data.chartTitles[1];
                document.getElementById('chartTitle3').innerText = data.chartTitles[2];
                document.getElementById('tableTitle1').innerText = data.tableTitles[0];
                document.getElementById('tableTitle2').innerText = data.tableTitles[1];
            }

            // ── Events ────────────────────────────────────────────────────────
            companyFilter.addEventListener('change', () => {
                updatePortfolioOptions();
                fetchDashboard();
            });

            portfolioFilter.addEventListener('change', () => {
                const opt = portfolioFilter.options[portfolioFilter.selectedIndex];
                if (opt?.dataset.company) {
                    companyFilter.value = opt.dataset.company;
                    updatePortfolioOptions();
                }
                fetchDashboard();
            });

            document.getElementById('resetFilters').addEventListener('click', () => {
                companyFilter.value = 'all';
                portfolioFilter.value = 'all';
                updatePortfolioOptions();
                fp.setDate([startDate, endDate]);
                currentDateFrom =
                    `${padDate(startDate.getDate())}/${padDate(startDate.getMonth()+1)}/${startDate.getFullYear()}`;
                currentDateTo =
                    `${padDate(endDate.getDate())}/${padDate(endDate.getMonth()+1)}/${endDate.getFullYear()}`;
                fetchDashboard();
            });

            document.getElementById('confirmExportBtn').addEventListener('click', () => {
                const sectionKeys = ['kpis', 'summary', 'details', 'employee_perf', 'user_collections',
                    'top_debtors', 'debt_vs_remaining'
                ];
                const sections = sectionKeys.filter(k => document.getElementById(`sec_${k}`)?.checked);
                const limit = document.querySelector('input[name="exportLimit"]:checked')?.value ?? '20';

                if (!sections.length) {
                    toastr.warning('يرجى اختيار قسم واحد على الأقل للتصدير.');
                    return;
                }

                const params = new URLSearchParams({
                    company_id: companyFilter.value,
                    portfolio_id: portfolioFilter.value,
                    date_from: currentDateFrom,
                    date_to: currentDateTo,
                    sections: sections.join(','),
                    export_limit: limit,
                });
                window.location.href = `${exportUrl}?${params}`;
                bootstrap.Modal.getInstance(document.getElementById('exportModal'))?.hide();
            });

            // ── Init ──────────────────────────────────────────────────────────
            updatePortfolioOptions();
            fetchDashboard();
        });
    </script>

@endsection
