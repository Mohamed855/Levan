@php
    use Illuminate\Support\Facades\Route;
    $configData = App\Helpers\Helpers::appClasses();
    use App\Helpers\Helpers;
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

    <!-- ! Hide app brand if navbar-full -->
    @if (!isset($navbarFull))
        <div class="app-brand demo">
            <a href="{{ route('admin.dashboard') }}" class="app-brand-link">
                <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 30, 'logoPath' => asset('img/logo.svg')])</span>
                <span class="app-brand-text demo menu-text fw-bold">
                    {{ App\Helpers\Admin\Settings\AdminSettingsHelper::get('logo_text') }}
                </span>
            </a>

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="ti menu-toggle-icon d-none d-xl-block align-middle"></i>
                <i class="ti ti-x d-block d-xl-none ti-md align-middle"></i>
            </a>
        </div>
    @endif

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">

        <li class="menu-header small">
            <span class="menu-header-text">الرئيسية</span>
        </li>

        @can('dashboard.main')
            <li class="menu-item @if (Route::is('admin.dashboard')) active open @endif">
                <a href="{{ route('admin.dashboard') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-layout-dashboard"></i>
                    <div>لوحة القيادة</div>
                </a>
            </li>
        @endcan

        @can('users.view')
            <li class="menu-item @if (Route::is('admin.users.*')) active open @endif">
                <a href="{{ route('admin.users.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-user-cog"></i>
                    <div>إدارة المستخدمين</div>
                </a>
            </li>
        @endcan

        @can('subscriptions.view')
            <li class="menu-item @if (Route::is('admin.companies.*')) active open @endif">
                <a href="{{ route('admin.companies.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-building"></i>
                    <div>إدارة الشركات</div>
                </a>
            </li>
        @endcan

        @can('activity_log.view')
            <li class="menu-item @if (Route::is('admin.activity-logs.*')) active open @endif">
                <a href="{{ route('admin.activity-logs.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-history"></i>
                    <div>سجل النشاطات</div>
                </a>
            </li>
        @endcan

        <li class="menu-header small">
            <span class="menu-header-text">الباقات والعروض</span>
        </li>

        @can('plans.view')
            <li class="menu-item @if (Route::is('admin.plans.*')) active open @endif">
                <a href="{{ route('admin.plans.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-package"></i>
                    <div>إدارة الباقات</div>
                </a>
            </li>
        @endcan


        @can('coupons.view')
            <li class="menu-item @if (Route::is('admin.coupons.*')) active open @endif">
                <a href="{{ route('admin.coupons.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-ticket"></i>
                    <div>إدارة الكوبونات</div>
                </a>
            </li>
        @endcan

        <li class="menu-header small">
            <span class="menu-header-text">الإشتراكات والمالية</span>
        </li>

        {{-- @can('clients.view')
        <li class="menu-item @if (Route::is('admin.tenantManage.*')) active open @endif">
            <a href="{{ route('admin.tenantManage.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-users"></i>
                <div>إدارة العملاء</div>

            </a>
        </li>
        @endcan --}}

        @can('subscriptions.view')
            @php
                $expiringCount = cache()->remember('admin:expiring_subscriptions_count', 60, function () {
                    return app(
                        \App\Services\Admin\SubscriptionManagement\SubscriptionManagementService::class,
                    )->getExpiringSoonCount();
                });
                $pendingCount = cache()->remember('admin:pending_subscriptions_count', 60, function () {
                    return \App\Models\Admin\Subscription\Subscription::where(
                        'status',
                        \App\Enums\Admin\Subscriptions\SubscriptionStatus::PENDING,
                    )->count();
                });
                $expiredCount = cache()->remember('admin:expired_subscriptions_count', 60, function () {
                    return \App\Models\Admin\Subscription\Subscription::where(
                        'status',
                        \App\Enums\Admin\Subscriptions\SubscriptionStatus::EXPIRED,
                    )->count();
                });
                $totalBadgeCount = $expiringCount + $pendingCount + $expiredCount;
            @endphp
            <li class="menu-item @if (Route::is('admin.subscriptionManage.*')) active open @endif">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons ti ti-timeline-event"></i>
                    <div>إدارة الإشتراكات</div>
                    @if ($totalBadgeCount > 0)
                        <div class="badge bg-label-danger rounded-pill ms-auto">{{ $totalBadgeCount }}</div>
                    @endif
                </a>
                <ul class="menu-sub">
                    <li class="menu-item @if (Route::is('admin.subscriptionManage.pending')) active @endif">
                        <a href="{{ route('admin.subscriptionManage.pending') }}" class="menu-link">
                            <div>الطلبات</div>
                            @if ($pendingCount > 0)
                                <div class="badge bg-warning rounded-pill ms-auto">{{ $pendingCount }}</div>
                            @endif
                        </a>
                    </li>
                    <li class="menu-item @if (Route::is('admin.subscriptionManage.expiring')) active @endif">
                        <a href="{{ route('admin.subscriptionManage.expiring') }}" class="menu-link">
                            <div>تقارب على الانتهاء</div>
                            @if ($expiringCount > 0)
                                <div class="badge bg-danger rounded-pill ms-auto">{{ $expiringCount }}</div>
                            @endif
                        </a>
                    </li>
                    <li class="menu-item @if (Route::is('admin.subscriptionManage.expired')) active @endif">
                        <a href="{{ route('admin.subscriptionManage.expired') }}" class="menu-link">
                            <div>الاشتراكات المنتهية</div>
                            @if ($expiredCount > 0)
                                <div class="badge bg-secondary rounded-pill ms-auto">{{ $expiredCount }}</div>
                            @endif
                        </a>
                    </li>
                </ul>
            </li>
        @endcan

        @can('financial_transactions.view')
            <li class="menu-item @if (Route::is('admin.bills.*')) active open @endif">
                <a href="{{ route('admin.bills.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-credit-card"></i>
                    <div>إدارة المعاملات المالية</div>
                </a>
            </li>
        @endcan

        <li class="menu-header small">
            <span class="menu-header-text">الدعم الفني والتذاكر</span>
        </li>

        @can('tickets.view')
            <li class="menu-item @if (Route::is('admin.tickets.*')) active open @endif">
                <a href="{{ route('admin.tickets.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-messages"></i>
                    <div>إدارة التذاكر</div>
                </a>
            </li>
        @endcan

        <li class="menu-header small">
            <span class="menu-header-text">المحذوفات</span>
        </li>

        <li class="menu-item @if (Route::is('admin.archives.*')) active open @endif">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons ti ti-trash"></i>
                <div>البيانات المحذوفة</div>
            </a>
            <ul class="menu-sub">

                <li class="menu-item @if (Route::is('admin.archives.index')) active @endif">
                    <a href="{{ route('admin.archives.index') }}" class="menu-link">
                        <div>المستخدمون</div>
                    </a>
                </li>

                <li class="menu-item @if (Route::is('admin.archives.plans.*')) active @endif">
                    <a href="{{ route('admin.archives.plans.index') }}" class="menu-link">
                        <div>الباقات</div>
                    </a>
                </li>

                <li class="menu-item @if (Route::is('admin.archives.coupons.*')) active @endif">
                    <a href="{{ route('admin.archives.coupons.index') }}" class="menu-link">
                        <div>الكوبونات</div>
                    </a>
                </li>

                <li class="menu-item @if (Route::is('admin.archives.tenants.*')) active @endif">
                    <a href="{{ route('admin.archives.tenants.index') }}" class="menu-link">
                        <div>العملاء</div>
                    </a>
                </li>

                <li class="menu-item @if (Route::is('admin.archives.subscriptions.*')) active @endif">
                    <a href="{{ route('admin.archives.subscriptions.index') }}" class="menu-link">
                        <div>الاشتراكات</div>
                    </a>
                </li>

                <li class="menu-item @if (Route::is('admin.archives.bills.*')) active @endif">
                    <a href="{{ route('admin.archives.bills.index') }}" class="menu-link">
                        <div>المعاملات المالية</div>
                    </a>
                </li>
            </ul>
        </li>


        <li class="menu-header small">
            <span class="menu-header-text">التطوير والإصدارات</span>
        </li>

        @can('versions.view')
            <li class="menu-item @if (Route::is('admin.versions.*')) active open @endif">
                <a href="{{ route('admin.versions.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-git-pull-request-draft"></i>
                    <div>إدارة الإصدارات</div>
                </a>
            </li>
        @endcan

        @can('version_testers.view')
            <li class="menu-item @if (Route::is('admin.version_testers.*')) active open @endif">
                <a href="{{ route('admin.version_testers.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-swipe"></i>
                    <div>فريق اختبار الإصدارات</div>
                </a>
            </li>
        @endcan
    </ul>
</aside>
