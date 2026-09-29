@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Route;

    $containerNav = $configData['contentLayout'] === 'compact' ? 'container-xxl' : 'container-fluid';
    $navbarDetached = $navbarDetached ?? '';
@endphp

@if (isset($navbarDetached) && $navbarDetached == 'navbar-detached')
    <nav class="layout-navbar {{ $containerNav }} navbar navbar-expand-xl {{ $navbarDetached }} align-items-center bg-navbar-theme"
        id="layout-navbar">
    @else
        <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
            <div class="{{ $containerNav }}">
@endif

@if (!isset($navbarHideToggle))
    <div
        class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none' : '' }}{{ isset($contentNavbar) ? ' d-xl-none' : '' }}">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0);">
            <i class="ti ti-menu-2 ti-md"></i>
        </a>
    </div>
@endif

<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
    @if (!isset($menuHorizontal))
        <!-- Search -->
        <div class="navbar-nav align-items-center d-none d-md-inline-block">
            <div class="nav-item navbar-search-wrapper mb-0">
                <a class="nav-item nav-link search-toggler d-flex align-items-center px-0" href="javascript:void(0);">
                    <i class="ti ti-search ti-md me-2 me-lg-4 ti-lg"></i>
                    <span class="d-none d-md-inline-block text-muted fw-normal">ابحـث (Ctrl+/)</span>
                </a>
            </div>
        </div>
        <!-- /Search -->
    @endif

    <ul class="navbar-nav flex-row align-items-center ms-auto">
        @if (isset($menuHorizontal))
            <li class="nav-item navbar-search-wrapper">
                <a class="nav-link btn btn-text-secondary btn-icon rounded-pill search-toggler"
                    href="javascript:void(0);">
                    <i class="ti ti-search ti-md"></i>
                </a>
            </li>
        @endif

        @if (!empty($tenantHasUpgrade) && $tenantHasUpgrade && !empty($latestVersion))
            @can('subscriptions.upgrade')
                <li class="nav-item mx-2 mx-md-5">
                    <a class="btn btn-sm btn-primary px-2" style="white-space: nowrap; font-size: 10px;"
                        href="{{ tenant_route_name('versions.latest') }}" title="عرض تفاصيل الإصدارة الجديدة">

                        <span class="d-inline d-md-none">الإصدارة
                            {{ $latestVersion->version ?? '#' . $latestVersion->id }}
                            متاحة</span>
                        <span class="d-none d-md-inline" style="font-size: 14px;">الإصدارة
                            {{ $latestVersion->version ?? '#' . $latestVersion->id }} متاحة</span>

                    </a>
                </li>
            @endcan
        @endif

        @can('domain.view')
            <li class="nav-item dropdown me-2 me-xl-2">
                <a href="{{ tenant_route_name('domains.setting') }}" class="menu-link" title="إدارة النطاقات"
                    data-bs-toggle="tooltip" data-bs-placement="bottom">
                    <i class="menu-icon tf-icons ti ti-world"></i>
                </a>
            </li>
        @endcan



        {{-- Settings - يظهر فقط إذا توفرت إحدى الصلاحيات --}}
        @canany([
            'system.settings',
            'users.view',
            'roles.view',
            'activity_log.view',
            'message_logs.view',
            'branches_settings.view',
            'countries.view',
            'payment_methods.view',
            'client_statuses.view',
            'contact_channels.view',
            'message_templates.view',
            'subscriptions.view',
            'bills.view',
            'system.groups',
            ])
            <li class="nav-item dropdown me-2 me-xl-2">
                <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
                    href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class="ti ti-settings ti-md"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end p-3 nav-min-width">
                    <div class="row g-2">
                        {{-- الصف الأول: إعدادات النظام --}}
                        @can('system.settings')
                            <div class="col-12">
                                <a href="{{ tenant_route_name('settings.index') }}"
                                    class="d-flex align-items-center p-3 border rounded hover-bg-light text-decoration-none">
                                    <div class="avatar avatar-md me-3">
                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class="ti ti-settings ti-lg"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-medium">إعدادات النظام</h6>
                                        <small class="text-muted">إدارة الإعدادات الأساسية للنظام</small>
                                    </div>
                                    <i class="ti ti-chevron-left text-muted"></i>
                                </a>
                            </div>
                        @endcan

                        {{-- الصف الثاني: المستخدمين والأنظمة الأمنية --}}
                        @can('users.view')
                            <div class="col-6">
                                <a href="{{ tenant_route_name('users.index') }}"
                                    class="d-block p-3 text-center border rounded hover-bg-light text-decoration-none">
                                    <div class="avatar avatar-md mx-auto mb-2">
                                        <span class="avatar-initial rounded bg-label-success">
                                            <i class="ti ti-users ti-lg"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted fw-medium">المستخدمين</small>
                                </a>
                            </div>
                        @endcan

                        @can('roles.view')
                            <div class="col-6">
                                <a href="{{ tenant_route_name('roles.index') }}"
                                    class="d-block p-3 text-center border rounded hover-bg-light text-decoration-none">
                                    <div class="avatar avatar-md mx-auto mb-2">
                                        <span class="avatar-initial rounded bg-label-info">
                                            <i class="ti ti-shield-lock ti-lg"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted fw-medium">الأنظمة الأمنية</small>
                                </a>
                            </div>
                        @endcan

                        {{-- الصف الثالث: السجلات --}}
                        @can('activity_log.view')
                            <div class="col-6">
                                <a href="{{ tenant_route_name('activity-logs.index') }}"
                                    class="d-block p-3 text-center border rounded hover-bg-light text-decoration-none">
                                    <div class="avatar avatar-md mx-auto mb-2">
                                        <span class="avatar-initial rounded bg-label-warning">
                                            <i class="ti ti-activity ti-lg"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted fw-medium">سجل النشاطات</small>
                                </a>
                            </div>
                        @endcan

                        @can('message_logs.view')
                            <div class="col-6">
                                <a href="{{ tenant_route_name('communication.message-individual-logs.index') }}"
                                    class="d-block p-3 text-center border rounded hover-bg-light text-decoration-none">
                                    <div class="avatar avatar-md mx-auto mb-2">
                                        <span class="avatar-initial rounded bg-label-info">
                                            <i class="ti ti-message ti-lg"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted fw-medium">سجل الرسائل</small>
                                </a>
                            </div>
                        @endcan

                        {{-- المجموعات --}}
                        @can('system.groups')
                            <div class="col-6">
                                <a href="{{ tenant_route_name('teams.index') }}"
                                    class="d-block p-3 text-center border rounded hover-bg-light text-decoration-none">
                                    <div class="avatar avatar-md mx-auto mb-2">
                                        <span class="avatar-initial rounded bg-label-dark">
                                            <i class="ti ti-users-group ti-lg"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted fw-medium">المجموعات</small>
                                </a>
                            </div>
                        @endcan
                        @can('subscriptions.view')
                            <div class="col-6">
                                <a href="{{ tenant_route_name('subscriptions.index') }}"
                                    class="d-block p-3 text-center border rounded hover-bg-light text-decoration-none">
                                    <div class="avatar avatar-md mx-auto mb-2">
                                        <span class="avatar-initial rounded bg-label-secondary">
                                            <i class="ti ti-settings-cog ti-lg"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted fw-medium">إدارة الإشتراك</small>
                                </a>
                            </div>
                        @endcan


                        @can('bills.view')
                            <div class="col-12">
                                <a href="{{ tenant_route_name('bills.index') }}"
                                    class="d-block p-3 text-center border rounded hover-bg-light text-decoration-none">
                                    <div class="avatar avatar-md mx-auto mb-2">
                                        <span class="avatar-initial rounded bg-label-primary">
                                            <i class="ti ti-receipt ti-lg"></i>
                                        </span>
                                    </div>
                                    <small class="text-muted fw-medium">الفواتير</small>
                                </a>
                            </div>
                        @endcan

                    </div>

                    {{-- رابط المركز التفصيلي --}}
                    @canany([
                        'branches_settings.view',
                        'countries.view',
                        'payment_methods.view',
                        'client_statuses.view',
                        'contact_channels.view',
                        'message_templates.view',
                        ])
                        <div class="dropdown-divider my-3"></div>
                        <div class="d-grid">
                            <a href="{{ tenant_route_name('settings.center') }}" class="btn btn-primary btn-sm">
                                <i class="ti ti-adjustments me-2"></i>
                                الإعدادات التفصيلية
                            </a>
                        </div>
                    @endcanany
                </div>
            </li>
        @endcanany
        {{-- /Settings --}}

        <!-- Notification -->
        <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-2 me-xl-2">
            <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
                href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                aria-expanded="false" id="notificationDropdown">
                <span class="position-relative">
                    <i class="ti ti-bell ti-md"></i>
                    <span class="badge rounded-pill bg-danger badge-dot badge-notifications border"
                        style="{{ (auth()->user()?->unreadNotifications?->count() ?? 0) > 0 ? '' : 'display: none;' }}"></span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end p-0" style="min-width: 350px;">
                <li class="dropdown-menu-header border-bottom">
                    <div class="dropdown-header d-flex align-items-center py-3">
                        <h6 class="mb-0 me-auto fw-bold">الإشعارات</h6>
                        <div class="d-flex align-items-center h6 mb-0">
                            <span class="badge bg-label-primary me-2" id="unreadCountBadge">
                                {{ auth()->user()?->unreadNotifications?->count() ?? 0 }} جديد
                            </span>
                            <a href="javascript:void(0)" onclick="markAllAsRead()"
                                class="btn btn-text-secondary rounded-pill btn-icon" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="تحديد الكل كمقروء">
                                <i class="ti ti-mail-opened text-heading"></i>
                            </a>
                        </div>
                    </div>
                </li>

                <li class="dropdown-notifications-list scrollable-container overflow-auto" style="max-height: 300px;">
                    <ul class="list-group list-group-flush" id="notificationsList">
                        @forelse(auth()->user()?->notifications()->take(10)->get() ?? [] as $notification)
                            @php
                                $data = $notification->data;
                                $iconColors = [
                                    'success' => 'bg-label-success',
                                    'warning' => 'bg-label-warning',
                                    'info' => 'bg-label-info',
                                    'danger' => 'bg-label-danger',
                                    'primary' => 'bg-label-primary',
                                    'secondary' => 'bg-label-secondary',
                                ];
                                $colorClass = $iconColors[$data['type'] ?? 'info'] ?? 'bg-label-info';
                            @endphp

                            <li class="list-group-item list-group-item-action dropdown-notifications-item {{ $notification->read_at ? 'marked-as-read' : '' }}"
                                data-notification-id="{{ $notification->id }}">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar">
                                            <span class="avatar-initial rounded-circle {{ $colorClass }}">
                                                <i class="{{ $data['icon'] ?? 'ti-bell' }}"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1" @if(!empty($data['url'])) onclick="window.location.href='{{ $data['url'] }}'" style="cursor: pointer;" @endif>
                                        <h6 class="small mb-1 fw-semibold">{{ $data['title'] ?? 'إشعار' }}</h6>
                                        <p class="mb-1 small text-muted">{{ $data['message'] ?? '' }}</p>
                                        <small
                                            class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                    </div>
                                    <div class="flex-shrink-0 dropdown-notifications-actions">
                                        @if (!$notification->read_at)
                                            <span class="badge badge-dot bg-primary"></span>
                                        @endif
                                        <a href="javascript:void(0)"
                                            onclick="deleteNotification('{{ $notification->id }}')"
                                            class="dropdown-notifications-archive ms-2">
                                            <span class="ti ti-x"></span>
                                        </a>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-center py-4">
                                <i class="ti ti-bell-off ti-lg text-muted mb-2"></i>
                                <p class="text-muted mb-0">لا توجد إشعارات</p>
                            </li>
                        @endforelse
                    </ul>
                </li>

                @if (auth()->user()->notifications()->count() > 0)
                    <li class="border-top">
                        <div class="d-grid p-4">
                            <a class="btn btn-primary btn-sm d-flex"
                                href="{{ tenant_route_name('notifications.all') }}">
                                <small class="align-middle">عرض كل الإشعارات</small>
                            </a>
                        </div>
                    </li>
                @endif
            </ul>
        </li>
        <!-- /Notification -->

        {{-- @if ($configData['hasCustomizer'] === true)
            <!-- Style Switcher -->
            <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-2 d-none d-md-inline-block">
                <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
                    href="javascript:void(0);" data-bs-toggle="dropdown">
                    <i class="ti ti-md"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                            <span class="align-middle"><i class="ti ti-sun ti-md me-3"></i>فاتح</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                            <span class="align-middle"><i class="ti ti-moon-stars ti-md me-3"></i>داكن</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                            <span class="align-middle"><i
                                    class="ti ti-device-desktop-analytics ti-md me-3"></i>النظام</span>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- /Style Switcher -->
        @endif --}}

        <!-- User -->
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                <div class="avatar avatar-online">

                    <img src="{{ tasset(Auth::user()->image) }}" alt="{{ Auth::user()->name }}"
                        class="rounded-circle" width="40" height="40">

                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item mt-0" href="#">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-online me-2">
                                <img src="{{ tasset(Auth::user()->image) }}" alt="{{ Auth::user()->name }}"
                                    class="rounded-circle" width="40" height="40">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">{{ Auth::user()->name }}</h6>
                                <small class="text-muted">{{ Auth::user()->getRoleNames()->implode(', ') }}</small>
                            </div>
                        </div>
                    </a>
                </li>
                <li>
                    <div class="dropdown-divider my-1 mx-n2"></div>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ tenant_route_name('users.profile') }}">
                        <i class="ti ti-user me-3 ti-md"></i>
                        <span class="align-middle">الملف الشخصي</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ tenant_route_name('users.password.change') }}">
                        <i class="ti ti-settings me-3 ti-md"></i>
                        <span class="align-middle">تغيير كلمة المرور</span>
                    </a>
                </li>

                <li>
                    <a class="dropdown-item" href="{{ tenant_route_name('tenants.two-factor.index') }}">
                        <i class="ti ti-shield-lock me-3 ti-md"></i>
                        <span class="align-middle">المصادقة الثنائية</span>
                    </a>
                </li>

                <li>
                    <div class="dropdown-divider my-1 mx-n2"></div>
                </li>
                <li>
                    <div class="d-grid px-2 pt-2 pb-1">

                        <button type="button" id="tenant-logout-btn"
                            class="btn btn-sm btn-danger d-flex align-items-center justify-content-center">
                            <small class="align-middle">تسجيل الخروج</small>
                            <i class="ti ti-logout ms-2 ti-14px"></i>
                        </button>

                        <form id="tenant-logout-form" method="POST" action="{{ tenant_route_name('logout') }}">
                            @csrf
                        </form>
                    </div>
                </li>

                <script>
                    document.getElementById('tenant-logout-btn')
                        .addEventListener('click', function() {
                            document.getElementById('tenant-logout-form').submit();
                        });
                </script>

            </ul>
        </li>
        <!--/ User -->
    </ul>
</div>

<!-- Search Small Screens -->
<div class="navbar-search-wrapper search-input-wrapper {{ isset($menuHorizontal) ? $containerNav : '' }} d-none">
    <input type="text"
        class="form-control search-input {{ isset($menuHorizontal) ? '' : $containerNav }} border-0"
        placeholder="Search..." aria-label="Search...">
    <i class="ti ti-x search-toggler cursor-pointer"></i>
</div>
<!--/ Search Small Screens -->
</nav>
