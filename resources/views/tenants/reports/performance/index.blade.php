@extends('tenants.layouts.layoutMaster')
@section('title', 'تقارير الأداء')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            performanceReportsIndex: "{{ tenant_route_name('reports.performance.index') }}",
            performanceReportsData: "{{ tenant_route_name('reports.performance.data') }}",
            performanceReportsExportPDF: "{{ tenant_route_name('reports.performance.export.pdf') }}",
            performanceReportsExportExcel: "{{ tenant_route_name('reports.performance.export.excel') }}",
            performanceReportsShowExportModal: "{{ tenant_route_name('reports.performance.show-export-modal') }}",
            performanceReportsCheckProgress: "{{ tenant_route_name('reports.performance.check-progress') }}",
            performanceReportsDownload: "{{ tenant_route_name('reports.performance.download', ['file' => 'FILE_PLACEHOLDER']) }}",
            performanceReportsCheckCount: "{{ tenant_route_name('reports.performance.check.count') }}"
        };
        window.assets = {
            arabicDataTable: "{{ asset('assets/json/ar.json') }}"
        };

        window.csrfToken = "{{ csrf_token() }}";

        window.chartData = {
            paymentComparison: @json($payment_comparison),
            currentMonthPerformance: @json($current_month_performance),
            contactStats: @json($contact_stats)
        };
    </script>
    @vite(['resources/assets/js/tenants/reports/performance/index.js'])
@endsection

