@extends('tenants.layouts.layoutMaster')

@section('title', 'لوحة التحكم - الموظف')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css', 'resources/assets/css/tenants/dashboard/user/dashboard.css'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenants/dashboard/user/dashboard.js', 'resources/assets/js/tenants/dashboard/user/comparison.js', 'resources/assets/js/tenants/dashboard/user/my-overdue-tasks.js', 'resources/assets/js/shared/chart/chart.js'])
    <script>
        window.userRoutes = {
            myUpcomingFollowUps: "{{ tenant_route_name('dashboard.myUpcomingFollowUps') }}",
            myPerformanceComparison: "{{ tenant_route_name('dashboard.myPerformanceComparison') }}",
            myOverdueTasks: "{{ tenant_route_name('dashboard.myOverdueTasks') }}",
            exportMyOverdueTasks: "{{ tenant_route_name('dashboard.exportMyOverdueTasks') }}"
        };

        window.userDashboardData = @json([
            'user' => $user,
            'comparisonData' => $comparisonData,
        ]);
    </script>
@endsection

@section('content')
    <div class="rtl-direction">

        {{-- ===== Welcome Card ===== --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card welcome-card">
                    <div class="card-body p-5">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <h4 class="mb-3 text-white fw-bold">{{ $welcomeMessage }}</h4>
                                <p class="mb-3 text-white opacity-75">نظرة على أدائك اليوم وتقدمك نحو تحقيق أهدافك</p>

                                <div class="d-flex gap-2 flex-wrap">
                                    {{-- مهام اليوم --}}
                                    <div class="card border-0 shadow-sm bg-white" style="min-width: 130px;">
                                        <div class="card-body text-center py-2 px-3">
                                            <h6 class="fw-bold text-primary mb-0">{{ $myWorkStats['today_tasks'] ?? 0 }}
                                            </h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">مهام اليوم</small>
                                        </div>
                                    </div>

                                    {{-- المسندون الجدد --}}
                                    <div class="card border-0 shadow-sm bg-white" style="min-width: 130px;">
                                        <div class="card-body text-center py-2 px-3">
                                            <h6 class="fw-bold text-success mb-0">
                                                {{ $myCustomersData['assigned']['count'] ?? 0 }}</h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">مسندون جدد</small>
                                        </div>
                                    </div>

                                    {{-- المحولون إليّ --}}
                                    <div class="card border-0 shadow-sm bg-white" style="min-width: 130px;">
                                        <div class="card-body text-center py-2 px-3">
                                            <h6 class="fw-bold text-info mb-0">
                                                {{ $myCustomersData['transferred']['count'] ?? 0 }}</h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">محولون إليّ</small>
                                        </div>
                                    </div>

                                    {{-- المهام المتأخرة --}}
                                    <div class="card border-0 shadow-sm bg-white" style="min-width: 130px;">
                                        <div class="card-body text-center py-2 px-3">
                                            <h6 class="fw-bold text-danger mb-0">{{ $myWorkStats['overdue_tasks'] ?? 0 }}
                                            </h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">مهام متأخرة</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 d-none d-lg-block text-center">
                                <img src="{{ asset('assets/img/branding/logo.svg') }}" alt="Dashboard Illustration"
                                    height="200" class="mt-4">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Quick Stats Cards ===== --}}
        <div class="row g-3 mb-4">
            {{-- 1) إجمالي تحصيلاتي --}}
            <div class="col-md-3 col-sm-6">
                <div class="card h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div>
                                <h6 class="mb-0">إجمالي تحصيلاتي</h6>
                                <small class="text-muted">الشهر الحالي</small>
                            </div>
                            <div class="bg-info bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center rounded-2"
                                style="width:40px;height:40px;">
                                <i class="fa fa-clipboard"></i>
                            </div>
                        </div>
                        <h4 class="mb-0 d-flex align-items-center" style="gap:.5rem;">
                            {{ number_format($myQuickStats['month_total_amount'] ?? 0, 0) }}
                            <img src="{{ asset('assets/shared/images/Saudi_Riyal.png') }}" alt="SAR"
                                style="width:22px;height:22px;object-fit:contain;">
                        </h4>
                    </div>
                </div>
            </div>

            {{-- 2) التحصيلات المُعتمدة --}}
            <div class="col-md-3 col-sm-6">
                <div class="card h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div>
                                <h6 class="mb-0">التحصيلات المُعتمدة</h6>
                                <small class="text-muted">الشهر الحالي</small>
                            </div>
                            <div class="bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center rounded-2"
                                style="width:40px;height:40px;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <h4 class="mb-0 d-flex align-items-center" style="gap:.5rem;">
                            {{ number_format($myQuickStats['month_approved_amount'] ?? 0, 0) }}
                            <img src="{{ asset('assets/shared/images/Saudi_Riyal.png') }}" alt="SAR"
                                style="width:22px;height:22px;object-fit:contain;">
                        </h4>
                    </div>
                </div>
            </div>

            {{-- 3) قيد الاعتماد --}}
            <div class="col-md-3 col-sm-6">
                <div class="card h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div>
                                <h6 class="mb-0">قيد الاعتماد</h6>
                                <small class="text-muted">الشهر الحالي</small>
                            </div>
                            <div class="bg-warning bg-opacity-10 text-warning d-inline-flex align-items-center justify-content-center rounded-2"
                                style="width:40px;height:40px;">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <h4 class="mb-0 d-flex align-items-center" style="gap:.5rem;">
                            {{ number_format($myQuickStats['month_pending_amount'] ?? 0, 0) }}
                            <img src="{{ asset('assets/shared/images/Saudi_Riyal.png') }}" alt="SAR"
                                style="width:22px;height:22px;object-fit:contain;">
                        </h4>
                    </div>
                </div>
            </div>

            {{-- 4) مقارنة هذا الشهر بالشهر الماضي --}}
            <div class="col-md-3 col-sm-6">
                <div class="card h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div>
                                <h6 class="mb-0">مقارنة الشهر بالشهر الماضي</h6>
                                <small class="text-muted">هذا الشهر / الشهر الماضي</small>
                            </div>
                            <div class="bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center rounded-2"
                                style="width:40px;height:40px;">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                        <h4 class="mb-0 d-flex align-items-center" style="gap:.5rem;">
                            {{ number_format($myQuickStats['month_vs_last']['this_month_amount'] ?? 0, 0) }}
                            /
                            {{ number_format($myQuickStats['month_vs_last']['last_month_amount'] ?? 0, 0) }}
                            <img src="{{ asset('assets/shared/images/Saudi_Riyal.png') }}" alt="SAR"
                                style="width:22px;height:22px;object-fit:contain;">
                        </h4>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Performance Comparison - يظهر فقط لو الإعداد مفعّل ===== --}}
        @if ($dashboardSetting->compare_effort)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h5 class="card-title mb-1">
                                    <i class="ti ti-chart-bar me-2 text-primary"></i>
                                    مقارنة أدائي مع زملائي
                                </h5>
                                <small class="text-muted d-block" style="font-size: 0.8125rem;">
                                    مقارنة أدائك مع زملائك بناءً على مؤشرات الأداء للفترة المحددة
                                </small>
                            </div>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="userDataTypeFilter"
                                        id="userCollectionsTab" value="collections" checked>
                                    <label class="btn btn-outline-primary btn-sm" for="userCollectionsTab">
                                        <i class="ti ti-cash me-1"></i> التحصيلات
                                    </label>
                                    <input type="radio" class="btn-check" name="userDataTypeFilter"
                                        id="userFollowsTab" value="follows">
                                    <label class="btn btn-outline-secondary btn-sm" for="userFollowsTab">
                                        <i class="ti ti-phone-call me-1"></i> المتابعات
                                    </label>
                                </div>
                                <select id="userComparisonPeriodFilter" class="form-select form-select-sm"
                                    style="width: 150px;">
                                    <option value="today">اليوم</option>
                                    <option value="week">هذا الأسبوع</option>
                                    <option value="month" selected>هذا الشهر</option>
                                    <option value="quarter">ربع سنوي</option>
                                    <option value="year">سنوي</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="userComparisonLoader" class="text-center d-none p-4">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">جاري التحميل...</span>
                                </div>
                            </div>
                            <div id="comparison-container">
                                @if (isset($comparisonData['top_collections']) && count($comparisonData['top_collections']) > 0)
                                    <div id="collectionsComparisonContainer">
                                        {{-- سيتم إنشاء المخطط هنا بواسطة JavaScript --}}
                                    </div>
                                @else
                                    <div class="text-center py-5">
                                        <i class="ti ti-chart-bar" style="font-size: 3rem; color: #d9d9d9;"></i>
                                        <h6 class="mt-3 text-muted">لا توجد بيانات للمقارنة</h6>
                                        <small class="text-muted">يرجى التواصل مع مدير النظام</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===== المهام اليومية - يظهر فقط لو الإعداد مفعّل ===== --}}
        @if ($dashboardSetting->daily_tasks)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">المهام اليومية</h5>
                            <span class="badge bg-label-primary px-3 py-2">{{ now()->format('Y/m/d') }}</span>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table id="my-upcoming-followups-table"
                                    class="table table-striped table-hover table-bordered nowrap" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 24%;">العميل</th>
                                            <th style="width: 14%;" class="text-center">رقم الحساب</th>
                                            <th style="width: 14%;" class="text-center">رقم الهوية</th>
                                            <th style="width: 16%;" class="text-center">الشركة</th>
                                            <th style="width: 16%;" class="text-center">المحفظة</th>
                                            <th style="width: 16%;" class="text-center">نتيجة المتابعة</th>
                                            <th style="width: 16%;" class="text-center">المديونية</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- تعبئة عبر DataTables --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ===== مهامي المتأخرة - يظهر فقط لو الإعداد مفعّل ===== --}}
        @if ($dashboardSetting->Overdue_tasks)
            <div class="row g-6">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h5 class="card-title mb-1">مهامي المتأخرة</h5>
                                <small class="text-muted">المتابعات التي تجاوزت موعدها المحدد</small>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <select id="myOverdueResultTypeFilter" class="form-select form-select-sm"
                                    style="width:160px;">
                                    <option value="">كل الأنواع</option>
                                    <option value="3">وعد بالسداد</option>
                                    <option value="4">معاودة اتصال</option>
                                    <option value="9">طلب مستندات</option>
                                </select>

                                <select id="myOverdueDaysFilter" class="form-select form-select-sm" style="width:160px;">
                                    <option value="">كل الفترات</option>
                                    <option value="1">أمس فقط</option>
                                    <option value="3">آخر 3 أيام</option>
                                    <option value="7">آخر أسبوع</option>
                                    <option value="30">آخر شهر</option>
                                    <option value="custom">نطاق مخصص</option>
                                </select>

                                <div id="myCustomDateRange" class="d-none" style="display: flex; gap: 5px;">
                                    <input type="date" id="myOverdueDateFrom" class="form-control form-control-sm"
                                        style="width:140px;">
                                    <input type="date" id="myOverdueDateTo" class="form-control form-control-sm"
                                        style="width:140px;">
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-3">
                            <div class="table-responsive">
                                <table id="my-overdue-tasks-table"
                                    class="table table-striped table-hover table-bordered nowrap" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 6%; text-align:center;">#</th>
                                            <th style="width: 22%;">العميل</th>
                                            <th style="width: 12%;" class="text-center">رقم الحساب</th>
                                            <th style="width: 14%;" class="text-center">الشركة</th>
                                            <th style="width: 14%;" class="text-center">المحفظة</th>
                                            <th style="width: 14%;" class="text-center">نوع المهمة</th>
                                            <th style="width: 12%;" class="text-center">التاريخ المحدد</th>
                                            <th style="width: 10%;" class="text-center">أيام التأخير</th>
                                            <th style="width: 12%;" class="text-center">المديونية</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection
