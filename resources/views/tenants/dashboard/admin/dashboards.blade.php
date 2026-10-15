@extends('tenants.layouts.layoutMaster')

@section('title', 'لوحة القيادة - التحليلات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/swiper/swiper.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/swiper/swiper.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/cards-advance.scss', 'resources/css/dataTable.css'])
    <style>
        .chart-setting-card {
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .chart-setting-card:hover {
            border-color: rgba(105, 108, 255, 0.15);
            transform: translateY(-2px);
        }

        .chart-setting-icon {
            width: 2.5rem;
            height: 2.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            background: rgba(105, 108, 255, 0.08);
            color: #696cff;
            font-weight: 700;
            font-size: 0.75rem;
        }
    </style>
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenants/dashboard/admin/daily-tasks.js', 'resources/assets/js/tenants/dashboard/admin/charts.js', 'resources/assets/js/tenants/dashboard/admin/slider.js', 'resources/assets/js/tenants/dashboard/admin/excellence.js', 'resources/assets/js/tenants/dashboard/admin/overdue-tasks.js', 'resources/assets/js/shared/chart/chart.js'])

    <script>
        window.routes = {
            upcomingFollowUps: "{{ tenant_route_name('dashboard.upcomingFollowUps') }}",
            dashboard: "{{ tenant_route_name('dashboard') }}",
            statistics: "{{ tenant_route_name('dashboard.statistics') }}",
            approvals: "{{ tenant_route_name('dashboard.approvals') }}",
            overdueTasks: "{{ tenant_route_name('dashboard.overdueTasks') }}",
            exportOverdueTasks: "{{ tenant_route_name('dashboard.exportOverdueTasks') }}",
            chartVisibilityUpdate: "{{ tenant_route_name('charSetting.update') }}" // 👈 جديد
        };

        window.assets = {
            arabicDataTable: "{{ asset('static/i18n/ar.json') }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
        window.dashboardData = {
            collectionsStats: {
                pending: {{ $collectionStats['pending'] }},
                approved: {{ $collectionStats['approved'] }},
                rejected: {{ $collectionStats['rejected'] }}
            },
            @if (count($targetVsAchievement) > 0)
                targetVsAchievement: @json($targetVsAchievement),
                totalAchievement: {{ round(collect($targetVsAchievement)->avg('achievement_percentage'), 0) }}
            @else
                targetVsAchievement: [],
                totalAchievement: 0
            @endif
        };

        window.excellenceData = {!! Js::from([
            'topPerformers' => $topPerformers ?? [],
            'performanceDistribution' => $performanceDistribution ?? [],
            'performanceTrend' => $performanceTrend ?? [],
        ]) !!};

        window.excellenceRoutes = {
            getData: "{{ tenant_route_name('dashboard.excellence') }}"
        };

        // 👇 Chart Visibility Toggle Handler
        (function() {
            const toggles = document.querySelectorAll('.js-chart-visibility-toggle');

            toggles.forEach(el => {
                el.addEventListener('change', async function() {
                    const key = this.dataset.key;
                    const value = this.checked ? 1 : 0;
                    const previousState = !this.checked;

                    this.disabled = true;

                    try {
                        const res = await fetch(window.routes.chartVisibilityUpdate, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': window.csrfToken
                            },
                            body: JSON.stringify({
                                key,
                                value
                            })
                        });

                        if (!res.ok) throw new Error('فشل الحفظ');

                        const data = await res.json();
                        toastr.success(data.message || 'تم حفظ الإعداد بنجاح');


                    } catch (e) {
                        this.checked = previousState;
                        toastr.error(e.message || 'حدث خطأ أثناء الحفظ');
                    } finally {
                        this.disabled = false;
                    }
                });
            });
        })();
    </script>
@endsection

