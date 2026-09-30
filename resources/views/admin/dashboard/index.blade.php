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
        <x-admin.dashboard-card col="col-sm-4 col-lg-6 col-xxl-2" title="طلبات الاشتراك الجديدة" :value="number_format($kpis['new_subscription_requests'])"
            subtitle="طلبات قيد المراجعة" icon="ti-user-plus" color="primary" />

        <x-admin.dashboard-card col="col-sm-4 col-lg-6 col-xxl-2" title="الشركات المشتركة" :value="number_format($kpis['subscribed_companies'])"
            subtitle="شركات لديها اشتراك نشط" icon="ti-building" color="success" />

        <x-admin.dashboard-card col="col-sm-4 col-lg-6 col-xxl-2" title="الاشتراكات النشطة" :value="number_format($kpis['active_subscriptions'])"
            subtitle="اشتراكات فعّالة حالياً" icon="ti-file-text" color="info" />

        <x-admin.dashboard-card col="col-sm-4 col-lg-6 col-xxl-2" title="الاشتراكات المعلقة" :value="number_format($kpis['suspended_subscriptions'])"
            subtitle="اشتراكات موقوفة مؤقتاً" icon="ti-clock" color="warning" />

        <x-admin.dashboard-card col="col-sm-4 col-lg-6 col-xxl-2" title="القوالب المركزية" :value="number_format($kpis['central_templates'])"
            subtitle="قالب متاح للتطبيق" icon="ti-template" color="danger" />

        <x-admin.dashboard-card col="col-sm-4 col-lg-6 col-xxl-2" title="المدفوعات هذا الشهر" :value="number_format($kpis['monthly_payments'], 0)"
            subtitle="ر.س إجمالي هذا الشهر" icon="ti-wallet" color="dark" />
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

    {{-- ═══════════════════════════════════════
         Tables Row
    ═══════════════════════════════════════ --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6 d-flex flex-column gap-3">
            {{-- إجراءات سريعة --}}
            <div class="card dashboard-chart-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-bolt me-1 text-primary"></i>
                        إجراءات سريعة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-4">
                            <a href="{{ route('admin.companies.create') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="ti ti-building fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">إضافة شركة</span>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="{{ route('admin.users.create') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-label-success">
                                        <i class="ti ti-user-cog fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">إضافة مستخدم</span>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="{{ route('admin.plans.create') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-label-info">
                                        <i class="ti ti-package fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">إضافة باقة</span>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="{{ route('admin.coupons.create') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-label-warning">
                                        <i class="ti ti-ticket fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">إضافة كوبون</span>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="{{ route('admin.subscriptionManage.expired') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-label-danger">
                                        <i class="ti ti-timeline-event fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">تفعيل اشتراك</span>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="{{ route('admin.tickets.index') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-label-dark">
                                        <i class="ti ti-messages fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">الدعم الفنى</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- أحدث الأنشطة --}}
            <div class="card dashboard-chart-card flex-grow-1">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-clock me-1 text-primary"></i>
                        أحدث الأنشطة
                    </h5>
                    <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-sm btn-label-primary">
                        عرض الكل
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>المستخدم</th>
                                    <th>النشاط</th>
                                    <th>الوقت</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @forelse($recentActivities as $activity)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs me-2">
                                                    <span class="avatar-initial rounded-circle bg-label-secondary">
                                                        <i class="ti ti-user ti-xs"></i>
                                                    </span>
                                                </div>
                                                <span
                                                    class="fw-medium text-heading small">{{ $activity->causer_name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <span
                                                    class="badge bg-label-primary small">{{ $activity->formatted_event }}</span>
                                                @if ($activity->formatted_module)
                                                    <span
                                                        class="text-muted small">({{ $activity->formatted_module }})</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $activity->time_ago }}</small>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">
                                            <i class="ti ti-info-circle fs-4 d-block mb-1"></i>
                                            لا توجد أنشطة حديثة
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- أحدث طلبات الإشتراك --}}
        <div class="col-lg-6">
            <div class="card h-100 dashboard-chart-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-list me-1 text-primary"></i>
                        أحدث طلبات الإشتراك
                    </h5>
                    <a href="{{ route('admin.subscriptionManage.pending') }}" class="btn btn-sm btn-label-primary">
                        عرض الكل
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive text-nowrap">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>اسم الشركة</th>
                                    <th>الباقة</th>
                                    <th>تاريخ الطلب</th>
                                    <th>المبلغ</th>
                                    <th>الحالة</th>
                                    <th class="text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @forelse($latestSubscriptionRequests as $request)
                                    <tr>
                                        <td>
                                            <span class="fw-medium text-heading">
                                                {{ $request->tenant->company_name ?? ($request->tenant_id ?? '-') }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-label-info">
                                                {{ $request->plan->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $request->created_at?->format('Y-m-d') }}</small>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ number_format($request->price, 2) }} ر.س</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-label-warning">قيد المراجعة</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.subscriptionManage.pending') }}"
                                                class="btn btn-icon btn-sm btn-label-primary" title="عرض في الطلبات">
                                                <i class="ti ti-arrow-left"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="ti ti-inbox fs-2 d-block mb-2"></i>
                                            لا توجد طلبات اشتراك معلقة حالياً
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
