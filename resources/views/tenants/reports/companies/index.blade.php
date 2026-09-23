@extends('tenants.layouts.layoutMaster')

@section('title', 'تقارير الشركات والمحافظ')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
    <style>
        .report-toolbar {
            background: #fff;
            border: 1px solid var(--report-border);
            border-radius: var(--report-radius);
            box-shadow: 0 .125rem .625rem rgba(67, 89, 113, .05);
            padding: 2px 15px 25px 15px;
        }

        .kpi-icon {
            align-items: center;
            border-radius: .5rem;
            display: inline-flex;
            flex: 0 0 40px;
            height: 40px;
            justify-content: center;
            width: 40px;
        }

        .kpi-label {
            color: var(--bs-secondary-color);
            font-size: var(--report-font-sm);
            font-weight: 700;
            line-height: 1.45;
        }

        .kpi-value {
            font-size: 1rem;
            font-weight: 700;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .kpi-meta {
            color: var(--bs-secondary-color);
            font-size: var(--report-font-sm);
            line-height: 1.6;
        }

        .kpi-inline-muted {
            font-size: var(--report-font-sm);
            font-weight: 500;
        }

        .chart-box {
            height: 255px;
            position: relative;
        }

        .table-filters {
            border-top: 1px solid var(--report-border);
        }

        .filter-actions {
            display: flex;
            justify-content: flex-end;
        }

        .report-table-card .card-body {
            padding-top: 1rem;
        }
    </style>
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/chart/chart.js'])
    <script>
        window.reportData = {
            routes: {
                index: @json(tenant_route_name('reports.companies.index')),
                companyStats: @json(tenant_route_name('reports.companies.stats', ['company' => '__COMPANY_ID__'])),
                companyPortfolios: @json(tenant_route_name('reports.companies.portfolios', ['company' => '__COMPANY_ID__'])),
                portfolioStats: @json(tenant_route_name('reports.companies.portfolio', ['portfolio' => '__PORTFOLIO_ID__'])),
                showExportModal: @json(tenant_route_name('reports.companies.show-export-modal')),
                exportExcel: @json(tenant_route_name('reports.companies.export.excel')),
                checkProgress: @json(tenant_route_name('reports.companies.check-progress')),
                download: @json(tenant_route_name('reports.companies.download', ['file' => 'FILE_PLACEHOLDER'])),
            },
            csrfToken: @json(csrf_token()),
            currentPeriod: @json($period),
            global: @json($globalStats),
            charts: {
                topDebt: @json($topCompaniesByDebt),
                distribution: @json($customerDistribution),
                collectionTrend: @json($collectionRateTrend),
            },
        };
    </script>
    @vite(['resources/assets/js/tenants/reports/companies/index.js'])
@endsection

@section('content')
    <div class="mb-5">
        <div class="card mb-3 p-5">
            <div class="row g-2">
                <div class="col-md-2 report-filter-field">
                    <label for="period-selector" class="form-label form-label">الفترة الزمنية</label>
                    <select id="period-selector" class="form-select select2">
                        @foreach ($periods as $key => $label)
                            <option value="{{ $key }}" @selected($period === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-5 report-filter-field">
                    <label for="company-selector" class="form-label form-label-sm">الشركة</label>
                    <select id="company-selector" class="form-select form-select-sm select2"
                        data-placeholder="جميع الشركات">
                        <option value="">جميع الشركات</option>
                        @foreach ($companiesList as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="portfolio-wrap" class="col-md-5 report-filter-field is-disabled">
                    <label for="portfolio-selector" class="form-label form-label-sm">المحفظة</label>
                    <select id="portfolio-selector" class="form-select form-select-sm" data-placeholder="جميع محافظ الشركة">
                        <option value="">جميع المحافظ</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            {{-- <div id="stat-col-employees" class="col-sm-6 col-xl-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <span class="text-heading">إجمالي الموظفين</span>
                                <div class="my-1">
                                    <h4 class="mb-0 stat-value" id="stat-total-employees">
                                        3333
                                    </h4>
                                </div>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-users ti-26px"></i>
                                </span>
                            </div>

                        </div>
                    </div>
                </div>
            </div> --}}

            <div id="kpi1-col" class="col-sm-6 col-xl-3">
                <div class="card kpi-card kpi-border-primary h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="">
                                <div>
                                    <div class="kpi-label">عدد الشركات</div>
                                    <div id="kpi1-total" class="kpi-value d-inline">
                                        {{ number_format($globalStats['companies']['total']) }}
                                    </div>
                                    <span> شركة</span>

                                </div>
                            </div>

                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-building ti-26px"></i>
                                </span>
                            </div>
                        </div>
                        <div class="kpi-meta d-flex flex-wrap gap-2">
                            <span class="text-success">نشطة <strong
                                    id="kpi1-active">{{ number_format($globalStats['companies']['active']) }}</strong></span>
                            <span class="text-danger">غير نشطة <strong
                                    id="kpi1-inactive">{{ number_format($globalStats['companies']['inactive']) }}</strong></span>
                            <span>مؤرشفة <strong
                                    id="kpi1-archived">{{ number_format($globalStats['companies']['archived']) }}</strong></span>
                        </div>
                        <div class="kpi-meta mt-2">جديدة هذا الشهر: <strong
                                id="kpi1-new">{{ number_format($globalStats['companies']['new_this_month']) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div id="kpi2-col" class="col-sm-6 col-xl-3">
                <div class="card kpi-card kpi-border-info h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div id="kpi2-title" class="kpi-label">عدد المحافظ</div>
                                <div id="kpi2-value" class="kpi-value d-inline">
                                    {{ number_format($globalStats['portfolios']['total']) }}
                                </div>
                            </div>

                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-briefcase ti-26px"></i>
                                </span>
                            </div>
                        </div>
                        <div id="kpi2-meta" class="kpi-meta d-flex flex-wrap gap-2"></div>
                        <div id="kpi2-sub" class="kpi-meta mt-2"></div>
                    </div>
                </div>
            </div>

            <div id="kpi3-col" class="col-sm-6 col-xl-3">
                <div class="card kpi-card kpi-border-warning h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div id="kpi3-title" class="kpi-label">قاعدة العملاء</div>
                                <div id="kpi3-value" class="kpi-value d-inline">
                                    {{ number_format($globalStats['customers']['total']) }}
                                </div>
                                <span> عميل</span>
                            </div>

                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-users ti-26px"></i>
                                </span>
                            </div>
                        </div>
                        <div id="kpi3-avg-portfolio" class="kpi-meta"></div>
                        <div id="kpi3-avg-debt" class="kpi-meta mt-1"></div>
                    </div>
                </div>
            </div>

            <div id="kpi4-col" class="col-sm-6 col-xl-3">
                <div class="card kpi-card kpi-border-success h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div id="kpi4-title" class="kpi-label">الأداء مقابل الهدف</div>
                                <div id="kpi4-value" class="kpi-value"></div>
                            </div>

                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-target ti-26px"></i>
                                </span>
                            </div>
                        </div>
                        <div id="kpi4-meta" class="kpi-meta"></div>
                        <div class="mini-bar mt-2">
                            <div id="kpi4-bar" class="mini-bar-fill bg-success" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="card kpi-card kpi-border-danger h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="kpi-label mb-2">المديونية</div>
                                <div id="kpi5-value" class="kpi-value text-danger"></div>
                            </div>

                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-file-invoice ti-26px"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card kpi-card kpi-border-success h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="d-flex mb-2">
                                    <div class="kpi-label"> التحصيل</div>
                                    (<div id="kpi6-rate" class="kpi-value text-success mx-0"></div>)
                                </div>

                                <div id="kpi6-credit" class="kpi-value text-success"></div>
                            </div>

                            <div class="">
                                <div class="avatar">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="ti ti-cash ti-26px"></i>
                                    </span>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-4">
                <div class="card kpi-card kpi-border-purple h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="kpi-label mb-2">المتبقي </div>
                                <div id="kpi7-safi" class="kpi-value"></div>
                            </div>

                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-scale ti-26px"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header py-2">
                        <h6 class="mb-0">أعلى 5 شركات مديونية</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-box">
                            <canvas id="debtBarChart" aria-label="أعلى خمس شركات مديونية" role="img"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header py-2">
                        <h6 class="mb-0">توزيع العملاء</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-box">
                            <canvas id="customerDoughnutChart" aria-label="توزيع العملاء بين الشركات"
                                role="img"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header py-2">
                        <h6 class="mb-0">
                            تطور التحصيل
                            @if ($period === 'this_month' || $period === 'last_month')
                                <small class="text-muted fw-normal" style="font-size:.75rem">— أسبوعي</small>
                            @endif
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-box">
                            <canvas id="collectionLineChart" aria-label="تطور نسبة التحصيل" role="img"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card report-table-card">
            <div class="card-header d-flex justify-content-between align-items-center gap-2 pb-0">
                <h6 class="mb-0"><i class="ti ti-table me-2"></i>جدول الشركات</h6>

                <button type="button" id="toggle-table-filters" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-filter me-1"></i>
                    <span> الفلاتر</span>
                </button>
            </div>


            <div class="table-filters d-none" id="filtersSection">
                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="filter-status" class="form-label form-label-sm">الحالة</label>
                            <select id="filter-status" class="form-select form-select-sm">
                                <option value="">الكل</option>
                                <option value="active">نشط</option>
                                <option value="inactive">غير نشط</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filter-date-from" class="form-label form-label-sm">من تاريخ الإنشاء</label>
                            <input type="date" id="filter-date-from" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label for="filter-date-to" class="form-label form-label-sm">إلى تاريخ الإنشاء</label>
                            <input type="date" id="filter-date-to" class="form-control form-control-sm">
                        </div>

                        <div class="col-md-12">
                            <div class="filter-actions">
                                <button type="button" id="reset-filters" class="btn btn-sm btn-outline-secondary">
                                    <i class="ti ti-refresh me-1"></i>إعادة تعيين
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'companies-table'], false) !!}
            </div>
        </div>

        <div class="modal fade" id="exportFieldsModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ti ti-file-download me-2"></i>تخصيص حقول التصدير</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="fieldsContainer"></div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>إلغاء</button>
                        <button id="confirmExport" class="btn btn-primary"><i class="ti ti-download me-1"></i>تصدير</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center p-4">
                        <div class="spinner-border text-primary mb-3" style="width:3rem;height:3rem;" role="status">
                            <span class="visually-hidden">جاري المعالجة...</span>
                        </div>
                        <div id="loadingMessage" class="fw-semibold">جاري المعالجة...</div>
                        <div id="loadingProgress" class="progress mt-3" style="display:none;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
