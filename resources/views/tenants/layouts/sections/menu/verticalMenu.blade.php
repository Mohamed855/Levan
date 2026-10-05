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
                <x-partials.tenant-logo :logo-path="asset('static/brand/logos/logo.svg')" />
                <span class="app-brand-text demo menu-text fw-bold">
                    {{ App\Helpers\Tenants\Settings\SettingsHelper::get('logo_text') }}
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
                <i class="menu-icon tf-icons ti ti-smart-home"></i>
                <div>لوحة القيادة</div>
            </a>
        </li>

        {{-- إدارة الشركة وفريق العمل --}}
        @canany(['users.view', 'system.groups', 'roles.view'])
            <li class="menu-header small">
                <span class="menu-header-text">الشركة وفريق العمل</span>
            </li>

            @can('system.groups')
                <li class="menu-item @if (Helpers::isActiveRoute('teams.*')) active @endif">
                    <a href="{{ tenant_route_name('teams.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-users-group"></i>
                        <div>فرق العمل</div>
                    </a>
                </li>
            @endcan

            @can('users.view')
                <li class="menu-item @if (Helpers::isActiveRoute('users.*')) active @endif">
                    <a href="{{ tenant_route_name('users.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-users"></i>
                        <div>المستخدمين</div>
                    </a>
                </li>
            @endcan

            @can('roles.view')
                <li class="menu-item @if (Helpers::isActiveRoute('roles.*')) active @endif">
                    <a href="{{ tenant_route_name('roles.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-shield-lock"></i>
                        <div>الأدوار والصلاحيات</div>
                    </a>
                </li>
            @endcan
        @endcanany

        {{-- الاشتراكات والفوترة --}}
        @canany(['subscriptions.view', 'bills.view', 'domain.view'])
            <li class="menu-header small">
                <span class="menu-header-text">الاشتراك والفوترة</span>
            </li>

            @can('subscriptions.view')
                <li class="menu-item @if (Helpers::isActiveRoute('subscriptions.*')) active @endif">
                    <a href="{{ tenant_route_name('subscriptions.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-award"></i>
                        <div>الاشتراك والتراخيص</div>
                    </a>
                </li>
            @endcan

            @can('bills.view')
                <li class="menu-item @if (Helpers::isActiveRoute('bills.*')) active @endif">
                    <a href="{{ tenant_route_name('bills.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-credit-card"></i>
                        <div>الفواتير</div>
                    </a>
                </li>
            @endcan

            @can('domain.view')
                <li class="menu-item @if (Helpers::isActiveRoute('domains.*')) active @endif">
                    <a href="{{ tenant_route_name('domains.setting') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-world"></i>
                        <div>إدارة النطاقات</div>
                    </a>
                </li>
            @endcan
        @endcanany

        {{-- الدعم الفني والنظام --}}
        <li class="menu-header small">
            <span class="menu-header-text">النظام والدعم</span>
        </li>

        <li class="menu-item @if (Helpers::isActiveRoute('tickets.*')) active @endif">
            <a href="{{ tenant_route_name('tickets.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-headset"></i>
                <div>الدعم الفني</div>
            </a>
        </li>

        @can('activity_log.view')
            <li class="menu-item @if (Helpers::isActiveRoute('activity-logs.*')) active @endif">
                <a href="{{ tenant_route_name('activity-logs.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-activity"></i>
                    <div>سجل النشاطات</div>
                </a>
            </li>
        @endcan

        @can('user_archive.view')
            <li class="menu-item @if (Helpers::isActiveRoute('archive.users.*')) active @endif">
                <a href="{{ tenant_route_name('archive.users.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-archive"></i>
                    <div>المستخدمون المحذوفون</div>
                </a>
            </li>
        @endcan

        @can('system.settings')
            <li class="menu-item @if (Helpers::isActiveRoute('settings.*')) active @endif">
                <a href="{{ tenant_route_name('settings.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons ti ti-settings"></i>
                    <div>إعدادات الشركة</div>
                </a>
            </li>
        @endcan

    </ul>
</aside>
