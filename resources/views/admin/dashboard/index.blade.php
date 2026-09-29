@extends('admin.layouts.layoutMaster')

@section('title', 'لوحة التحكم الرئيسية - LEVAN')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite([
        'resources/css/dataTable.css',
        'resources/scss/admin/pages/dashboard.scss'
    ])
@endsection

@section('page-script')
    <script>
        window.dashboardData = {
            subscriptionStatus: @json($subscriptionStatus),
            platformActivity: @json($platformActivity),
            platformActivityUrl: "{{ route('admin.dashboard.platform-activity') }}"
        };
    </script>
    @vite(['resources/assets/js/admin/dashboard/index.js', 'resources/assets/js/shared/chart/chart.js'])
@endsection

@section('content')
    {{-- ═══════════════════════════════════════
         KPI Cards (6 كروت)
    ═══════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- 1. طلبات الاشتراك الجديدة --}}
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">طلبات الاشتراك الجديدة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($kpis['new_subscription_requests']) }}</h4>
                            </div>
                            <small class="mb-0">طلبات قيد المراجعة</small>
                        </div>
                        <div class="avatar circle">
                            <span class="avatar-initial bg-label-primary">
                                <i class="ti ti-user-plus ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. الشركات المشتركة --}}
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">الشركات المشتركة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($kpis['subscribed_companies']) }}</h4>
                            </div>
                            <small class="mb-0">شركات لديها اشتراك نشط</small>
                        </div>
                        <div class="avatar circle">
                            <span class="avatar-initial bg-label-success">
                                <i class="ti ti-building ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. الاشتراكات النشطة --}}
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">الاشتراكات النشطة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($kpis['active_subscriptions']) }}</h4>
                            </div>
                            <small class="mb-0">اشتراكات فعّالة حالياً</small>
                        </div>
                        <div class="avatar circle">
                            <span class="avatar-initial bg-label-info">
                                <i class="ti ti-file-text ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. الاشتراكات المعلقة --}}
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">الاشتراكات المعلقة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($kpis['suspended_subscriptions']) }}</h4>
                            </div>
                            <small class="mb-0">اشتراكات موقوفة مؤقتاً</small>
                        </div>
                        <div class="avatar circle">
                            <span class="avatar-initial bg-label-warning">
                                <i class="ti ti-clock ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 5. القوالب المركزية --}}
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">القوالب المركزية</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($kpis['central_templates']) }}</h4>
                            </div>
                            <small class="mb-0">قالب متاح للتطبيق</small>
                        </div>
                        <div class="avatar circle">
                            <span class="avatar-initial bg-label-secondary">
                                <i class="ti ti-layers ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 6. المدفوعات هذا الشهر --}}
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المدفوعات هذا الشهر</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($kpis['monthly_payments'], 0) }}</h4>
                            </div>
                            <small class="mb-0">ر.س إجمالي هذا الشهر</small>
                        </div>
                        <div class="avatar circle">
                            <span class="avatar-initial bg-label-success">
                                <i class="ti ti-wallet ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ═══════════════════════════════════════
         Charts Row
    ═══════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- Chart 1: استخدام المنصة --}}
        <div class="col-xl-7 col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-chart-line me-1 text-primary"></i>
                        استخدام المنصة
                    </h5>
                    <select id="platform-activity-range" class="form-select chart-range-select w-auto">
                        <option value="1month">آخر شهر</option>
                        <option value="3months">آخر 3 أشهر</option>
                        <option value="6months">آخر 6 أشهر</option>
                        <option value="1year">آخر سنة</option>
                    </select>
                </div>
                <div class="card-body">
                    <div class="platform-activity-chart-wrap">
                        <canvas id="platformActivityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart 2: توزيع حالات الاشتراكات --}}
        <div class="col-xl-5 col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-donut me-1 text-info"></i>
                        توزيع حالات الاشتراكات
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-0 h-100">
                        {{-- القائمة اليمين --}}
                        <div class="col-6 d-flex flex-column justify-content-center pe-3">
                            @foreach ($subscriptionStatus as $item)
                                <div class="status-list-row">
                                    <span class="status-name-wrap">
                                        <span class="status-dot status-dot-{{ $item['color'] }}"></span>
                                        <span class="fw-medium status-label">{{ $item['label'] }}</span>
                                    </span>
                                    <span class="status-pct">{{ $item['percentage'] }}%</span>
                                    <span class="status-count">{{ number_format($item['count']) }}</span>
                                </div>
                            @endforeach
                        </div>
                        {{-- الدونت اليسار --}}
                        <div class="col-6 d-flex align-items-center justify-content-center">
                            <div class="subscription-status-chart-wrap">
                                <canvas id="subscriptionStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
