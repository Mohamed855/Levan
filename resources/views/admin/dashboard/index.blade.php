@extends('admin.layouts.layoutMaster')

@section('title', 'لوحة القيادة الرئيسية - Levan')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/scss/admin/pages/dashboard.scss'])
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
    <div class="row g-4 mb-4">
        <x-ui.stat-card title="طلبات الاشتراك الجديدة" :value="number_format($kpis['new_subscription_requests'])" subtitle="طلبات قيد المراجعة" icon="ti-user-plus"
            color="primary" />

        <x-ui.stat-card title="العملاء المشتركين" :value="number_format($kpis['subscribed_companies'])" subtitle="شركات لديها اشتراك نشط" icon="ti-building"
            color="success" />

        <x-ui.stat-card title="الاشتراكات النشطة" :value="number_format($kpis['active_subscriptions'])" subtitle="اشتراكات فعّالة حالياً" icon="ti-file-text"
            color="info" />

        <x-ui.stat-card title="الاشتراكات المعلقة" :value="number_format($kpis['suspended_subscriptions'])" subtitle="اشتراكات موقوفة مؤقتاً" icon="ti-clock"
            color="warning" />

        <x-ui.stat-card title="القوالب المركزية" :value="number_format($kpis['central_templates'])" subtitle="قالب متاح للتطبيق" icon="ti-template"
            color="danger" />

        <x-ui.stat-card title="المدفوعات هذا الشهر" :value="number_format($kpis['monthly_payments'], 0)" subtitle="ر.س إجمالي هذا الشهر" icon="ti-wallet"
            color="label-dark text-dark" />
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <x-admin.dashboard-panel class="h-100" title="استخدام المنصة" icon="ti-chart-bar">
                <x-slot:headerAction>
                    <select id="platform-activity-range" class="form-select select2 chart-range-select"
                        aria-label="تحديد النطاق الزمني">
                        <x-form.controls.enum-options :enum="\App\Enums\Admin\Dashboard\ActivityRange::class" :selected="\App\Enums\Admin\Dashboard\ActivityRange::ONE_MONTH->value" />
                    </select>
                </x-slot:headerAction>

                <div class="platform-activity-chart-wrap">
                    <canvas id="platformActivityChart"></canvas>
                </div>
            </x-admin.dashboard-panel>
        </div>

        <div class="col-lg-6">
            <x-admin.dashboard-panel class="h-100" title="توزيع حالات الاشتراكات" icon="ti-donut" icon-color="info">
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
            </x-admin.dashboard-panel>
        </div>
    </div>

    {{-- Tables Row --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6 d-flex flex-column gap-4">
            {{-- إجراءات سريعة --}}
            <x-admin.dashboard-panel title="إجراءات سريعة" icon="ti-bolt">
                <div class="row g-3">
                    @can('clients.create')
                        <div class="col-4">
                            <a href="{{ route('admin.clients.create') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-primary">
                                        <i class="ti ti-building fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">إضافة عميل</span>
                            </a>
                        </div>
                    @endcan

                    @can('users.create')
                        <div class="col-4">
                            <a href="{{ route('admin.users.create') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-success">
                                        <i class="ti ti-user-cog fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">إضافة مستخدم</span>
                            </a>
                        </div>
                    @endcan

                    @can('plans.create')
                        <div class="col-4">
                            <a href="{{ route('admin.plans.create') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-info">
                                        <i class="ti ti-package fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">إضافة باقة</span>
                            </a>
                        </div>
                    @endcan

                    @can('coupons.create')
                        <div class="col-4">
                            <a href="{{ route('admin.coupons.create') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-warning">
                                        <i class="ti ti-ticket fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">إضافة كوبون</span>
                            </a>
                        </div>
                    @endcan

                    @can('subscriptions.view')
                        <div class="col-4">
                            <a href="{{ route('admin.subscriptionManage.expired') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-danger">
                                        <i class="ti ti-timeline-event fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">تفعيل اشتراك</span>
                            </a>
                        </div>
                    @endcan

                    @can('tickets.view')
                        <div class="col-4">
                            <a href="{{ route('admin.tickets.index') }}" class="quick-action-box">
                                <div class="avatar avatar-md mb-2">
                                    <span class="avatar-initial rounded bg-label-dark text-dark">
                                        <i class="ti ti-messages fs-4"></i>
                                    </span>
                                </div>
                                <span class="action-title">الدعم الفنى</span>
                            </a>
                        </div>
                    @endcan
                </div>
            </x-admin.dashboard-panel>

            {{-- أحدث الأنشطة --}}
            <x-admin.dashboard-panel class="flex-grow-1" title="أحدث الأنشطة" icon="ti-clock" body-class="p-0">
                <x-slot:headerAction>
                    @can('activity_log.view')
                        <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-list me-1"></i>عرض الكل
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
                                                $avatarUrl = null;
                                                if ($causer) {
                                                    $rawImage = $causer->image ?: ($causer->profile_picture ?? null);
                                                    if ($rawImage) {
                                                        if (\Illuminate\Support\Str::startsWith($rawImage, ['http://', 'https://'])) {
                                                            $avatarUrl = $rawImage;
                                                        } else {
                                                            $cleanPath = ltrim(\Illuminate\Support\Str::after($rawImage, 'storage/'), '/');
                                                            $avatarUrl = asset('storage/' . $cleanPath);
                                                        }
                                                    } elseif (isset($causer->profile_photo_url) && !empty($causer->profile_photo_url)) {
                                                        $avatarUrl = $causer->profile_photo_url;
                                                    }
                                                }
                                            @endphp
                                            <div class="avatar avatar-xs me-2">
                                                @if ($avatarUrl)
                                                    <img src="{{ $avatarUrl }}" alt="{{ $activity->causer_name }}"
                                                        class="rounded-circle object-fit-cover w-100 h-100 border">
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
                                        <div class="d-inline-flex align-items-center gap-1 overflow-hidden" style="max-width: 210px;">
                                            @php
                                                $eventClass = match($activity->event) {
                                                    'created' => 'bg-label-success',
                                                    'updated' => 'bg-label-info',
                                                    'deleted' => 'bg-label-danger',
                                                    'login'   => 'bg-label-primary',
                                                    default   => 'bg-label-secondary',
                                                };
                                            @endphp
                                            <span class="badge {{ $eventClass }} small dashboard-activity-event text-truncate"
                                                title="{{ $activity->formatted_event }}">{{ $activity->formatted_event }}</span>
                                            @if ($activity->formatted_module)
                                                <span class="text-muted small text-truncate" style="max-width: 90px;"
                                                    title="{{ $activity->formatted_module }}">({{ $activity->formatted_module }})</span>
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
            </x-admin.dashboard-panel>
        </div>

        {{-- أحدث طلبات الإشتراك --}}
        <div class="col-lg-6">
            <x-admin.dashboard-panel class="h-100" title="أحدث طلبات الإشتراك" icon="ti-list" body-class="p-0">
                <x-slot:headerAction>
                    @can('subscriptions.view')
                        <a href="{{ route('admin.subscriptionManage.pending') }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-list me-1"></i>عرض الكل
                        </a>
                    @endcan
                </x-slot:headerAction>

                <div class="table-responsive text-nowrap">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>اسم الشركة</th>
                                <th>الباقة</th>
                                <th>تاريخ الطلب</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                                <th class="text-center">الإجراء</th>
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
                                        <span class="badge bg-info">
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
                                        <span class="badge bg-warning">قيد المراجعة</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.subscriptionManage.pending') }}"
                                            class="btn btn-sm btn-icon btn-primary" title="عرض في الطلبات">
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
            </x-admin.dashboard-panel>
        </div>
    </div>
@endsection
