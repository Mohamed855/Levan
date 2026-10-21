<div class="app-brand demo">
    <a href="{{ route('admin.dashboard') }}" class="app-brand-link">
        <span class="app-brand-text">لوت</span>
        <small class="text-white-50">إدارة المنصة</small>
    </a>
</div>
<ul class="menu-inner py-1">
    <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <a href="{{ route('admin.dashboard') }}" class="menu-link">
            <i class="menu-icon ti ti-layout-dashboard"></i>
            <span>لوحة التحكم</span>
        </a>
    </li>

    @can('tenants.view')
        <li class="menu-item {{ request()->routeIs('admin.tenants.*') ? 'active' : '' }}">
            <a href="{{ route('admin.tenants.index') }}" class="menu-link">
                <i class="menu-icon ti ti-building"></i>
                <span>العملاء</span>
            </a>
        </li>
    @endcan

    @can('subscriptions.view')
        <li class="menu-item {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">
            <a href="{{ route('admin.subscriptions.index') }}" class="menu-link">
                <i class="menu-icon ti ti-credit-card"></i>
                <span>الاشتراكات</span>
            </a>
        </li>
    @endcan

    @can('bills.view')
        <li class="menu-item {{ request()->routeIs('admin.bills.*') ? 'active' : '' }}">
            <a href="{{ route('admin.bills.index') }}" class="menu-link">
                <i class="menu-icon ti ti-receipt"></i>
                <span>الفواتير</span>
            </a>
        </li>
    @endcan

    @can('tickets.view')
        <li class="menu-item {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
            <a href="{{ route('admin.tickets.index') }}" class="menu-link">
                <i class="menu-icon ti ti-headset"></i>
                <span>الدعم الفني</span>
            </a>
        </li>
    @endcan


    @can('plans.view')
        <li class="menu-item {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
            <a href="{{ route('admin.plans.index') }}" class="menu-link">
                <i class="menu-icon ti ti-box-seam"></i>
                <span>الباقات</span>
            </a>
        </li>
    @endcan

    @can('coupons.view')
        <li class="menu-item {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">
            <a href="{{ route('admin.coupons.index') }}" class="menu-link">
                <i class="menu-icon ti ti-ticket"></i>
                <span>كوبونات الخصم</span>
            </a>
        </li>
    @endcan

    @can('public_requests.view')
        <li class="menu-item {{ request()->routeIs('admin.contact-requests.*', 'admin.trial-requests.*') ? 'open active' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon ti ti-mail"></i>
                <span>طلبات الموقع</span>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{ request()->routeIs('admin.contact-requests.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.contact-requests.index') }}" class="menu-link">
                        <span>طلبات التواصل</span>
                    </a>
                </li>
                <li class="menu-item {{ request()->routeIs('admin.trial-requests.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.trial-requests.index') }}" class="menu-link">
                        <span>طلبات التجربة</span>
                    </a>
                </li>
            </ul>
        </li>
    @endcan

    @can('auction_templates.view')
        <li class="menu-item {{ request()->routeIs('admin.auction-templates.*') ? 'active' : '' }}">
            <a href="{{ route('admin.auction-templates.index') }}" class="menu-link">
                <i class="menu-icon ti ti-templates"></i>
                <span>قوالب المزادات</span>
            </a>
        </li>
    @endcan

    @can('admin_users.view')
        <li class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <a href="{{ route('admin.users.index') }}" class="menu-link">
                <i class="menu-icon ti ti-users"></i>
                <span>مديرو المنصة</span>
            </a>
        </li>
    @endcan

    @can('roles.view')
        <li class="menu-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
            <a href="{{ route('admin.roles.index') }}" class="menu-link">
                <i class="menu-icon ti ti-shield-check"></i>
                <span>الأدوار والصلاحيات</span>
            </a>
        </li>
    @endcan

    @can('banks.view')
        <li class="menu-item {{ request()->routeIs('admin.banks.*') ? 'active' : '' }}">
            <a href="{{ route('admin.banks.index') }}" class="menu-link">
                <i class="menu-icon ti ti-building-bank"></i>
                <span>الحسابات البنكية</span>
            </a>
        </li>
    @endcan

    @can('control_domains.view')
        <li class="menu-item {{ request()->routeIs('admin.control-domains.*') ? 'active' : '' }}">
            <a href="{{ route('admin.control-domains.index') }}" class="menu-link">
                <i class="menu-icon ti ti-world"></i>
                <span>نطاقات النظام</span>
            </a>
        </li>
    @endcan

    @can('faq.view')
        <li class="menu-item {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}">
            <a href="{{ route('admin.faqs.index') }}" class="menu-link">
                <i class="menu-icon ti ti-help"></i>
                <span>الأسئلة الشائعة</span>
            </a>
        </li>
    @endcan

    @can('activity_log.view')
        <li class="menu-item {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}">
            <a href="{{ route('admin.activity-logs.index') }}" class="menu-link">
                <i class="menu-icon ti ti-activity"></i>
                <span>سجل النشاطات</span>
            </a>
        </li>
    @endcan

    <li class="menu-item {{ request()->routeIs('admin.archives.*') ? 'open active' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon ti ti-archive"></i>
            <span>أرشيف المحذوفات</span>
        </a>
        <ul class="menu-sub">
            @can('plans.view')
                <li class="menu-item {{ request()->routeIs('admin.archives.plans.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.archives.plans.index') }}" class="menu-link">
                        <span>أرشيف الباقات</span>
                    </a>
                </li>
            @endcan
            @can('coupons.view')
                <li class="menu-item {{ request()->routeIs('admin.archives.coupons.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.archives.coupons.index') }}" class="menu-link">
                        <span>أرشيف الكوبونات</span>
                    </a>
                </li>
            @endcan
            @can('tenants.view')
                <li class="menu-item {{ request()->routeIs('admin.archives.tenants.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.archives.tenants.index') }}" class="menu-link">
                        <span>أرشيف المستأجرين</span>
                    </a>
                </li>
            @endcan
            @can('bills.view')
                <li class="menu-item {{ request()->routeIs('admin.archives.bills.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.archives.bills.index') }}" class="menu-link">
                        <span>أرشيف الفواتير</span>
                    </a>
                </li>
            @endcan
            @can('admin_users.view')
                <li class="menu-item {{ request()->routeIs('admin.archives.users.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.archives.users.index') }}" class="menu-link">
                        <span>أرشيف المشرفين</span>
                    </a>
                </li>
            @endcan
        </ul>
    </li>

    @can('settings.view')
        <li class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
            <a href="{{ route('admin.settings.index') }}" class="menu-link">
                <i class="menu-icon ti ti-settings"></i>
                <span>إعدادات النظام</span>
            </a>
        </li>
    @endcan
</ul>

