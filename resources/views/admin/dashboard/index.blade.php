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
    @vite(['resources/css/dataTable.css'])
@endsection

@section('page-script')
    <script>
        window.dashboardData = {
            monthlyCustomers: @json($monthlyCustomers),
            topCustomers: @json($topCustomersByUsers),
            monthlyRevenue: @json($monthlyRevenue),
            subscriptionStatus: @json($subscriptionStatus),
            userUsage: @json($userUsage),
            couponUsage: @json($couponUsage)
        };
    </script>

    @vite(['resources/assets/js/admin/dashboard/index.js', 'resources/assets/js/shared/chart/chart.js'])
@endsection

@section('content')
    <!--- الاحصائيات -->
    <div class="row g-3 mb-4">
        <!-- بطاقات الإحصائيات -->
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي الإشتراكات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($kpis['active_subscriptions']) }}</h4>
                            </div>
                            <small class="mb-0">إجمالي جميع الإشتراكات</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-ticket ti-26px"></i>

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
                            <span class="text-heading">الإيرادات الشهرية (MRR)</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($kpis['mrr'], 0) }}</h4>
                            </div>
                            <small class="mb-0"> إجمالي الإيرادات الشهرية </small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-discount-check ti-26px"></i>
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
                            <span class="text-heading">مستخدمين مسند</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($kpis['total_users']) }}</h4>
                            </div>
                            <small class="mb-0"> إجمالي المستخدمين</small>

                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-discount-off ti-26px"></i>

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
                            <span class="text-heading">عدد الكوبونات النشطة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($kpis['active_coupons']) }}</h4>
                            </div>
                            <small class="mb-0">إجمالي الكوبونات النشطة</small>

                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-discount-off ti-26px"></i>

                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!--- المخططات -->
    <div class="row g-3 mb-4">
        {{-- Chart 1: العملاء الجدد (لون برتقالي) --}}
        <div class="col-xl-6 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="ti ti-calendar-stats" style="color: #ff9800;"></i>
                        العملاء الجدد شهرياً
                    </h5>
                </div>
                <div class="card-body">
                    <div class="container" style="position: relative; height: 320px;">
                        <canvas id="monthlyCustomersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart 2: أعلى 10 عملاء (لون أصفر) --}}
        <div class="col-xl-6 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="ti ti-award" style="color: #ffc107;"></i>
                        أعلى 10 عملاء من حيث المستخدمين
                    </h5>
                </div>
                <div class="card-body">
                    <div class="container" style="position: relative; height: 320px;">
                        <canvas id="topCustomersChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart 3: الإيرادات الشهرية --}}
        <div class="col-xl-6 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="ti ti-line" style="color: #28a745;"></i>
                        الإيرادات الشهرية
                    </h5>
                </div>
                <div class="card-body">
                    <div class="container" style="position: relative; height: 320px;">
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart 4: توزيع الاشتراكات --}}
        <div class="col-xl-6 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="ti ti-donut" style="color: #17a2b8;"></i>
                        توزيع حالات الاشتراكات
                    </h5>
                </div>
                <div class="card-body">
                    <div class="container" style="position: relative; height: 320px;">
                        <canvas id="subscriptionStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart 5: استخدام المستخدمين --}}
        <div class="col-xl-6 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="ti ti-users" style="color: var(--primary-color);"></i>
                        استخدام المستخدمين (أعلى 10)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="container" style="position: relative; height: 320px;">
                        <canvas id="userUsageChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart 6: الكوبونات (لون بنفسجي) --}}
        <div class="col-xl-6 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="ti ti-discount" style="color: #9c27b0;"></i>
                        أكثر 10 كوبونات استخداماً
                    </h5>
                </div>
                <div class="card-body">
                    <div class="container" style="position: relative; height: 320px;">
                        <canvas id="couponUsageChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--- الجداول -->
    <div class="row g-3">
        <div class="col-12">
            <div id="section-Overdue_tasks">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h5 class="card-title"> التجديدات القادمة (30 يوم)</h5>
                            </div>
                        </div>

                        <div class="card-body pt-3">
                            <div class="table-responsive">
                                <table id="overdue-tasks-table"
                                    class="table table-striped table-hover table-bordered nowrap" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th>العميل</th>
                                            <th>الاشتراك</th>
                                            <th>المبلغ</th>
                                            <th>التاريخ</th>
                                            <th>المتبقي</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($upcomingRenewals as $renewal)
                                            <tr>
                                                <td><strong>{{ $renewal['tenant'] }}</strong></td>
                                                <td>{{ $renewal['subscription'] }}</td>
                                                <td><strong style="color: #28a745;">{{ $renewal['amount'] }} ر.س</strong>
                                                </td>
                                                <td>{{ $renewal['renewal_date'] }}</td>
                                                <td>
                                                    <span class="status-badge status-pending">
                                                        {{ $renewal['days_until'] }} يوم
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="no-data text-center">
                                                    <i class="ti ti-inbox"></i>
                                                    <p>لا توجد تجديدات قادمة</p>
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
        </div>

        <div class="col-12">
            <div id="section-Overdue_tasks">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h5 class="card-title"> اشتراكات على وشك الانتهاء</h5>
                            </div>
                        </div>

                        <div class="card-body pt-3">
                            <div class="table-responsive">
                                <table id="overdue-tasks-table"
                                    class="table table-striped table-hover table-bordered nowrap" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th>العميل</th>
                                            <th>الاشتراك</th>
                                            <th>تاريخ الانتهاء</th>
                                            <th>المتبقي</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($expiringSubscriptions as $expiring)
                                            <tr>
                                                <td><strong>{{ $expiring['tenant'] }}</strong></td>
                                                <td>{{ $expiring['subscription'] }}</td>
                                                <td>{{ $expiring['expires_at'] }}</td>
                                                <td>
                                                    <span class="status-badge status-expired">
                                                        {{ $expiring['days_left'] }} يوم
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="no-data text-center">
                                                    <i class="ti ti-inbox"></i>
                                                    <p>لا توجد اشتراكات على وشك الانتهاء</p>
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
        </div>

        <div class="col-12">
            <div id="section-Overdue_tasks">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h5 class="card-title"> أسعار امتدادات النطاقات</h5>
                            </div>
                        </div>

                        <div class="card-body pt-3">
                            <div class="table-responsive">
                                <table id="overdue-tasks-table"
                                    class="table table-striped table-hover table-bordered nowrap" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th>النطاق</th>
                                            <th>السعر</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($controlDomains as $domain)
                                            <tr>
                                                <td>{{ $domain['extension'] }}</td>
                                                <td><strong style="color: #28a745;">{{ $domain['price'] }} ر.س</strong>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-active">
                                                        {{ $domain['status'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="no-data text-center">
                                                    <i class="ti ti-inbox"></i>
                                                    <p>لا توجد امتدادات مسجلة</p>
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
        </div>

        <div class="col-12">
            <div id="section-Overdue_tasks">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h5 class="card-title"> النطاقات المسجلة حديثاً</h5>
                            </div>
                        </div>

                        <div class="card-body pt-3">
                            <div class="table-responsive">
                                <table id="overdue-tasks-table"
                                    class="table table-striped table-hover table-bordered nowrap" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th>النطاق</th>
                                            <th>العميل</th>
                                            <th>التاريخ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentDomains as $domain)
                                            <tr>
                                                <td><strong>{{ $domain['domain'] }}</strong></td>
                                                <td>{{ $domain['tenant'] }}</td>
                                                <td>{{ $domain['created_at'] }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="no-data text-center">
                                                    <i class="ti ti-inbox"></i>
                                                    <p>لا توجد نطاقات مسجلة</p>
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
        </div>

        <div class="col-12">
            <div id="section-Overdue_tasks">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h5 class="card-title"> النطاقات المسجلة حديثاً</h5>
                            </div>
                        </div>

                        <div class="card-body pt-3">
                            <div class="table-responsive">
                                <table id="overdue-tasks-table"
                                    class="table table-striped table-hover table-bordered nowrap" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th>العميل</th>
                                            <th>رقم الاشتراك</th>
                                            <th>الخطة</th>
                                            <th>السعر</th>
                                            <th>الحالة</th>
                                            <th>تاريخ الإنشاء</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentSubscriptions as $recent)
                                            <tr>
                                                <td><strong>{{ $recent['tenant'] }}</strong></td>
                                                <td>{{ $recent['subscription'] }}</td>
                                                <td>{{ $recent['plan'] }}</td>
                                                <td><strong style="color: #28a745;">{{ $recent['price'] }} ر.س</strong>
                                                </td>
                                                <td>
                                                    @if ($recent['status'] == 'active')
                                                        <span class="status-badge status-active">نشط</span>
                                                    @elseif($recent['status'] == 'suspended')
                                                        <span class="status-badge status-pending">معلق</span>
                                                    @else
                                                        <span
                                                            class="status-badge status-expired">{{ $recent['status'] }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $recent['created_at'] }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="no-data text-center">
                                                    <i class="ti ti-inbox"></i>
                                                    <p>لا توجد اشتراكات حديثة</p>
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
        </div>

    </div>
@endsection
