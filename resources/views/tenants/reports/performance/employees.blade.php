@extends('tenants.layouts.layoutMaster')
@section('title', 'تقارير أداء الموظفين')

@php
    $selectedDate = request('date_from') ? \Carbon\Carbon::parse(request('date_from')) : now();
    $selectedYear = (int) $selectedDate->format('Y');
    $selectedMonth = (int) $selectedDate->format('n');
@endphp

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
    <style>
        .stat-card-value {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .chart-card .card-body {
            min-height: 280px;
        }

        .filter-loading {
            opacity: .5;
            pointer-events: none;
            transition: opacity .2s;
        }
    </style>
@endsection

@section('page-script')
    <script>
        window.routes = {
            index: "{{ tenant_route_name('reports.employees.index') }}",
            stats: "{{ tenant_route_name('reports.employees.stats') }}",
            exportPDF: "{{ tenant_route_name('reports.employees.export.pdf') }}",
            exportExcel: "{{ tenant_route_name('reports.employees.export.excel') }}",
            showExportModal: "{{ tenant_route_name('reports.employees.show-export-modal') }}",
            checkProgress: "{{ tenant_route_name('reports.employees.check-progress') }}",
            download: "{{ tenant_route_name('reports.employees.download', ['file' => 'FILE_PLACEHOLDER']) }}",
            checkCount: "{{ tenant_route_name('reports.employees.check.count') }}",
        };
        window.csrfToken = "{{ csrf_token() }}";
        window.initialChartData = @json($chart_data);
        window.initialStats = @json($employees_statistics);

        {{-- غير مستخدم بعد التحويل لـ AJAX --}}
        {{-- window.employeesDistribution = @json($employees_distribution); --}}
        {{-- window.topPerformers         = @json($top_performers); --}}
    </script>
    @vite(['resources/assets/js/tenants/reports/performance/employees.js'])
@endsection

@section('content')

    {{-- قسم الفلاتر --}}
    <x-data-table.filters id="filters-card" table="#employee-performance-table" reset-id="reset-filters">
        <div class="col-md-4">
            <label class="form-label">السنة</label>
            <select id="filter-year" class="form-control select2" data-datatable-filter="true" data-filter-key="year" data-placeholder="اختر السنة">
                <option value=""></option>
                @for ($year = date('Y'); $year >= date('Y') - 5; $year--)
                    <option value="{{ $year }}" @selected($year == $selectedYear)>{{ $year }}</option>
                @endfor
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">الشهر</label>
            <select id="filter-month" class="form-control select2" data-datatable-filter="true" data-filter-key="month" data-placeholder="اختر الشهر">
                <option value=""></option>
                @foreach ($months as $num => $name)
                    <option value="{{ $num }}" @selected($num == $selectedMonth)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">الفريق</label>
            <select id="filter-team" class="form-control select2" data-datatable-filter="true" data-filter-key="team_id" data-placeholder="جميع الفرق">
                <option value=""></option>
                @foreach ($teams as $team)
                    <option value="{{ $team->id }}"
                        @selected((string) request('team_id') === (string) $team->id)>
                        {{ $team->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">الموظف</label>
            <select id="filter-user" class="form-control select2" data-datatable-filter="true" data-filter-key="user_id" data-placeholder="جميع الموظفين">
                <option value=""></option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" data-team="{{ $user->team_id }}"
                        @selected((string) request('user_id') === (string) $user->id)>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </x-data-table.filters>

    {{-- بطاقات الإحصائيات — الصف الأول --}}
    <div class="row g-4 mb-4" id="stats-row-1">

        <div id="stat-col-employees" class="col-sm-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-heading">إجمالي الموظفين</span>
                            <div class="my-1">
                                <h4 class="mb-0 stat-value" id="stat-total-employees">
                                    {{ number_format($employees_statistics['total_employees']) }}
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
        </div>

        <div id="stat-col-customers" class="col-sm-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-heading">إجمالي العملاء</span>
                            <div class="my-1">
                                <h4 class="mb-0 stat-value" id="stat-total-customers">
                                    {{ number_format($employees_statistics['total_customers']) }}
                                </h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-user-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="stat-col-collection" class="col-sm-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-heading">إجمالي التحصيل</span>
                            <div class="my-1">
                                <h4 class="mb-0 stat-value" id="stat-total-collection">
                                    {{ number_format($employees_statistics['total_collection']) }}
                                </h4>
                            </div>
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

        <div id="stat-col-portfolio" class="col-sm-6 col-xl-3">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-heading">إجمالي مديونية المحافظ</span>
                            <div class="my-1">
                                <h4 class="mb-0 stat-value" id="stat-total-portfolio">
                                    {{ number_format($employees_statistics['total_portfolio']) }}
                                </h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-briefcase ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- بطاقات الإحصائيات — الصف الثاني --}}
    <div class="row g-4 mb-4" id="stats-row-2">

        <div id="stat-col-rate" class="col-sm-6 col-xl-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-heading">نسبة التحصيل</span>
                            <div class="my-1">
                                <h4 class="mb-0 stat-value" id="stat-collection-percentage">
                                    {{ number_format($employees_statistics['collection_percentage'], 2) }}%
                                </h4>
                            </div>
                            <small class="text-muted">من إجمالي المحفظة</small>
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

        <div id="stat-col-achieving" class="col-sm-6 col-xl-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-heading">الموظفون المحققون للهدف</span>
                            <div class="my-1">
                                <h4 class="mb-0 stat-value" id="stat-achieving-target">
                                    {{ number_format($employees_statistics['employees_achieving_target']) }}
                                </h4>
                            </div>
                            <small class="text-muted">
                                من <span
                                    id="stat-total-emp-sub">{{ number_format($employees_statistics['total_employees']) }}</span>
                                موظف
                            </small>
                        </div>
                        <div class="avatar">

                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-target ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="stat-col-avg" class="col-sm-6 col-xl-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-heading">متوسط الأداء العام</span>
                            <div class="my-1">
                                <h4 class="mb-0 stat-value" id="stat-avg-performance">
                                    {{ number_format($employees_statistics['average_performance'], 1) }}%
                                </h4>
                            </div>
                            <small class="text-muted">معدل تحقيق الهدف</small>
                        </div>
                        <div class="avatar">

                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-chart-line ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- المخططات الأربعة --}}
    <div class="row g-4 mb-4" id="charts-row">

        {{-- ١. تحصيل الموظفين --}}
        <div class="col-md-6">
            <div class="card chart-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ti ti-cash me-2 text-success"></i>تحصيل الموظفين</h6>
                </div>
                <div class="card-body">
                    <div id="chart-collections"></div>
                </div>
            </div>
        </div>

        {{-- ٢. متابعات الموظفين --}}
        <div class="col-md-6">
            <div class="card chart-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ti ti-phone me-2 text-info"></i>متابعات الموظفين</h6>
                </div>
                <div class="card-body">
                    <div id="chart-follows"></div>
                </div>
            </div>
        </div>

        {{-- ٣. المهام المتأخرة --}}
        <div class="col-md-6">
            <div class="card chart-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ti ti-alert-triangle me-2 text-warning"></i>المهام المتأخرة</h6>
                    <small class="text-muted">مهام معلقة تجاوزت تاريخ الاستحقاق</small>
                </div>
                <div class="card-body">
                    <div id="chart-not-contacted"></div>
                </div>
            </div>
        </div>

        {{-- ٤. تحقيق الأهداف --}}
        <div class="col-md-6">
            <div class="card chart-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="ti ti-target me-2 text-primary"></i>تحقيق الأهداف</h6>
                    <small class="text-muted">% من المستهدف</small>
                </div>
                <div class="card-body">
                    <div id="chart-targets"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول التقرير --}}
    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'employee-performance-table'],
                false,
            ) !!}
        </div>
    </div>

    {{-- مودال التصدير --}}
    <div class="modal fade" id="exportFieldsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-file-download me-2"></i>تخصيص حقول التصدير</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="fieldsContainer"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                            class="ti ti-x me-1"></i>إلغاء</button>
                    <button type="button" id="confirmExport" class="btn btn-primary"><i
                            class="ti ti-download me-1"></i>تصدير</button>
                </div>
            </div>
        </div>
    </div>

    {{-- مودال التحميل --}}
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
