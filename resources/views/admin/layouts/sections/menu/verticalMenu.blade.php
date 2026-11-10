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
                <span class="menu-header-text">الإشتراكات والمالية</span>
            </li>

            @can('subscriptions.view')
                @php
                    $expiringCount = cache()->remember('admin:expiring_subscriptions_count', 60, function () {
                        return app(
                            \App\Services\Admin\SubscriptionManagement\SubscriptionManagementService::class,
                        )->getExpiringSoonCount();
                    });
                    $expiredCount = cache()->remember('admin:expired_subscriptions_count', 60, function () {
                        return \App\Models\Admin\Subscription\Subscription::where(
                            'status',
                            \App\Enums\Admin\Subscriptions\SubscriptionStatus::EXPIRED,
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
                        <div>المعاملات المالية</div>
                    </a>
                </li>
            @endcan
        @endcanany

        @canany(['tickets.view', 'contact_requests.view', 'trial_requests.view', 'faqs.view'])
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
                        <i class="menu-icon tf-icons ti ti-messages"></i>
                        <div>الدعم الفني</div>
                        <div class="badge bg-light text-primary rounded-pill ms-auto @if ($openTicketsCount <= 0) d-none @endif"
                            data-sidebar-counter="tickets_open">{{ $openTicketsCount }}</div>
                    </a>
                </li>
            @endcan

            @can('faqs.view')
                <li class="menu-item @if (Route::is('admin.faqs.*')) active open @endif"><a
                        href="{{ route('admin.faqs.index') }}" class="menu-link"><i class="menu-icon tf-icons ti ti-help"></i>
                        <div>الأسئلة الشائعة</div>
                    </a>
                </li>
            @endcan
        @endcanany
    </ul>
</aside>