@section('content')

    @php
        $chartSettings = $chartSettings ?? [];
        $isOn = fn($k) => (int) ($chartSettings[$k] ?? 1) === 1;
    @endphp

    <div class="row g-4 mb-3">
        @php
            // ✅ المنطق الصحيح
            $user = Auth::user();
            $isTeamLeader = $user->canManageTeam();
        @endphp
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body p-0">
                    <div class="swiper-container swiper-container-horizontal swiper swiper-card-advance-bg"
                        id="swiper-with-pagination-cards">
                        <div class="swiper-wrapper">

                            @php
                                use App\Helpers\Tenants\Settings\SettingsHelper;

                                // Original database-backed tenant logo lookup preserved:
                                // $logoImage = SettingsHelper::get('image');
                                // $logoPath = tasset($logoImage);
                                $logoPath = asset('static/brand/logos/logo-light.svg');
                            @endphp
                            @if (!$isTeamLeader)
                                <!-- 1) الشركات -->
                                <div class="swiper-slide">
                                    <div class="row">
                                        <div class="col-12">
                                            <h5 class="text-white mb-0">الشركات</h5>
                                            <h6 class="text-white mb-1">مؤشرات الشركات</h6>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-7 col-md-9 col-12 order-2 order-md-1 pt-md-9">
                                                <h6 class="text-white mt-0 mt-md-3 mb-4">القراءات</h6>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <ul class="list-unstyled mb-0">
                                                            <li class="d-flex mb-4 align-items-center">
                                                                <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                    {{ $companyStats['total'] ?? 0 }}</p>
                                                                <p class="mb-0">إجمالي الشركات</p>
                                                            </li>
                                                            <li class="d-flex align-items-center">
                                                                <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                    {{ $companyStats['active'] ?? 0 }}</p>
                                                                <p class="mb-0">شركات نشطة</p>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-6">
                                                        <ul class="list-unstyled mb-0">
                                                            <li class="d-flex mb-4 align-items-center">
                                                                <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                    {{ $companyStats['recent'] ?? 0 }}</p>
                                                                <p class="mb-0">جديدة (30 يومًا)</p>
                                                            </li>
                                                            <li class="d-flex align-items-center">
                                                                <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                    {{ $companyStats['without_delegates'] ?? 0 }}</p>
                                                                <p class="mb-0">شركات دون مفوضين</p>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                class="col-lg-5 col-md-3 col-12 order-1 order-md-2 my-4 my-md-0 text-center">
                                                <img src="{{ $logoPath }}" alt="الشركات" height="150"
                                                    class="card-website-analytics-img">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2) العقود -->
                                <div class="swiper-slide">
                                    <div class="row">
                                        <div class="col-12">
                                            <h5 class="text-white mb-0">العقود</h5>
                                            <small>مؤشرات العقود</small>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-7 col-md-9 col-12 order-2 order-md-1 pt-md-9">
                                                <h6 class="text-white mt-0 mt-md-3 mb-4">القراءات</h6>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <ul class="list-unstyled mb-0">
                                                            <li class="d-flex mb-4 align-items-center">
                                                                <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                    {{ $contractStats['total'] ?? 0 }}</p>
                                                                <p class="mb-0">إجمالي العقود</p>
                                                            </li>
                                                            <li class="d-flex align-items-center">
                                                                <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                    {{ $contractStats['active'] ?? 0 }}</p>
                                                                <p class="mb-0">عقود سارية</p>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-6">
                                                        <ul class="list-unstyled mb-0">
                                                            <li class="d-flex mb-4 align-items-center">
                                                                <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                    {{ $contractStats['expiring_soon'] ?? 0 }}</p>
                                                                <p class="mb-0">قريبة الانتهاء</p>
                                                            </li>
                                                            <li class="d-flex align-items-center">
                                                                <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                    {{ $contractStats['expired'] ?? 0 }}</p>
                                                                <p class="mb-0">منتهية</p>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                class="col-lg-5 col-md-3 col-12 order-1 order-md-2 my-4 my-md-0 text-center">
                                                <img src="{{ $logoPath }}" alt="العقود" height="150"
                                                    class="card-website-analytics-img">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3) المحافظ -->
                                <div class="swiper-slide">
                                    <div class="row">
                                        <div class="col-12">
                                            <h5 class="text-white mb-0">المحافظ</h5>
                                            <small>مؤشرات المحافظ</small>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-7 col-md-9 col-12 order-2 order-md-1 pt-md-9">
                                                <h6 class="text-white mt-0 mt-md-3 mb-4">القراءات</h6>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <ul class="list-unstyled mb-0">
                                                            <li class="d-flex mb-4 align-items-center">
                                                                <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                    {{ $portfolioStats['total'] ?? 0 }}</p>
                                                                <p class="mb-0">إجمالي المحافظ</p>
                                                            </li>
                                                            <li class="d-flex align-items-center">
                                                                <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                    {{ $portfolioStats['active'] ?? 0 }}</p>
                                                                <p class="mb-0">محافظ نشطة</p>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-6">
                                                        <ul class="list-unstyled mb-0">
                                                            <li class="d-flex mb-4 align-items-center">
                                                                <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                    {{ $portfolioStats['recent'] ?? 0 }}</p>
                                                                <p class="mb-0">جديدة (30 يومًا)</p>
                                                            </li>
                                                            <li class="d-flex align-items-center">
                                                                <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                    {{ $portfolioStats['inactive'] ?? 0 }}</p>
                                                                <p class="mb-0">غير نشطة</p>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                class="col-lg-5 col-md-3 col-12 order-1 order-md-2 my-4 my-md-0 text-center">
                                                <img src="{{ $logoPath }}" alt="المحافظ" height="150"
                                                    class="card-website-analytics-img">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <!-- 4) العملاء -->
                            <div class="swiper-slide">
                                <div class="row">
                                    <div class="col-12">
                                        <h5 class="text-white mb-0">العملاء</h5>
                                        <small> مؤشرات العمـلاء</small>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-7 col-md-9 col-12 order-2 order-md-1 pt-md-9">
                                            <h6 class="text-white mt-0 mt-md-3 mb-4">القراءات</h6>
                                            <div class="row">
                                                <div class="col-6">
                                                    <ul class="list-unstyled mb-0">
                                                        <li class="d-flex mb-4 align-items-center">
                                                            <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                {{ $customerStats['total'] ?? 0 }}</p>
                                                            <p class="mb-0">إجمالي العملاء</p>
                                                        </li>
                                                        <li class="d-flex align-items-center">
                                                            <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                {{ $customerStats['recent'] ?? 0 }}</p>
                                                            <p class="mb-0">جدد (30 يومًا)</p>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <div class="col-6">
                                                    <ul class="list-unstyled mb-0">
                                                        <li class="d-flex mb-4 align-items-center">
                                                            <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                {{ $customerStats['paid_full_this_month'] ?? 0 }}</p>
                                                            <p class="mb-0">سداد كامل (هذا الشهر)</p>
                                                        </li>
                                                        <li class="d-flex align-items-center">
                                                            <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                {{ $customerStats['with_debt'] ?? 0 }}</p>
                                                            <p class="mb-0">عملاء بمديونيات</p>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-5 col-md-3 col-12 order-1 order-md-2 my-4 my-md-0 text-center">
                                            <img src="{{ $logoPath }}" alt="العملاء" height="150"
                                                class="card-website-analytics-img">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 5) التحصيلات (الشهر الحالي) -->
                            <div class="swiper-slide">
                                <div class="row">
                                    <div class="col-12">
                                        <h5 class="text-white mb-0">التحصيلات</h5>
                                        <small> مؤشرات التحصيلات - هذا الشهر </small>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-7 col-md-9 col-12 order-2 order-md-1 pt-md-9">
                                            <h6 class="text-white mt-0 mt-md-3 mb-4">القراءات</h6>
                                            <div class="row">
                                                <div class="col-6">
                                                    <ul class="list-unstyled mb-0">
                                                        <li class="d-flex mb-4 align-items-center">
                                                            <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                {{ $collectionStatsMonth['total'] ?? 0 }}</p>
                                                            <p class="mb-0">إجمالي العمليات</p>
                                                        </li>
                                                        <li class="d-flex align-items-center">
                                                            <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                {{ $collectionStatsMonth['approved'] ?? 0 }}</p>
                                                            <p class="mb-0">المعتمدة</p>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <div class="col-6">
                                                    <ul class="list-unstyled mb-0">
                                                        <li class="d-flex mb-4 align-items-center">
                                                            <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                {{ $collectionStatsMonth['pending'] ?? 0 }}</p>
                                                            <p class="mb-0">المعلّقة</p>
                                                        </li>
                                                        <li class="d-flex align-items-center">
                                                            <p class="mb-0 fw-medium me-2 website-analytics-text-bg">
                                                                {{ $collectionStatsMonth['rejected'] ?? 0 }}</p>
                                                            <p class="mb-0">المرفوضة</p>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-5 col-md-3 col-12 order-1 order-md-2 my-4 my-md-0 text-center">
                                            <img src="{{ $logoPath }}" alt="التحصيلات" height="150"
                                                class="card-website-analytics-img">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-0 text-body">الإحصائيات</p>
                    </div>
                    <select id="statisticsPeriodFilter" class="form-select form-select-sm select2" style="width: 150px;">
                        <x-form.controls.enum-options :enum="\App\Enums\Shared\DashboardPeriod::class" />
                    </select>
                </div>
                <div class="mb-4"></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-4 text-start">
                            <div class="d-flex gap-2 align-items-center mb-2">
                                <span class="badge bg-label-primary p-1 rounded"
                                    style="background-color: #372632 !important;">
                                    <i class="ti ti-phone-call ti-sm text-white"></i>
                                </span>
                                <p class="mb-0" style="font-size: small;">المتابعات</p>
                            </div>
                            <h5 class="mb-0 pt-1" id="followupsCountTop">0</h5>
                        </div>
                        <div class="col-4">
                            <div class="divider divider-vertical">
                                <div class="divider-text">
                                    <span class="badge-divider-bg bg-label-primary fw-bold">VS</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="d-flex gap-2 justify-content-end align-items-center mb-2">
                                <p class="mb-0" style="font-size: small;">التحصيلات</p>
                                <span class="badge bg-label-primary p-1 rounded"
                                    style="background-color: #4cc1bb !important;">
                                    <i class="ti ti-cash ti-sm text-white"></i>
                                </span>
                            </div>
                            <h5 class="mb-0 pt-1" id="collectionsCountTop">0</h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-6">
                        <div class="progress w-100" style="height: 1rem; border-radius: 0.25rem;">
                            <div class="progress-bar d-flex align-items-center justify-content-center position-relative"
                                id="followupsProgressSegment" role="progressbar"
                                style="width: 0%; background-color: #372632; opacity: 0.5;" aria-valuenow="0"
                                aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="top"
                                title="عدد المتابعات: 0">
                                <span id="followupsBarText"
                                    style="font-size: 0.7rem; color: #ffffff; font-weight: bold; text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.7);"></span>
                            </div>
                            <div class="progress-bar d-flex align-items-center justify-content-center position-relative"
                                id="collectionsProgressSegment" style="width: 0%; background-color: #4cc1bb;"
                                role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="مبلغ التحصيلات: 0 ريال">
                                <span id="collectionsBarText"
                                    style="font-size: 0.7rem; color: #ffffff; font-weight: bold; text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.7);"></span>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-2">
                        <div class="d-inline-block">
                            <span class="d-inline-block"
                                style="width: 0.5rem; height: 0.5rem; border-radius: 50%; background-color: #4cc1bb;"></span>
                            <span class="ms-1" style="font-size: 0.65rem; color: #6c757d;"
                                id="collectionsSmallNumber">0
                                ريال</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <p class="mb-0 text-body">الاعتمادات</p>
                    </div>
                    <select id="approvalsPeriodFilter" class="form-select form-select-sm select2" style="width: 150px;">
                        <x-form.controls.enum-options :enum="\App\Enums\Shared\DashboardPeriod::class" />
                    </select>
                </div>
                <div class="mb-4"></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-4 text-start">
                            <div class="d-flex gap-2 align-items-center mb-2">
                                <span class="badge bg-label-primary p-1 rounded"
                                    style="background-color: #372632 !important;">
                                    <i class="ti ti-clock ti-sm text-white"></i>
                                </span>
                                <p class="mb-0" style="font-size: small;">قيد الانتظار</p>
                            </div>
                            <h5 class="mb-0 pt-1" id="pendingCountTop">0</h5>
                        </div>
                        <div class="col-4">
                            <div class="divider divider-vertical">
                                <div class="divider-text">
                                    <span class="badge-divider-bg bg-label-primary fw-bold">VS</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="d-flex gap-2 justify-content-end align-items-center mb-2">
                                <p class="mb-0" style="font-size: small;">المعتمدة</p>
                                <span class="badge bg-label-primary p-1 rounded"
                                    style="background-color: #4cc1bb !important;">
                                    <i class="ti ti-check ti-sm text-white"></i>
                                </span>
                            </div>
                            <h5 class="mb-0 pt-1" id="approvedCountTop">0</h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-6">
                        <div class="progress w-100" style="height: 1rem; border-radius: 0.25rem;">
                            <div class="progress-bar d-flex align-items-center justify-content-center position-relative"
                                id="pendingProgressSegment" role="progressbar"
                                style="width: 0%; background-color: #372632; opacity: 0.5;" aria-valuenow="0"
                                aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="top"
                                title="قيد الانتظار: 0">
                                <span id="pendingBarText"
                                    style="font-size: 0.7rem; color: #ffffff; font-weight: bold; text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.7);"></span>
                            </div>
                            <div class="progress-bar d-flex align-items-center justify-content-center position-relative"
                                id="approvedProgressSegment" style="width: 0%; background-color: #4cc1bb;"
                                role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="المعتمد: 0">
                                <span id="approvedBarText"
                                    style="font-size: 0.7rem; color: #ffffff; font-weight: bold; text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.7);"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
