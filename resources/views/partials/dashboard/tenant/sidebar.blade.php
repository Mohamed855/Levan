<div class="app-brand demo"><a href="{{ route('tenant.dashboard') }}" class="app-brand-link"><span class="app-brand-text">لوت</span><small class="text-white-50">لوحة المستأجر</small></a></div>
<ul class="menu-inner py-1">
    <li class="menu-item {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}"><a href="{{ route('tenant.dashboard') }}" class="menu-link"><i class="menu-icon ti ti-layout-dashboard"></i><span>لوحة التحكم</span></a></li>
    <li class="menu-item {{ request()->routeIs('tenant.users.*') ? 'active' : '' }}"><a href="{{ route('tenant.users.index') }}" class="menu-link"><i class="menu-icon ti ti-users"></i><span>المستخدمون</span></a></li>
    <li class="menu-item {{ request()->routeIs('tenant.roles.*') ? 'active' : '' }}"><a href="{{ route('tenant.roles.index') }}" class="menu-link"><i class="menu-icon ti ti-shield-check"></i><span>الأدوار والصلاحيات</span></a></li>
    <li class="menu-item {{ request()->routeIs('tenant.companies.*') ? 'active' : '' }}"><a href="{{ route('tenant.companies.index') }}" class="menu-link"><i class="menu-icon ti ti-briefcase"></i><span>مركز الأعمال</span></a></li>
    <li class="menu-item {{ request()->routeIs('tenant.assignments.*') ? 'active' : '' }}"><a href="{{ route('tenant.assignments.index') }}" class="menu-link"><i class="menu-icon ti ti-file-description"></i><span>الإسنادات</span></a></li>
    <li class="menu-item {{ request()->routeIs('tenant.properties.*') ? 'active' : '' }}"><a href="{{ route('tenant.properties.index') }}" class="menu-link"><i class="menu-icon ti ti-building-estate"></i><span>العقارات</span></a></li>
    <li class="menu-item {{ request()->routeIs('tenant.auctions.*') ? 'active' : '' }}"><a href="{{ route('tenant.auctions.index') }}" class="menu-link"><i class="menu-icon ti ti-gavel"></i><span>المزادات</span></a></li>
    <li class="menu-item {{ request()->routeIs('tenant.bills.*') ? 'active' : '' }}"><a href="{{ route('tenant.bills.index') }}" class="menu-link"><i class="menu-icon ti ti-receipt-2"></i><span>الفواتير</span></a></li>
    <li class="menu-item {{ request()->routeIs('tenant.tickets.*') ? 'active' : '' }}"><a href="{{ route('tenant.tickets.index') }}" class="menu-link"><i class="menu-icon ti ti-headset"></i><span>الدعم الفني</span></a></li>
    <li class="menu-item {{ request()->routeIs('tenant.notifications.*') ? 'active' : '' }}"><a href="{{ route('tenant.notifications.index') }}" class="menu-link"><i class="menu-icon ti ti-bell"></i><span>الإشعارات</span></a></li>
    <li class="menu-item {{ request()->routeIs('tenant.activity-log.*') ? 'active' : '' }}"><a href="{{ route('tenant.activity-log.index') }}" class="menu-link"><i class="menu-icon ti ti-history"></i><span>سجل العمليات</span></a></li>
    <li class="menu-item {{ request()->routeIs('tenant.archives.*') ? 'active open' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle"><i class="menu-icon ti ti-archive"></i><span>الأرشيف</span></a>
        <ul class="menu-sub">
            <li class="menu-item {{ request()->routeIs('tenant.archives.properties.*') ? 'active' : '' }}"><a href="{{ route('tenant.archives.properties.index') }}" class="menu-link"><span>أرشيف العقارات</span></a></li>
            <li class="menu-item {{ request()->routeIs('tenant.archives.assignments.*') ? 'active' : '' }}"><a href="{{ route('tenant.archives.assignments.index') }}" class="menu-link"><span>أرشيف الإسنادات</span></a></li>
            <li class="menu-item {{ request()->routeIs('tenant.archives.companies.*') ? 'active' : '' }}"><a href="{{ route('tenant.archives.companies.index') }}" class="menu-link"><span>أرشيف مركز الأعمال</span></a></li>
        </ul>
    </li>
    <li class="menu-item {{ request()->routeIs('tenant.settings.*') ? 'active' : '' }}"><a href="{{ route('tenant.settings.index') }}" class="menu-link"><i class="menu-icon ti ti-settings"></i><span>الإعدادات</span></a></li>
</ul>
