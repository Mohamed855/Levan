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

        @can('roles.view')
            <li class="menu-item @if (Route::is('admin.roles.*')) active open @endif">
                <a href="{{ route('admin.roles.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-shield-lock"></i>
                    <div>إدارة الأدوار والصلاحيات</div>
                </a>
            </li>
        @endcan

        @if (auth()->user()?->can('clients.view') || auth()->user()?->can('companies.view'))
            <li class="menu-item @if (Route::is('admin.clients.*')) active open @endif">
                <a href="{{ route('admin.clients.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-building"></i>
                    <div>إدارة العملاء</div>
                </a>
            </li>
        @endif

        <li class="menu-header small">
            <span class="menu-header-text">القوالب</span>
        </li>

        @can('auction_templates.view')
            <li class="menu-item @if (Route::is('admin.auction-templates.*')) active open @endif">
                <a href="{{ route('admin.auction-templates.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-template"></i>
                    <div>قوالب المزادات</div>
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
            <li class="menu-item @if (Route::is('admin.subscriptions.*') || Route::is('admin.subscriptionManage.*')) active open @endif">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons ti ti-file-certificate"></i>
                    <div>إدارة الاشتراكات</div>
                    @if ($totalBadgeCount > 0)
                        <div class="badge bg-label-warning rounded-pill ms-auto">{{ $totalBadgeCount }}</div>
                    @endif
                </a>
                <ul class="menu-sub">
                    <li class="menu-item @if (Route::is('admin.subscriptions.*')) active @endif">
                        <a href="{{ route('admin.subscriptions.index') }}" class="menu-link">
                            <div>جميع الإشتراكات</div>
                            @if ($pendingCount > 0)
                                <div class="badge bg-label-warning rounded-pill ms-auto">{{ $pendingCount }}</div>
                            @endif
                        </a>
                    </li>
                    <li class="menu-item @if (Route::is('admin.subscriptionManage.pending')) active @endif">
                        <a href="{{ route('admin.subscriptionManage.pending') }}" class="menu-link">
                            <div>الطلبات</div>
                            @if ($pendingCount > 0)
                                <div class="badge bg-label-warning rounded-pill ms-auto">{{ $pendingCount }}</div>
                            @endif
                        </a>
                    </li>
                    <li class="menu-item @if (Route::is('admin.subscriptionManage.expiring')) active @endif">
                        <a href="{{ route('admin.subscriptionManage.expiring') }}" class="menu-link">
                            <div>تقارب على الانتهاء</div>
                            @if ($expiringCount > 0)
                                <div class="badge bg-label-warning rounded-pill ms-auto">{{ $expiringCount }}</div>
                            @endif
                        </a>
                    </li>
                    <li class="menu-item @if (Route::is('admin.subscriptionManage.expired')) active @endif">
                        <a href="{{ route('admin.subscriptionManage.expired') }}" class="menu-link">
                            <div>الاشتراكات المنتهية</div>
                            @if ($expiredCount > 0)
                                <div class="badge bg-label-warning rounded-pill ms-auto">{{ $expiredCount }}</div>
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
            <span class="menu-header-text">الدعم والطلبات</span>
        </li>

        @can('tickets.view')
            <li class="menu-item @if (Route::is('admin.tickets.*')) active open @endif">
                <a href="{{ route('admin.tickets.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-messages"></i>
                    <div>إدارة التذاكر</div>
                </a>
            </li>
        @endcan

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
                    @if ($pendingContactRequestsCount > 0)
                        <div class="badge bg-label-warning rounded-pill ms-auto">{{ $pendingContactRequestsCount }}
                        </div>
                    @endif
                </a>
            </li>
        @endcan

        @can('trial_requests.view')
            @php
                $pendingTrialRequestsCount = cache()->remember('admin:pending_trial_requests_count', 60, function () {
                    return \App\Models\Admin\PublicRequests\TrialRequest::where(
                        'status',
                        \App\Enums\Admin\PublicRequests\TrialRequestStatus::PENDING,
                    )->count();
                });
            @endphp
            <li class="menu-item @if (Route::is('admin.trial-requests.*')) active open @endif">
                <a href="{{ route('admin.trial-requests.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-clock"></i>
                    <div>طلبات التجربة</div>
                    @if ($pendingTrialRequestsCount > 0)
                        <div class="badge bg-label-warning rounded-pill ms-auto">{{ $pendingTrialRequestsCount }}</div>
                    @endif
                </a>
            </li>
        @endcan


        @canany(['system.settings', 'bank_settings.view', 'domain_extension_prices.settings', 'faqs.view'])
            <li class="menu-header small">
                <span class="menu-header-text">إعدادات المنصة</span>
            </li>

            @can('system.settings')
                <li class="menu-item @if (Route::is('admin.settings.index')) active open @endif">
                    <a href="{{ route('admin.settings.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-settings"></i>
                        <div>الإعدادات العامة</div>
                    </a>
                </li>
            @endcan

            @can('bank_settings.view')
                <li class="menu-item @if (Route::is('admin.settings.banks.*')) active open @endif">
                    <a href="{{ route('admin.settings.banks.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-building-bank"></i>
                        <div>الحسابات البنكية</div>
                    </a>
                </li>
            @endcan

            @can('domain_extension_prices.settings')
                <li class="menu-item @if (Route::is('admin.settings.domains.*')) active open @endif">
                    <a href="{{ route('admin.settings.domains.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-world"></i>
                        <div>إعدادات النطاقات</div>
                    </a>
                </li>
            @endcan

            @can('faqs.view')
                <li class="menu-item @if (Route::is('admin.faqs.*')) active open @endif"><a
                        href="{{ route('admin.faqs.index') }}" class="menu-link"><i
                            class="menu-icon tf-icons ti ti-help"></i>
                        <div>الأسئلة الشائعة</div>
                    </a></li>
            @endcan
        @endcanany

        <li class="menu-header small">
            <span class="menu-header-text">النشاطات</span>
        </li>

        @can('activity_log.view')
            <li class="menu-item @if (Route::is('admin.activity-logs.*')) active open @endif">
                <a href="{{ route('admin.activity-logs.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-history"></i>
                    <div>سجل النشاطات</div>
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
                        <div>العملاء المؤرشفون</div>
                    </a>
                </li>

                <li class="menu-item @if (Route::is('admin.archives.bills.*')) active @endif">
                    <a href="{{ route('admin.archives.bills.index') }}" class="menu-link">
                        <div>المعاملات المالية</div>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</aside>
