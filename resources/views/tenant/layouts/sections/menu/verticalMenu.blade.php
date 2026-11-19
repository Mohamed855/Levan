@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;
    $configData = App\Helpers\Helpers::appClasses();
    use App\Helpers\Helpers;
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

    <!-- ! Hide app brand if navbar-full -->
    @if (!isset($navbarFull))
        <div class="app-brand demo">
            <a href="{{ tenant_route_name('dashboard') }}" class="app-brand-link">
                <x-partials.tenant-logo :height="32" />
                @php
                    $logoText = App\Helpers\Tenants\Settings\SettingsHelper::get('logo_text');
                    $officeName = App\Helpers\Tenants\Settings\SettingsHelper::get('office_name');
                    $brandText =
                        $logoText && $logoText !== 'لوت LOT'
                            ? $logoText
                            : ($officeName ?:
                            ($logoText ?:
                            config('app.name')));
                @endphp
                <span class="app-brand-text demo menu-text fw-bold text-truncate" title="{{ $brandText }}">
                    {{ $brandText }}
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

        {{-- لوحة القيادة --}}
        <li class="menu-item @if (Helpers::isActiveRoute('dashboard')) active open @endif">
            <a href="{{ tenant_route_name('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-layout-dashboard"></i>
                <div>لوحة القيادة</div>
            </a>
        </li>

        {{-- الإسنادات والعقارات --}}
        @canany(['issuing_entities.view', 'properties.view', 'assignments.view'])
            <li class="menu-header small">
                <span class="menu-header-text">الإسنادات والعقارات</span>
            </li>

            @can('issuing_entities.view')
                <li class="menu-item @if (Helpers::isActiveRoute('issuing-entities.*')) active @endif">
                    <a href="{{ tenant_route_name('issuing-entities.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-scale"></i>
                        <div>الجهات المسندة</div>
                    </a>
                </li>
            @endcan

            @can('properties.view')
                <li class="menu-item @if (Helpers::isActiveRoute('properties.*')) active @endif">
                    <a href="{{ tenant_route_name('properties.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-building"></i>
                        <div>العقارات</div>
                    </a>
                </li>
            @endcan

            @can('assignments.view')
                <li class="menu-item @if (Helpers::isActiveRoute('assignments.*')) active @endif">
                    <a href="{{ tenant_route_name('assignments.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-file-certificate"></i>
                        <div>الإسنادات</div>
                    </a>
                </li>
            @endcan
        @endcanany

        {{-- المزادات والتقارير --}}
        @canany(['auctions.view', 'pending_auctions.view'])
            <li class="menu-header small">
                <span class="menu-header-text">المزادات والتقارير</span>
            </li>

            @can('auctions.view')
                <li class="menu-item @if (Helpers::isActiveRoute('auctions.*')) active @endif">
                    <a href="{{ tenant_route_name('auctions.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-gavel"></i>
                        <div>المزادات</div>
                    </a>
                </li>
            @endcan
            @can('pending_auctions.view')
                @php
                    $tenantCounts = $tenantCounts ?? app(\App\Services\Shared\Sidebar\SidebarCounterService::class)->getTenantCounts(auth('tenant')->user());
                    $pendingAuctionsCount = $tenantCounts['pending_auctions'] ?? 0;
                @endphp
                <li class="menu-item @if (Helpers::isActiveRoute('pending-auctions.*')) active @endif">
                    <a href="{{ tenant_route_name('pending-auctions.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-clock-check"></i>
                        <div>بانتظار الاعتماد</div>
                        <div class="badge bg-light text-primary rounded-pill ms-auto @if ($pendingAuctionsCount <= 0) d-none @endif"
                            data-sidebar-counter="pending_auctions">{{ $pendingAuctionsCount }}</div>
                    </a>
                </li>
            @endcan
        @endcanany

        @can('tickets.view')
            <li class="menu-header small">
                <span class="menu-header-text">التذاكر والدعم</span>
            </li>
            @php
                $sidebarCounterService = app(\App\Services\Shared\Sidebar\SidebarCounterService::class);
                $tenantCounts = $sidebarCounterService->getTenantCounts(auth()->user());
                $openTicketsCount = $tenantCounts['tickets_open'] ?? 0;
            @endphp
            <li class="menu-item @if (Helpers::isActiveRoute('tickets.*')) active @endif">
                <a href="{{ tenant_route_name('tickets.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-headset"></i>
                    <div>الدعم الفني</div>
                    <div class="badge bg-light text-primary rounded-pill ms-auto @if ($openTicketsCount <= 0) d-none @endif"
                        data-sidebar-counter="tickets_open">{{ $openTicketsCount }}</div>
                </a>
            </li>
        @endcan

        {{-- الأرشيف --}}
        @canany(['user_archive.view', 'properties_archive.view', 'assignments_archive.view', 'auctions_archive.view'])
            @php
                $tenantCounts =
                    $tenantCounts ??
                    app(\App\Services\Shared\Sidebar\SidebarCounterService::class)->getTenantCounts(auth()->user());
                $totalTenantArchiveCount = $tenantCounts['archive_tenant_total'] ?? 0;
            @endphp
            <li class="menu-header small">
                <span class="menu-header-text">الأرشيف</span>
            </li>
            <li class="menu-item @if (Helpers::isActiveRoute('archive.*')) active open @endif">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons ti ti-archive"></i>
                    <div>الأرشيف</div>
                    <div class="badge bg-light text-primary rounded-pill ms-auto @if ($totalTenantArchiveCount <= 0) d-none @endif"
                        data-sidebar-counter="archive_tenant_total">{{ $totalTenantArchiveCount }}</div>
                </a>
                <ul class="menu-sub">
                    @can('user_archive.view')
                        <li class="menu-item @if (Helpers::isActiveRoute('archive.users.*')) active @endif">
                            <a href="{{ tenant_route_name('archive.users.index') }}" class="menu-link">
                                <div>المستخدمين</div>
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if (($tenantCounts['archive_tenant_users'] ?? 0) <= 0) d-none @endif"
                                    data-sidebar-counter="archive_tenant_users">
                                    {{ $tenantCounts['archive_tenant_users'] ?? 0 }}</div>
                            </a>
                        </li>
                    @endcan
                    @can('properties_archive.view')
                        <li class="menu-item @if (Helpers::isActiveRoute('archive.properties.*')) active @endif">
                            <a href="{{ tenant_route_name('archive.properties.index') }}" class="menu-link">
                                <div>العقارات</div>
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if (($tenantCounts['archive_tenant_properties'] ?? 0) <= 0) d-none @endif"
                                    data-sidebar-counter="archive_tenant_properties">
                                    {{ $tenantCounts['archive_tenant_properties'] ?? 0 }}</div>
                            </a>
                        </li>
                    @endcan
                    @can('assignments_archive.view')
                        <li class="menu-item @if (Helpers::isActiveRoute('archive.assignments.*')) active @endif">
                            <a href="{{ tenant_route_name('archive.assignments.index') }}" class="menu-link">
                                <div>الإسنادات</div>
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if (($tenantCounts['archive_tenant_assignments'] ?? 0) <= 0) d-none @endif"
                                    data-sidebar-counter="archive_tenant_assignments">
                                    {{ $tenantCounts['archive_tenant_assignments'] ?? 0 }}</div>
                            </a>
                        </li>
                    @endcan
                    @can('auctions_archive.view')
                        <li class="menu-item @if (Helpers::isActiveRoute('archive.auctions.*')) active @endif">
                            <a href="{{ tenant_route_name('archive.auctions.index') }}" class="menu-link">
                                <div>المزادات</div>
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if (($tenantCounts['archive_tenant_auctions'] ?? 0) <= 0) d-none @endif"
                                    data-sidebar-counter="archive_tenant_auctions">
                                    {{ $tenantCounts['archive_tenant_auctions'] ?? 0 }}</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcanany
    </ul>
</aside>
