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
                <x-partials.admin-logo :height="32" :logo-path="asset('static/brand/logos/logo-light.svg')" />
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

        @can('dashboard.main')
            <li class="menu-item @if (Route::is('admin.dashboard')) active open @endif">
                <a href="{{ route('admin.dashboard') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-layout-dashboard"></i>
                    <div>لوحة القيادة</div>
                </a>
            </li>
        @endcan

        @canany(['clients.view', 'companies.view'])
            <li class="menu-item @if (Route::is('admin.clients.*')) active open @endif">
                <a href="{{ route('admin.clients.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-building"></i>
                    <div>إدارة العملاء</div>
                </a>
            </li>
        @endcanany

        {{-- @can('auction_templates.view')
            <li class="menu-item @if (Route::is('admin.auction-templates.*')) active open @endif">
                <a href="{{ route('admin.auction-templates.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-template"></i>
                    <div>قوالب إعلانية</div>
                </a>
            </li>
        @endcan --}}

        @canany(['plans.view', 'coupons.view'])
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
        @endcanany

        @canany(['subscriptions.view', 'financial_transactions.view'])
            <li class="menu-header small">
                <span class="menu-header-text">الإشتراكات والفواتير</span>
            </li>

            @can('subscriptions.view')
                @php
                    $expiringCount = cache()->remember('admin:expiring_subscriptions_count', 60, function () {
                        return app(
                            \App\Services\Admin\SubscriptionManagement\SubscriptionManagementService::class,
                        )->getExpiringSoonCount();
                    });
                    $expiredCount = cache()->remember('admin:expired_subscriptions_count', 60, function () {
                        return \App\Models\Admin\Subscription\Subscription::whereIn(
                            'status',
                            [
                                \App\Enums\Admin\Subscriptions\SubscriptionStatus::EXPIRED,
                                \App\Enums\Admin\Subscriptions\SubscriptionStatus::CANCELLED,
                            ],
                        )->count();
                    });
                    $totalBadgeCount = $expiringCount + $expiredCount;
                @endphp
                <li class="menu-item @if (Route::is('admin.subscriptions.*') || Route::is('admin.subscriptionManage.*')) active open @endif">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons ti ti-file-certificate"></i>
                        <div>إدارة الاشتراكات</div>
                        <div class="badge bg-light text-primary rounded-pill ms-auto @if ($totalBadgeCount <= 0) d-none @endif"
                            data-sidebar-counter="subscriptions_total">{{ $totalBadgeCount }}</div>
                    </a>
                    <ul class="menu-sub">
                        <li class="menu-item @if (Route::is('admin.subscriptions.*')) active @endif">
                            <a href="{{ route('admin.subscriptions.index') }}" class="menu-link">
                                <div>جميع الإشتراكات</div>
                            </a>
                        </li>
                        <li class="menu-item @if (Route::is('admin.subscriptionManage.expiring')) active @endif">
                            <a href="{{ route('admin.subscriptionManage.expiring') }}" class="menu-link">
                                <div>تقارب على الانتهاء</div>
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if ($expiringCount <= 0) d-none @endif"
                                    data-sidebar-counter="subscriptions_expiring">{{ $expiringCount }}</div>
                            </a>
                        </li>
                        <li class="menu-item @if (Route::is('admin.subscriptionManage.expired')) active @endif">
                            <a href="{{ route('admin.subscriptionManage.expired') }}" class="menu-link">
                                <div>الاشتراكات المنتهية</div>
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if ($expiredCount <= 0) d-none @endif"
                                    data-sidebar-counter="subscriptions_expired">{{ $expiredCount }}</div>
                            </a>
                        </li>
                    </ul>
                </li>
            @endcan

            @can('financial_transactions.view')
                <li class="menu-item @if (Route::is('admin.bills.*')) active open @endif">
                    <a href="{{ route('admin.bills.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-credit-card"></i>
                        <div>الفواتير</div>
                    </a>
                </li>
            @endcan
        @endcanany

        @canany(['tickets.view', 'contact_requests.view', 'trial_requests.view'])
            <li class="menu-header small">
                <span class="menu-header-text">الطلبات والدعم</span>
            </li>

            @can('contact_requests.view')
                @php
                    $pendingContactRequestsCount = cache()->remember(
                        'admin:pending_contact_requests_count',
                        60,
                        function () {
                            return \App\Models\Admin\PublicRequests\ContactRequest::where(
                                'status',
                                \App\Enums\Admin\PublicRequests\ContactRequestStatus::PENDING,
                            )->count();
                        },
                    );
                @endphp
                <li class="menu-item @if (Route::is('admin.contact-requests.*')) active open @endif">
                    <a href="{{ route('admin.contact-requests.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-mail"></i>
                        <div>طلبات التواصل</div>
                        <div class="badge bg-light text-primary rounded-pill ms-auto @if ($pendingContactRequestsCount <= 0) d-none @endif"
                            data-sidebar-counter="contact_requests_pending">{{ $pendingContactRequestsCount }}</div>
                    </a>
                </li>
            @endcan

            @can('trial_requests.view')
                @php
                    $pendingTrialRequestsCount = cache()->remember(
                        'admin:pending_trial_requests_count',
                        60,
                        function () {
                            return \App\Models\Admin\PublicRequests\TrialRequest::where(
                                'status',
                                \App\Enums\Admin\PublicRequests\TrialRequestStatus::PENDING,
                            )->count();
                        },
                    );
                @endphp
                <li class="menu-item @if (Route::is('admin.trial-requests.*')) active open @endif">
                    <a href="{{ route('admin.trial-requests.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-clock"></i>
                        <div>طلبات التجربة</div>
                        <div class="badge bg-light text-primary rounded-pill ms-auto @if ($pendingTrialRequestsCount <= 0) d-none @endif"
                            data-sidebar-counter="trial_requests_pending">{{ $pendingTrialRequestsCount }}</div>
                    </a>
                </li>
            @endcan

            @can('tickets.view')
                @php
                    $openTicketsCount = cache()->remember('admin:open_tickets_count', 60, function () {
                        return \App\Models\Admin\Tickets\Ticket::whereIn('status', ['open', 'reopened'])->count();
                    });
                @endphp
                <li class="menu-item @if (Route::is('admin.tickets.*')) active open @endif">
                    <a href="{{ route('admin.tickets.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-headset"></i>
                        <div>الدعم الفني</div>
                        <div class="badge bg-light text-primary rounded-pill ms-auto @if ($openTicketsCount <= 0) d-none @endif"
                            data-sidebar-counter="tickets_open">{{ $openTicketsCount }}</div>
                    </a>
                </li>
            @endcan
        @endcanany

        {{-- الأرشيف --}}
        @canany(['users.view', 'companies.view', 'plans.view', 'coupons.view', 'financial_transactions.view'])
            @php
                $adminArchiveCounts = cache()->remember('admin:archive_sidebar_counts', 60, function () {
                    try {
                        return [
                            'users' => \Illuminate\Support\Facades\Schema::hasTable('admin_users') ? \App\Models\Admin\AdminMangment\Users\AdminUser::onlyTrashed()->count() : 0,
                            'tenants' => \Illuminate\Support\Facades\Schema::hasTable('tenants') ? \App\Models\Admin\Tenants\Tenant::onlyTrashed()->count() : 0,
                            'plans' => \Illuminate\Support\Facades\Schema::hasTable('plans') ? \App\Models\Admin\Plans\Plan::onlyTrashed()->count() : 0,
                            'coupons' => \Illuminate\Support\Facades\Schema::hasTable('coupons') ? \App\Models\Admin\Coupons\Coupon::onlyTrashed()->count() : 0,
                            'bills' => \Illuminate\Support\Facades\Schema::hasTable('bills') ? \App\Models\Admin\Bills\Bill::onlyTrashed()->count() : 0,
                        ];
                    } catch (\Throwable) {
                        return ['users' => 0, 'tenants' => 0, 'plans' => 0, 'coupons' => 0, 'bills' => 0];
                    }
                });

                $adminUserCan = auth('admin')->user();
                $totalAdminArchiveCount = 0;
                if ($adminUserCan?->can('users.view')) {
                    $totalAdminArchiveCount += $adminArchiveCounts['users'] ?? 0;
                }
                if ($adminUserCan?->can('companies.view')) {
                    $totalAdminArchiveCount += $adminArchiveCounts['tenants'] ?? 0;
                }
                if ($adminUserCan?->can('plans.view')) {
                    $totalAdminArchiveCount += $adminArchiveCounts['plans'] ?? 0;
                }
                if ($adminUserCan?->can('coupons.view')) {
                    $totalAdminArchiveCount += $adminArchiveCounts['coupons'] ?? 0;
                }
                if ($adminUserCan?->can('financial_transactions.view')) {
                    $totalAdminArchiveCount += $adminArchiveCounts['bills'] ?? 0;
                }
            @endphp
            <li class="menu-header small">
                <span class="menu-header-text">الأرشيف</span>
            </li>
            <li class="menu-item @if (Route::is('admin.archives.*')) active open @endif">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons ti ti-archive"></i>
                    <div>الأرشيف</div>
                    <div class="badge bg-light text-primary rounded-pill ms-auto @if ($totalAdminArchiveCount <= 0) d-none @endif"
                        data-sidebar-counter="archive_admin_total">{{ $totalAdminArchiveCount }}</div>
                </a>
                <ul class="menu-sub">
                    @can('users.view')
                        <li class="menu-item @if (Route::is('admin.archives.index')) active @endif">
                            <a href="{{ route('admin.archives.index') }}" class="menu-link">
                                <div>المستخدمين</div>
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if (($adminArchiveCounts['users'] ?? 0) <= 0) d-none @endif"
                                    data-sidebar-counter="archive_admin_users">{{ $adminArchiveCounts['users'] ?? 0 }}</div>
                            </a>
                        </li>
                    @endcan
                    @can('companies.view')
                        <li class="menu-item @if (Route::is('admin.archives.tenants.*')) active @endif">
                            <a href="{{ route('admin.archives.tenants.index') }}" class="menu-link">
                                <div>العملاء</div>
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if (($adminArchiveCounts['tenants'] ?? 0) <= 0) d-none @endif"
                                    data-sidebar-counter="archive_admin_tenants">{{ $adminArchiveCounts['tenants'] ?? 0 }}</div>
                            </a>
                        </li>
                    @endcan
                    @can('plans.view')
                        <li class="menu-item @if (Route::is('admin.archives.plans.*')) active @endif">
                            <a href="{{ route('admin.archives.plans.index') }}" class="menu-link">
                                <div>الباقات</div>
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if (($adminArchiveCounts['plans'] ?? 0) <= 0) d-none @endif"
                                    data-sidebar-counter="archive_admin_plans">{{ $adminArchiveCounts['plans'] ?? 0 }}</div>
                            </a>
                        </li>
                    @endcan
                    @can('coupons.view')
                        <li class="menu-item @if (Route::is('admin.archives.coupons.*')) active @endif">
                            <a href="{{ route('admin.archives.coupons.index') }}" class="menu-link">
                                <div>الكوبونات</div>
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if (($adminArchiveCounts['coupons'] ?? 0) <= 0) d-none @endif"
                                    data-sidebar-counter="archive_admin_coupons">{{ $adminArchiveCounts['coupons'] ?? 0 }}</div>
                            </a>
                        </li>
                    @endcan
                    @can('financial_transactions.view')
                        <li class="menu-item @if (Route::is('admin.archives.bills.*')) active @endif">
                            <a href="{{ route('admin.archives.bills.index') }}" class="menu-link">
                                <div>الفواتير</div>
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if (($adminArchiveCounts['bills'] ?? 0) <= 0) d-none @endif"
                                    data-sidebar-counter="archive_admin_bills">{{ $adminArchiveCounts['bills'] ?? 0 }}</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcanany
    </ul>
</aside>
