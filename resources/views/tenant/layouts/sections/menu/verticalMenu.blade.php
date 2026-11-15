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
                    $brandText = ($logoText && $logoText !== 'لوت LOT') ? $logoText : ($officeName ?: ($logoText ?: config('app.name')));
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

        {{-- المزادات والإعلانات --}}
        @canany(['auctions.view', 'auction_establishment_requests.view', 'marketing_campaigns.view',
            'auction_closings.view'])
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

            @foreach ([['auction_establishment_requests.view', 'auction-establishment-requests', 'ti-file-certificate', 'طلب إقامة مزاد'], ['marketing_campaigns.view', 'marketing-campaigns', 'ti-speakerphone', 'الحملات التسويقية'], ['auction_closings.view', 'auction-closings', 'ti-lock', 'إقفال المزاد']] as [$permission, $resource, $icon, $label])
                @can($permission)
                    <li class="menu-item @if (Helpers::isActiveRoute($resource . '.*')) active @endif">
                        <a href="{{ tenant_route_name($resource . '.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons ti {{ $icon }}"></i>
                            <div>{{ $label }}</div>
                        </a>
                    </li>
                @endcan
            @endforeach

            {{-- @canany(['auctions.view', 'auctions.create'])
                <li class="menu-item @if (Helpers::isActiveRoute('promotional-advertisements.*')) active @endif">
                    <a href="{{ tenant_route_name('promotional-advertisements.create') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-speakerphone"></i>
                        <div>الإعلانات الترويجية</div>
                    </a>
                </li>
            @endcanany --}}
        @endcanany

        @can('tickets.view')
            <li class="menu-header small">
                <span class="menu-header-text">التذاكر والدعم</span>
            </li>
            @php
                $openTicketsCount = cache()->remember(
                    'tenant:' . tenant('id') . ':open_tickets_count',
                    60,
                    function () {
                        try {
                            return app(
                                \App\Services\Shared\Integration\EtmamTicketService::class,
                            )->getTenantTicketStats()['open'] ?? 0;
                        } catch (\Throwable $e) {
                            return 0;
                        }
                    },
                );
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
                $tenantArchiveCounts = cache()->remember('tenant:' . tenant('id') . ':archive_sidebar_counts', 60, function () {
                    return [
                        'users' => \App\Models\Tenants\UserMangment\User\User::onlyTrashed()->count(),
                        'properties' => \App\Models\Tenants\Properties\Property::onlyTrashed()->count(),
                        'assignments' => \App\Models\Tenants\Assignments\Assignment::onlyTrashed()->count(),
                        'auctions' => \App\Models\Tenants\Auctions\Auction::onlyTrashed()->count(),
                    ];
                });

                $tenantUserCan = auth()->user();
                $totalTenantArchiveCount = 0;
                if ($tenantUserCan?->can('user_archive.view')) {
                    $totalTenantArchiveCount += $tenantArchiveCounts['users'] ?? 0;
                }
                if ($tenantUserCan?->can('properties_archive.view')) {
                    $totalTenantArchiveCount += $tenantArchiveCounts['properties'] ?? 0;
                }
                if ($tenantUserCan?->can('assignments_archive.view')) {
                    $totalTenantArchiveCount += $tenantArchiveCounts['assignments'] ?? 0;
                }
                if ($tenantUserCan?->can('auctions_archive.view')) {
                    $totalTenantArchiveCount += $tenantArchiveCounts['auctions'] ?? 0;
                }
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
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if (($tenantArchiveCounts['users'] ?? 0) <= 0) d-none @endif"
                                    data-sidebar-counter="archive_tenant_users">{{ $tenantArchiveCounts['users'] ?? 0 }}</div>
                            </a>
                        </li>
                    @endcan
                    @can('properties_archive.view')
                        <li class="menu-item @if (Helpers::isActiveRoute('archive.properties.*')) active @endif">
                            <a href="{{ tenant_route_name('archive.properties.index') }}" class="menu-link">
                                <div>العقارات</div>
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if (($tenantArchiveCounts['properties'] ?? 0) <= 0) d-none @endif"
                                    data-sidebar-counter="archive_tenant_properties">{{ $tenantArchiveCounts['properties'] ?? 0 }}</div>
                            </a>
                        </li>
                    @endcan
                    @can('assignments_archive.view')
                        <li class="menu-item @if (Helpers::isActiveRoute('archive.assignments.*')) active @endif">
                            <a href="{{ tenant_route_name('archive.assignments.index') }}" class="menu-link">
                                <div>الإسنادات</div>
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if (($tenantArchiveCounts['assignments'] ?? 0) <= 0) d-none @endif"
                                    data-sidebar-counter="archive_tenant_assignments">{{ $tenantArchiveCounts['assignments'] ?? 0 }}</div>
                            </a>
                        </li>
                    @endcan
                    @can('auctions_archive.view')
                        <li class="menu-item @if (Helpers::isActiveRoute('archive.auctions.*')) active @endif">
                            <a href="{{ tenant_route_name('archive.auctions.index') }}" class="menu-link">
                                <div>المزادات</div>
                                <div class="badge bg-light text-primary rounded-pill ms-auto @if (($tenantArchiveCounts['auctions'] ?? 0) <= 0) d-none @endif"
                                    data-sidebar-counter="archive_tenant_auctions">{{ $tenantArchiveCounts['auctions'] ?? 0 }}</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcanany
    </ul>
</aside>