@section('content')
    {{-- الإحصائيات الرئيسية --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي المديونية</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($total_portfolio / 1000000, 1) }}M</h4>
                            </div>
                            <small class="mb-0">مديونية العملاء</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-briefcase ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي مبلغ التحصيل</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($total_collections / 1000, 0) }}K</h4>
                            </div>
                            <small class="mb-0">{{ number_format($total_collections, 0) }} ريال</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-cash ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">نسبة التحصيل</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($collection_percentage, 2) }}%</h4>
                            </div>
                            <small class="mb-0">من إجمالي المديونية</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-percentage ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">عدد الشركات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $companies_count }}</h4>
                            </div>
                            <small class="mb-0">شركة لديها عملاء</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-building ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- الصف الثاني من الإحصائيات --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المبلغ المتبقي بعد التحصيل</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($total_remaining / 1000000, 1) }}M</h4>
                            </div>
                            <small class="mb-0">{{ number_format($total_remaining, 0) }} ريال</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-arrow-down-circle ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">عدد العملاء المسددين</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($paid_customers_count) }}</h4>
                            </div>
                            <small class="mb-0">عميل مسدد</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-users ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المستهدف الشهري</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($monthly_target / 1000000, 1) }}M</h4>
                            </div>
                            <small class="mb-0">{{ number_format($monthly_target, 0) }} ريال</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-target ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- الرسوم البيانية --}}
    <div class="row g-4 mb-4">
        {{-- إجمالي السداد مقارنة بالمستهدف --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">إجمالي السداد مقارنة بالمستهدف</h5>
                    <i class="ti ti-target text-primary"></i>
                </div>
                <div class="card-body">
                    <div id="paymentComparisonChart"></div>

                    {{-- ✅ حجم مرتب ومتناسق --}}
                    <div class="mt-3 pt-2">
                        <div class="row text-center g-3">
                            {{-- المستهدف --}}
                            <div class="col-4">
                                <h6 class="mb-1 fw-semibold text-primary">
                                    {{ number_format($payment_comparison['monthly_target'] / 1000000, 1) }}M
                                </h6>
                                <small class="text-muted" style="font-size: 0.7rem;">المستهدف الشهري</small>
                            </div>

                            {{-- السداد --}}
                            <div class="col-4">
                                <h6 class="mb-1 fw-semibold text-success">
                                    {{ number_format($payment_comparison['total_payment'] / 1000, 0) }}K
                                </h6>
                                <small class="text-muted" style="font-size: 0.7rem;">إجمالي السداد</small>
                            </div>

                            {{-- المتبقي --}}
                            <div class="col-4">
                                <h6 class="mb-1 fw-semibold text-danger">
                                    {{ number_format($payment_comparison['total_remaining'] / 1000, 0) }}K
                                </h6>
                                <small class="text-muted" style="font-size: 0.7rem;">المتبقي</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- مؤشرات أداء السداد --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">مقارنة الأداء الشهري</h5>
                    <i class="ti ti-chart-line text-success"></i>
                </div>
                <div class="card-body">
                    @php
                        $change = $current_month_performance['previous_month_change'];
                        $currentAmount = $current_month_performance['current_month_payment'];
                        $previousAmount = $currentAmount / (1 + $change / 100);
                        $difference = $currentAmount - $previousAmount;
                    @endphp

                    {{-- ✅ مؤشر النمو في الأعلى --}}
                    <div class="text-center mb-4">
                        @if ($change >= 0)
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <i class="ti ti-trending-up text-success me-2" style="font-size: 1.3rem;"></i>
                                <span class="text-success fw-bold" style="font-size: 1.1rem;">
                                    نمو {{ number_format($change, 1) }}%
                                </span>
                            </div>
                        @else
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <i class="ti ti-trending-down text-danger me-2" style="font-size: 1.3rem;"></i>
                                <span class="text-danger fw-bold" style="font-size: 1.1rem;">
                                    انخفاض {{ number_format(abs($change), 1) }}%
                                </span>
                            </div>
                        @endif
                        <small class="text-muted">مقارنة بالشهر الماضي</small>
                    </div>

                    {{-- ✅ الكاردات المقارنة --}}
                    <div class="row g-2 mb-3 mt-6">
                        {{-- الشهر الحالي --}}
                        <div class="col-6">
                            <div class="text-center p-3 rounded" style="background-color: #f8f9fa;">
                                <h5 class="mb-1 fw-bold text-primary">
                                    {{ number_format($currentAmount / 1000, 0) }}K
                                </h5>
                                <small class="text-muted">الشهر الحالي</small>
                            </div>
                        </div>

                        {{-- الشهر الماضي --}}
                        <div class="col-6">
                            <div class="text-center p-3 rounded" style="background-color: #f8f9fa;">
                                <h5 class="mb-1 fw-bold text-secondary">
                                    {{ number_format($previousAmount / 1000, 0) }}K
                                </h5>
                                <small class="text-muted">الشهر الماضي</small>
                            </div>
                        </div>
                    </div>

                    {{-- ✅ تفاصيل الفرق --}}
                    <div class="text-center p-2 rounded mt-4" style="background-color: #f8f9fa;">
                        <div class="d-flex align-items-center justify-content-center">
                            <small class="text-muted me-2">الفرق:</small>
                            <span class="{{ $change >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                                {{ $change >= 0 ? '+' : '' }}{{ number_format($difference / 1000, 0) }}K
                            </span>
                            <i class="ti ti-arrow-{{ $change >= 0 ? 'up' : 'down' }} {{ $change >= 0 ? 'text-success' : 'text-danger' }} ms-1"
                                style="font-size: 0.9rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- إفادة التواصل مع العملاء --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">إفادة التواصل مع العملاء</h5>
                    <i class="ti ti-phone text-info"></i>
                </div>
                <div class="card-body">
                    <div id="contactStatsChart"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول الشركات --}}
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">تقرير الأداء العام</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="collapse"
                    data-bs-target="#filtersSection" aria-expanded="false">
                    <i class="ti ti-filter me-1"></i>
                    فلاتر البحث
                    <i class="ti ti-chevron-down ms-1"></i>
                </button>
            </div>
        </div>

        <x-data-table.filters id="filtersSection" class="collapse" card-class="border-bottom shadow-none mb-0" table="#company-performance-table" reset-id="reset-filters">
            <div class="col-md-3">
                <label for="filter-year" class="form-label">السنة</label>
                <select id="filter-year" class="form-control select2" data-datatable-filter="true" data-filter-key="year" data-placeholder="اختر السنة">
                    <option value=""></option>
                    @for ($year = date('Y'); $year >= date('Y') - 5; $year--)
                        <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>
                            {{ $year }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-md-3">
                <label for="filter-month" class="form-label">الشهر</label>
                <select id="filter-month" class="form-control select2" data-datatable-filter="true" data-filter-key="month" data-placeholder="اختر الشهر">
                    <option value=""></option>
                    @php
                        $months = [
                            1 => 'يناير',
                            2 => 'فبراير',
                            3 => 'مارس',
                            4 => 'أبريل',
                            5 => 'مايو',
                            6 => 'يونيو',
                            7 => 'يوليو',
                            8 => 'أغسطس',
                            9 => 'سبتمبر',
                            10 => 'أكتوبر',
                            11 => 'نوفمبر',
                            12 => 'ديسمبر',
                        ];
                    @endphp
                    @foreach ($months as $num => $name)
                        <option value="{{ $num }}" {{ $num == date('n') ? 'selected' : '' }}>
                            {{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="filter-company" class="form-label">الشركة</label>
                <select id="filter-company" class="form-control select2" data-datatable-filter="true" data-filter-key="company_id" data-placeholder="جميع الشركات">
                    <option value=""></option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="filter-portfolio" class="form-label">المحفظة</label>
                <select id="filter-portfolio" class="form-control select2" data-datatable-filter="true" data-filter-key="portfolio_id" data-placeholder="اختر المحفظة">
                    <option value=""></option>
                    @foreach ($portfolios as $portfolio)
                        <option value="{{ $portfolio->id }}" data-company="{{ $portfolio->company_id }}">
                            {{ $portfolio->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="filter-branch" class="form-label">الفرع</label>
                <select id="filter-branch" class="form-control select2" data-datatable-filter="true" data-filter-key="branch_id" data-placeholder="جميع الفروع">
                    <option value=""></option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="filter-team" class="form-label">الفريق</label>
                <select id="filter-team" class="form-control select2" data-datatable-filter="true" data-filter-key="team_id" data-placeholder="جميع الفرق">
                    <option value=""></option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" data-branch="{{ $team->branch_id }}">
                            {{ $team->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="filter-user" class="form-label">الموظف</label>
                <select id="filter-user" class="form-control select2" data-datatable-filter="true" data-filter-key="user_id" data-placeholder="جميع الموظفين">
                    <option value=""></option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" data-team="{{ $user->team_id }}">
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </x-data-table.filters>

        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>

    {{-- Modal للتصدير --}}
    <div class="modal fade" id="exportFieldsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تخصيص حقول التصدير</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="fieldsContainer">
                    <!-- الحقول ستظهر هنا ديناميكيًا -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" id="confirmExport" class="btn btn-primary">تصدير</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal التحميل --}}
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <div id="loadingMessage">جاري المعالجة...</div>
                </div>
            </div>
        </div>
    </div>
@endsection
