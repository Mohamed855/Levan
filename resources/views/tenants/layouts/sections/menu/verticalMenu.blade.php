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
            <span class="app-brand-logo demo">@include('_partials.macros', ['logoPath' => asset('img/logo-light.svg')])</span>
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

        {{-- إدارة المنشأة وفريق العمل --}}
        @canany(['الفروع', 'المستخدمين', 'المجموعات', 'الانظمة الامنية'])
        <li class="menu-header small">
            <span class="menu-header-text">المنشأة وفريق العمل</span>
        </li>

        @can('الفروع')
        <li class="menu-item @if (Helpers::isActiveRoute('branches.*')) active @endif">
            <a href="{{ tenant_route_name('branches.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-git-fork"></i>
                <div>الفروع</div>
            </a>
        </li>
        @endcan

        @can('المجموعات')
        <li class="menu-item @if (Helpers::isActiveRoute('teams.*')) active @endif">
            <a href="{{ tenant_route_name('teams.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-users-group"></i>
                <div>فرق العمل</div>
            </a>
        </li>
        @endcan

        @can('المستخدمين')
        <li class="menu-item @if (Helpers::isActiveRoute('users.*')) active @endif">
            <a href="{{ tenant_route_name('users.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-users"></i>
                <div>المستخدمين</div>
            </a>
        </li>
        @endcan

        @can('الانظمة الامنية')
        <li class="menu-item @if (Helpers::isActiveRoute('roles.*')) active @endif">
            <a href="{{ tenant_route_name('roles.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-shield-lock"></i>
                <div>الأدوار والصلاحيات</div>
            </a>
        </li>
        @endcan
        @endcanany

        {{-- الاشتراكات والفوترة --}}
        @canany(['الإشتراك', 'الفواتير', 'إعدادات الدومين'])
        <li class="menu-header small">
            <span class="menu-header-text">الاشتراك والفوترة</span>
        </li>

        @can('الإشتراك')
        <li class="menu-item @if (Helpers::isActiveRoute('subscriptions.*')) active @endif">
            <a href="{{ tenant_route_name('subscriptions.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-award"></i>
                <div>الاشتراك والتراخيص</div>
            </a>
        </li>
        @endcan

        @can('الفواتير')
        <li class="menu-item @if (Helpers::isActiveRoute('bills.*')) active @endif">
            <a href="{{ tenant_route_name('bills.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-credit-card"></i>
                <div>الفواتير</div>
            </a>
        </li>
        @endcan

        @can('إعدادات الدومين')
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

        @can('سجل النشاطات')
        <li class="menu-item @if (Helpers::isActiveRoute('activity-logs.*')) active @endif">
            <a href="{{ tenant_route_name('activity-logs.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-activity"></i>
                <div>سجل النشاطات</div>
            </a>
        </li>
        @endcan

        @can('أرشيف المستخدمين')
        <li class="menu-item @if (Helpers::isActiveRoute('archive.users.*')) active @endif">
            <a href="{{ tenant_route_name('archive.users.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-archive"></i>
                <div>أرشيف المستخدمين</div>
            </a>
        </li>
        @endcan

        @can('إعدادات النظام')
        <li class="menu-item @if (Helpers::isActiveRoute('settings.*')) active @endif">
            <a href="{{ tenant_route_name('settings.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-settings"></i>
                <div>إعدادات المنشأة</div>
            </a>
        </li>
        @endcan

    </ul>
</aside>