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
                <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 20])</span>
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

        <li class="menu-item @if (Helpers::isActiveRoute('dashboard')) active open @endif">
            <a href="{{ tenant_route_name('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-smart-home"></i>
                <div>لوحة القيادة</div>
            </a>
        </li>


        @canany(['الشركات', 'المحافظ', 'المستخدمين', 'العملاء', 'عملائي', 'توزيع العملاء'])
            <li class="menu-header small">
                <span class="menu-header-text">مركز العمليات</span>
            </li>
            @can('الشركات')
                <li class="menu-item @if (Helpers::isActiveRoute('companies.*')) active @endif">
                    <a href="{{ tenant_route_name('companies.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-building"></i>
                        <div>الشركات</div>
                    </a>
                </li>
            @endcan

            @can('العقود')
                <li class="menu-item @if (Helpers::isActiveRoute('contracts.*')) active @endif">
                    <a href="{{ tenant_route_name('contracts.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-file-certificate"></i>
                        <div>العقود</div>
                    </a>
                </li>
            @endcan

            @can('المحافظ')
                <li class="menu-item @if (Helpers::isActiveRoute('portfolios.*')) active @endif">
                    <a href="{{ tenant_route_name('portfolios.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons ti ti-wallet"></i>
                        <div>المحافظ</div>
                    </a>
                </li>
            @endcan

            @php
                $hasAllCustomers = auth()->user()->can('العملاء');
                $hasMyCustomers = auth()->user()->can('عملائي');
                $hasDistribution = auth()->user()->can('توزيع العملاء');
                $hasUpdateRequest = auth()->user()->can('طلبات تحديث البيانات');
            @endphp

            @if ($hasAllCustomers || $hasMyCustomers || $hasDistribution)
                <li class="menu-item @if (Helpers::isActiveRoute([
                        'customers.index',
                        'customers.my-customers',
                        'customers.distribution.index',
                        'customers.update-requests.index',
                    ])) active open @endif">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons ti ti-users"></i>
                        <div>إدارة العملاء</div>
                    </a>
                    <ul class="menu-sub">
                        @if ($hasAllCustomers)
                            <li class="menu-item @if (Helpers::isActiveRoute('customers.index')) active @endif">
                                <a href="{{ tenant_route_name('customers.index') }}" class="menu-link">
                                    <div>جميع العملاء</div>
                                </a>
                            </li>
                        @endif

                        @if ($hasMyCustomers)
                            <li class="menu-item @if (Helpers::isActiveRoute('customers.my-customers')) active @endif">
                                <a href="{{ tenant_route_name('customers.my-customers') }}" class="menu-link">
                                    <div>عملائي</div>
                                </a>
                            </li>
                        @endif

                        @if ($hasDistribution)
                            <li class="menu-item @if (Helpers::isActiveRoute('customers.distribution.index')) active @endif">
                                <a href="{{ tenant_route_name('customers.distribution.index') }}" class="menu-link">
                                    <div>توزيع العملاء</div>
                                </a>
                            </li>
                        @endif

                        @if ($hasUpdateRequest)
                            <li class="menu-item @if (Helpers::isActiveRoute('customers.update-requests.index')) active @endif">
                                <a href="{{ tenant_route_name('customers.update-requests.index') }}" class="menu-link">
                                    <div>طلبات تحديث البيانات</div>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif


            {{-- المهام --}}
            @canany(['المهام', 'مهامي'])
                <li class="menu-header small">
                    <span class="menu-header-text">إدارة المهام</span>
                </li>

                <li class="menu-item @if (Helpers::isActiveRoute(['tasks.*', 'my-tasks.*'])) active open @endif">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons ti ti-clipboard-list"></i>
                        <div>المهام</div>
                    </a>
                    <ul class="menu-sub">
                        @can('المهام')
                            <li class="menu-item @if (Helpers::isActiveRoute('tasks.*')) active @endif">
                                <a href="{{ tenant_route_name('tasks.index') }}" class="menu-link">
                                    <div>جميع المهام</div>
                                </a>
                            </li>
                        @endcan

                        @can('مهامي')
                            <li class="menu-item @if (Helpers::isActiveRoute('my-tasks.*')) active @endif">
                                <a href="{{ tenant_route_name('my-tasks.index') }}" class="menu-link">
                                    <div>مهامي</div>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany

            {{-- @if ($hasUpdateRequest || $hasAllCustomers)
        <li class="menu-item @if (Helpers::isActiveRoute(['customers.update-requests.index', 'company-letters.index'])) active open @endif">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons ti ti-file-text"></i>
                <div>خدمات العملاء</div>
            </a>
            <ul class="menu-sub">


                @if ($hasAllCustomers)
                <li class="menu-item @if (Helpers::isActiveRoute('company-letters.index')) active @endif">
                    <a href="{{ tenant_route_name('company-letters.index') }}" class="menu-link">
        <div>خطابات الشركات</div>
        </a>
        </li>
        @endif
    </ul>
    </li>
    @endif --}}
        @endcanany


        @canany(['أهداف التحصيل', 'تحصيلاتي', 'تحصيلات المستخدمين'])
            <li class="menu-header small">
                <span class="menu-header-text">إدارة التحصيل</span>
            </li>

            <li class="menu-item
                @if (Helpers::isActiveRoute([
                        'financial-collections.collection-targets.*',
                        'financial-collections.collections.*',
                        'financial-collections.admin-collections.*',
                        'financial-collections.reconciliation.*',
                    ])) active open @endif ">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons ti ti-currency-dollar"></i>
                    <div>التحصيلات</div>
                </a>
                <ul class="menu-sub">
                    @can('أهداف التحصيل')
                        <li class="menu-item @if (Helpers::isActiveRoute('financial-collections.collection-targets.*')) active @endif">
                            <a href="{{ tenant_route_name('financial-collections.collection-targets.index') }}"
                                class="menu-link">
                                <div>أهداف التحصيل</div>
                            </a>
                        </li>
                    @endcan
                    @can(['تحصيلاتي'])
                        <li class="menu-item @if (Helpers::isActiveRoute('financial-collections.collections.*')) active @endif">
                            <a href="{{ tenant_route_name('financial-collections.collections.index') }}" class="menu-link">
                                <div>تحصيلاتي</div>
                            </a>
                        </li>
                    @endcan
                    @can(['تحصيلات المستخدمين'])
                        <li class="menu-item @if (Helpers::isActiveRoute('financial-collections.admin-collections.*')) active @endif">
                            <a href="{{ tenant_route_name('financial-collections.admin-collections.index') }}"
                                class="menu-link">
                                <div>تحصيلات المستخدمين</div>
                            </a>
                        </li>
                    @endcan

                    @can(['مطابقة السدادات'])
                        <li class="menu-item @if (Helpers::isActiveRoute('financial-collections.reconciliation.*')) active @endif">
                            <a href="{{ tenant_route_name('financial-collections.reconciliation.index') }}" class="menu-link">
                                <div>مطابقة السدادات</div>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>
        @endcanany

        @canany([
            'تقارير العملاء',
            'تقارير التحصيل',
            'أرشيف
            الشركات',
            'أرشيف المحافظ',
            'أرشيف العملاء',
            'أرشيف التحصيلات',
            'أرشيف المستخدمين',
            ])
            <li class="menu-header small">
                <span class="menu-header-text">التقــارير والأرشيف</span>
            </li>
            {{-- قائمة "التقارير" --}}
            @canany(['تقارير العملاء', 'تقارير التحصيل'])
                <li class="menu-item
            @if (Helpers::isActiveRoute([
                    'reports.executive.*',
                    'reports.customers.*',
                    'reports.customer_follows.*',
                    'reports.portfolios.*',
                    'reports.companies.*',
                    'reports.financial.*',
                    'reports.employees.*',
                ])) active open @endif ">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons ti ti-report"></i>
                        <div>التقارير</div>
                    </a>
                    <ul class="menu-sub">
                        @can('التقارير التنفيذية')
                            <li class="menu-item @if (Helpers::isActiveRoute('reports.executive.*')) active @endif">
                                <a href="{{ tenant_route_name('reports.executive.index') }}" class="menu-link">
                                    <div>التقارير التنفيذية</div>
                                </a>
                            </li>
                        @endcan

                        @can('تقارير العملاء')
                            <li class="menu-item @if (Helpers::isActiveRoute('reports.customers.*')) active @endif">
                                <a href="{{ tenant_route_name('reports.customers.index') }}" class="menu-link">
                                    <div>تقارير العملاء</div>
                                </a>
                            </li>
                        @endcan

                        @can('تقارير العملاء')
                            <li class="menu-item @if (Helpers::isActiveRoute('reports.customer_follows.*')) active @endif">
                                <a href="{{ tenant_route_name('reports.customer_follows.index') }}" class="menu-link">
                                    <div>تقارير متابعات العملاء</div>
                                </a>
                            </li>
                        @endcan

                        @can('تقارير التحصيل')
                            <li class="menu-item @if (Helpers::isActiveRoute('reports.financial.*')) active @endif">
                                <a href="{{ tenant_route_name('reports.financial.index') }}" class="menu-link">
                                    <div>تقارير التحصيل</div>
                                </a>
                            </li>
                        @endcan

                        @can('تقارير الشركات')
                            <li class="menu-item @if (Helpers::isActiveRoute('reports.companies.*')) active @endif">
                                <a href="{{ tenant_route_name('reports.companies.index') }}" class="menu-link">
                                    <div>تقارير الشركات</div>
                                </a>
                            </li>
                        @endcan

                        @can('تقارير أداء الموظفين')
                            <li class="menu-item @if (Helpers::isActiveRoute('reports.employees.*')) active @endif">
                                <a href="{{ tenant_route_name('reports.employees.index') }}" class="menu-link">
                                    <div>تقارير أداء الموظفين</div>
                                </a>
                            </li>
                        @endcan

                    </ul>
                </li>
            @endcanany


            @canany(['أرشيف الشركات', 'أرشيف المحافظ', 'أرشيف العملاء', 'أرشيف التحصيلات', 'أرشيف المستخدمين'])
                <li class="menu-item
            @if (Helpers::isActiveRoute([
                    'archive.companies.*',
                    'archive.portfolios.*',
                    'archive.customers.*',
                    'archive.contracts.*',
                    'archive.users.*',
                ])) active open @endif ">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon tf-icons ti ti-archive"></i>
                        <div>الأرشيف</div>
                    </a>
                    <ul class="menu-sub">
                        @can('أرشيف الشركات')
                            <li class="menu-item @if (Helpers::isActiveRoute('archive.companies.*')) active @endif">
                                <a href="{{ tenant_route_name('archive.companies.index') }}" class="menu-link">
                                    <div>الشركات</div>
                                </a>
                            </li>
                        @endcan

                        @can('أرشيف المحافظ')
                            <li class="menu-item @if (Helpers::isActiveRoute('archive.contracts.*')) active @endif">
                                <a href="{{ tenant_route_name('archive.contracts.index') }}" class="menu-link">
                                    <div>العقود</div>
                                </a>
                            </li>
                        @endcan

                        @can('أرشيف المحافظ')
                            <li class="menu-item @if (Helpers::isActiveRoute('archive.portfolios.*')) active @endif">
                                <a href="{{ tenant_route_name('archive.portfolios.index') }}" class="menu-link">
                                    <div>المحافظ</div>
                                </a>
                            </li>
                        @endcan

                        @can('أرشيف العملاء')
                            <li class="menu-item @if (Helpers::isActiveRoute('archive.customers.*')) active @endif">
                                <a href="{{ tenant_route_name('archive.customers.index') }}" class="menu-link">
                                    <div>العملاء</div>
                                </a>
                            </li>
                        @endcan

                        @can('أرشيف المستخدمين')
                            <li class="menu-item @if (Helpers::isActiveRoute('archive.users.*')) active @endif">
                                <a href="{{ tenant_route_name('archive.users.index') }}" class="menu-link">
                                    <div>المستخدمين</div>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcanany
        @endcanany

        <li class="menu-header small">
            <span class="menu-header-text">الدعم الفني</span>
        </li>
        <li class="menu-item @if (Helpers::isActiveRoute('tickets.index')) active @endif">
            <a href="{{ tenant_route_name('tickets.index') }}" class="menu-link">
                <i class="menu-icon tf-icons ti ti-headset"></i>
                <div>الدعم الفني</div>
            </a>
        </li>

    </ul>
</aside>
