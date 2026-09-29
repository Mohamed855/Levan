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
    @vite(['resources/css/dataTable.css', 'resources/scss/admin/pages/dashboard.scss'])
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
        <x-admin.dashboard-card title="طلبات الاشتراك الجديدة" :value="number_format($kpis['new_subscription_requests'])" subtitle="طلبات قيد المراجعة"
            icon="ti-user-plus" color="primary" />

        <x-admin.dashboard-card title="الشركات المشتركة" :value="number_format($kpis['subscribed_companies'])" subtitle="شركات لديها اشتراك نشط" icon="ti-building"
            color="success" />

        <x-admin.dashboard-card title="الاشتراكات النشطة" :value="number_format($kpis['active_subscriptions'])" subtitle="اشتراكات فعّالة حالياً"
            icon="ti-file-text" color="info" />

        <x-admin.dashboard-card title="الاشتراكات المعلقة" :value="number_format($kpis['suspended_subscriptions'])" subtitle="اشتراكات موقوفة مؤقتاً"
            icon="ti-clock" color="warning" />

        <x-admin.dashboard-card title="القوالب المركزية" :value="number_format($kpis['central_templates'])" subtitle="قالب متاح للتطبيق" icon="ti-template"
            color="primary" />

        {{-- 6. المدفوعات هذا الشهر --}}
        <x-admin.dashboard-card title="المدفوعات هذا الشهر" :value="number_format($kpis['monthly_payments'], 0)" subtitle="ر.س إجمالي هذا الشهر"
            icon="ti-wallet" color="success" />

    </div>

    {{-- ═══════════════════════════════════════
         Charts Row
    ═══════════════════════════════════════ --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100 dashboard-chart-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-chart-line me-1 text-primary"></i>
                        استخدام المنصة
                    </h5>
                    <select id="platform-activity-range" class="form-select chart-range-select w-25">
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

        <div class="col-lg-6">
            <div class="card h-100 dashboard-chart-card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-donut me-1 text-info"></i>
                        توزيع حالات الاشتراكات
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-0 row-gap-3 h-100">
                        {{-- القائمة اليمين --}}
                        <div class="col-sm-6 col-lg-12 col-xxl-6 d-flex flex-column justify-content-center pe-3">
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
                        <div class="col-sm-6 col-lg-12 col-xxl-6 d-flex align-items-center justify-content-center">
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
