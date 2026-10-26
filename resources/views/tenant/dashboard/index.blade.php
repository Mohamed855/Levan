@extends('tenant.layouts.layoutMaster')

@section('title', 'لوحة القيادة الرئيسية - Levan')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/scss/tenant/pages/dashboard.scss'])
@endsection

@section('page-script')
    <script>
        window.tenantDashboardData = {
            auctionActivity: @json($auctionActivity),
            propertyStatus: @json($propertyStatus),
            auctionActivityUrl: "{{ tenant_route_name('dashboard.auction-activity') }}"
        };
    </script>
    @vite(['resources/assets/js/tenant/dashboard/index.js', 'resources/assets/js/shared/chart/chart.js'])
@endsection

@section('content')
    {{-- بطاقة الترحيب وحالة المنشأة --}}
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card tenant-welcome-card shadow-none">
                <div
                    class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 p-4">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h4 class="welcome-title mb-0">مرحباً بك، {{ auth()->user()->name }} 👋</h4>
                            <span
                                class="badge bg-label-primary font-monospace">{{ $tenant?->company_name ?? tenant('id') }}</span>
                        </div>
                        <p class="welcome-sub mb-0">لوحة تحكم وإدارة المزادات العقارية ومتابعة الأداء التشغيلي والإداري</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="license-pill">
                            <i class="ti ti-award text-success fs-5"></i>
                            <span>الباقة: <strong>{{ $subscription['plan_name'] ?? 'الأساسية' }}</strong></span>
                            <span
                                class="badge bg-label-success ms-1">{{ $subscription['subscription_status'] ?? 'نشط' }}</span>
                        </div>
                        @can('settings.index')
                            <a href="{{ tenant_route_name('settings.index') }}" class="btn btn-sm btn-icon btn-label-secondary"
                                title="إعدادات المنشأة">
                                <i class="ti ti-settings"></i>
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- مؤشرات الأداء الرئيسية (6 كروت متناسقة تبدأ بإجمالي المستخدمين) --}}
    <div class="row g-4 mb-4">
        <x-ui.stat-card title="إجمالي المستخدمين" :value="$kpis['active_users'] . ' / ' . ($kpis['total_seats'] > 0 ? $kpis['total_seats'] : 'غير محدود')" subtitle="مقاعد مستخدمة من الرخصة" icon="ti-users"
            color="primary" />

        <x-ui.stat-card title="إجمالي الإسنادات" :value="number_format($kpis['total_assignments'])" subtitle="قرارات إسناد مسجلة" icon="ti-folders"
            color="info" />

        <x-ui.stat-card title="المزادات النشطة" :value="number_format($kpis['active_auctions'])" subtitle="مزادات جارية حالياً" icon="ti-gavel"
            color="warning" />

        <x-ui.stat-card title="المزادات القادمة" :value="number_format($kpis['upcoming_auctions'])" subtitle="قيد الإعداد والاعتماد" icon="ti-calendar-event"
            color="secondary" />

        <x-ui.stat-card title="إجمالي العقارات" :value="number_format($kpis['total_properties'])" subtitle="إجمالي العقارات المسجلة" icon="ti-building"
            color="dark" />

        <x-ui.stat-card title="العقارات المباعة" :value="number_format($kpis['sold_properties'])" subtitle="تمت ترسيتها بنجاح" icon="ti-circle-check"
            color="success" />
    </div>

    {{-- قسم الرسوم البيانية التفاعلية --}}
    <div class="row g-4 mb-4">
        {{-- مخطط حركة المزادات --}}
        <div class="col-lg-6">
            <x-ui.dashboard-panel class="h-100" title="حركة المزادات" icon="ti-chart-bar">
                <x-slot:headerAction>
                    <select id="auction-activity-range" class="form-select select2 chart-range-select"
                        aria-label="تحديد النطاق الزمني">
                        <x-form.controls.enum-options :enum="\App\Enums\Tenants\Dashboard\DashboardActivityRange::class" :selected="null" />
                    </select>
                </x-slot:headerAction>

                <div class="auction-activity-chart-wrap">
                    <canvas id="auctionActivityChart"></canvas>
                </div>
            </x-ui.dashboard-panel>
        </div>

        <div class="col-lg-6">
            <x-ui.dashboard-panel class="h-100" title="توزيع حالات العقارات" icon="ti-donut" icon-color="info">
                <div class="row g-0 row-gap-3 h-100 align-items-center">
                    {{-- قائمة النسب والحالات --}}
                    <div class="col-sm-6 col-lg-12 col-xxl-6 d-flex flex-column justify-content-center pe-md-3">
                        @foreach ($propertyStatus as $item)
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
                    {{-- الرسم الدائري --}}
                    <div class="col-sm-6 col-lg-12 col-xxl-6 d-flex align-items-center justify-content-center">
                        <div class="property-status-chart-wrap">
                            <canvas id="propertyStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </x-ui.dashboard-panel>
        </div>
    </div>

    {{-- منطقة العمليات والرادار اليومي --}}
    <div class="row g-4 mb-4">
        {{-- العمود الأيمن: إجراءات سريعة + أحدث الأنشطة --}}
        <div class="col-lg-6 d-flex flex-column gap-4">
            {{-- إجراءات سريعة --}}
            <x-ui.dashboard-panel title="إجراءات سريعة" icon="ti-bolt">
                <div class="row g-3">
                    @can('auctions.create')
                        <div class="col-4">
                            <a href="{{ tenant_route_name('auctions.create') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="ti ti-gavel fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">إضافة مزاد</span>
                            </a>
                        </div>
                    @endcan

                    @can('properties.create')
                        <div class="col-4">
                            <a href="{{ tenant_route_name('properties.create') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-label-success">
                                        <i class="ti ti-building-estate fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">إضافة عقار</span>
                            </a>
                        </div>
                    @endcan

                    @can('assignments.create')
                        <div class="col-4">
                            <a href="{{ tenant_route_name('assignments.create') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-label-info">
                                        <i class="ti ti-file-plus fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">إسناد جديد</span>
                            </a>
                        </div>
                    @endcan

                    @can('users.create')
                        <div class="col-4">
                            <a href="{{ tenant_route_name('users.create') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-label-warning">
                                        <i class="ti ti-user-plus fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">إضافة مستخدم</span>
                            </a>
                        </div>
                    @endcan

                    @can('assignments.view')
                        <div class="col-4">
                            <a href="{{ tenant_route_name('assignments.index') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-label-secondary">
                                        <i class="ti ti-folders fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">الإسنادات</span>
                            </a>
                        </div>
                    @endcan

                    <div class="col-4">
                        <a href="{{ tenant_route_name('tickets.index') }}" class="quick-action-box">
                            <div class="avatar avatar-md mb-2">
                                <span class="avatar-initial rounded bg-label-dark">
                                    <i class="ti ti-headset fs-4"></i>
                                </span>
                            </div>
                            <span class="action-title">الدعم الفني</span>
                        </a>
                    </div>
                </div>
            </x-ui.dashboard-panel>

            {{-- أحدث الأنشطة --}}
            <x-ui.dashboard-panel class="flex-grow-1" title="أحدث الأنشطة" icon="ti-clock" body-class="p-0">
                <x-slot:headerAction>
                    @can('activity_log.view')
                        <a href="{{ tenant_route_name('activity-logs.index') }}" class="btn btn-sm btn-label-primary">
                            عرض الكل
                        </a>
                    @endcan
                </x-slot:headerAction>

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
                                            @php
                                                $causer = $activity->causer;
                                                $hasImage = filled($causer?->image);
                                            @endphp
                                            <div class="avatar avatar-xs me-2">
                                                @if ($hasImage)
                                                    <img src="{{ tasset($causer->image) }}" alt=""
                                                        class="rounded-circle object-fit-cover">
                                                @else
                                                    <span class="avatar-initial rounded-circle bg-label-primary">
                                                        <i class="ti ti-user ti-xs"></i>
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="fw-medium text-heading small">{{ $activity->causer_name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-label-primary small dashboard-activity-event"
                                                title="{{ $activity->formatted_event }}">
                                                {{ $activity->formatted_event }}
                                            </span>
                                            @if ($activity->formatted_module)
                                                <span class="text-muted small">({{ $activity->formatted_module }})</span>
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
                                        لا توجد أنشطة مسجلة حديثاً
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.dashboard-panel>
        </div>

        {{-- العمود الأيسر: رادار المزادات الحالية والقادمة --}}
        <div class="col-lg-6">
            <x-ui.dashboard-panel class="h-100" title="رادار المزادات الحالية والقادمة" icon="ti-gavel"
                body-class="p-0">
                <x-slot:headerAction>
                    @can('auctions.view')
                        <a href="{{ tenant_route_name('auctions.index') }}" class="btn btn-sm btn-label-primary">
                            عرض الكل
                        </a>
                    @endcan
                </x-slot:headerAction>

                <div class="table-responsive text-nowrap">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>اسم المزاد</th>
                                <th>المدينة</th>
                                <th>النوع</th>
                                <th>العقارات</th>
                                <th>تاريخ الانطلاق</th>
                                <th>الحالة</th>
                                <th class="text-center">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            @forelse($latestAuctions as $auction)
                                <tr>
                                    <td>
                                        <span class="fw-medium text-heading d-block text-truncate"
                                            style="max-width: 160px;" title="{{ $auction->name }}">
                                            {{ $auction->name }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $auction->city ?: '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-info">{{ $auction->type_label }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold">{{ $auction->properties_count }}</span>
                                    </td>
                                    <td>
                                        <small
                                            class="text-muted">{{ $auction->start_date ? $auction->start_date->format('Y-m-d') : '-' }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $statusVal =
                                                $auction->status instanceof \BackedEnum
                                                    ? $auction->status->value
                                                    : (string) $auction->status;
                                            $badgeClass = match ($statusVal) {
                                                'active' => 'bg-label-success',
                                                'in_preparation' => 'bg-label-warning',
                                                'pending_approval' => 'bg-label-info',
                                                'completed' => 'bg-label-secondary',
                                                default => 'bg-label-primary',
                                            };
                                            $statusLabel =
                                                $auction->status instanceof \App\Enums\Tenants\Auctions\AuctionStatus
                                                    ? $auction->status->label()
                                                    : ($statusVal === 'active'
                                                        ? 'نشط'
                                                        : ($statusVal === 'in_preparation'
                                                            ? 'قيد الإعداد'
                                                            : $statusVal));
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ tenant_route_name('auctions.show', $auction) }}"
                                            class="btn btn-sm btn-icon btn-label-primary" title="عرض تفاصيل المزاد">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="ti ti-gavel fs-2 d-block mb-2"></i>
                                        لا توجد مزادات مسجلة حالياً
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.dashboard-panel>
        </div>
    </div>
@endsection
