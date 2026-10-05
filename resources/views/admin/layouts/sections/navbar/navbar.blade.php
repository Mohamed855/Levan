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
        <div class="navbar-nav align-items-center">
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
            <!-- Search (horizontal) -->
            <li class="nav-item navbar-search-wrapper">
                <a class="nav-link btn btn-text-secondary btn-icon rounded-pill search-toggler"
                    href="javascript:void(0);">
                    <i class="ti ti-search ti-md"></i>
                </a>
            </li>
            <!-- /Search -->
        @endif

        <li class="nav-item dropdown me-2 me-xl-2">
            <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
                title="الموقع الرئيسي" data-bs-toggle="tooltip" data-bs-placement="bottom"
                href="{{ route('site.index') }}">
                <i class="ti ti-home ti-md"></i>
            </a>


        </li>

        <li class="nav-item dropdown me-2 me-xl-2">
            <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
                href="javascript:void(0);" data-bs-toggle="dropdown">
                <i class="ti ti-settings ti-md"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 320px;">
                <div class="row g-2">
                    @can('system.settings')
                        <div class="col-12">
                            <a href="{{ route('admin.settings.index') }}"
                                class="d-block p-3 text-center border rounded hover-bg-light text-decoration-none">
                                <div class="avatar avatar-md mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="ti ti-settings ti-lg"></i>
                                    </span>
                                </div>
                                <small class="text-muted fw-medium">إعدادات النظام</small>
                            </a>
                        </div>
                    @endcan


                    @can('roles.view')
                        <div class="col-6">
                            <a href="{{ route('admin.roles.index') }}"
                                class="d-block p-3 text-center border rounded hover-bg-light text-decoration-none">
                                <div class="avatar avatar-md mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-label-info">
                                        <i class="ti ti-shield-lock ti-lg"></i>
                                    </span>
                                </div>
                                <small class="text-muted fw-medium">إدارة الصلاحيات</small>
                            </a>
                        </div>
                    @endcan


                    @can('activity_log.view')
                        <div class="col-6">
                            <a href="{{ route('admin.activity-logs.index') }}"
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


                    @can('domain_extension_prices.settings')
                        <div class="col-6">
                            <a href="{{ route('admin.settings.domains.index') }}"
                                class="d-block p-3 text-center border rounded hover-bg-light text-decoration-none">
                                <div class="avatar avatar-md mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-secondary">
                                        <i class="ti ti-world ti-lg"></i>
                                    </span>
                                </div>
                                <small class="text-muted fw-medium"> اسعار الامتدادات </small>
                            </a>
                        </div>
                    @endcan


                    @can('bank_settings.view')
                        <div class="col-6">
                            <a href="{{ route('admin.settings.banks.index') }}"
                                class="d-block p-3 text-center border rounded hover-bg-light text-decoration-none">
                                <div class="avatar avatar-md mx-auto mb-2">
                                    <span class="avatar-initial rounded bg-secondary">
                                        <i class="ti ti-building-bank ti-lg"></i>
                                    </span>
                                </div>
                                <small class="text-muted fw-medium">إعدادات البنوك</small>
                            </a>
                        </div>
                    @endcan


                    <div class="col-12">
                        <div class="border rounded p-2">
                            <div class="d-flex align-items-center mb-2 px-2">
                                {{-- <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded bg-label-dark">
                                            <i class="ti ti-report"></i>
                                        </span>
                                    </div> --}}
                                <small class="fw-medium text-black">إدارة المحتوى</small>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="{{ route('admin.content_management.client.index') }}"
                                        class="d-block p-2 text-center border rounded hover-bg-light text-decoration-none">
                                        <small class="text-muted">العملاء</small>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="{{ route('admin.content_management.portfolio.index') }}"
                                        class="d-block p-2 text-center border rounded hover-bg-light text-decoration-none">
                                        <small class="text-muted">المحافظ</small>
                                    </a>
                                </div>
                                {{-- <div class="col-6">
                                        <a href="{{ route('admin.archives.coupons.index') }}"
                                            class="d-block p-2 text-center border rounded hover-bg-light text-decoration-none">
                                            <small class="text-muted">الكوبونات</small>
                                        </a>
                                    </div>
                                    --}}
                            </div>
                        </div>
                    </div>


                    <div class="col-12">
                        <div class="border rounded p-2">
                            <div class="d-flex align-items-center mb-2 px-2">
                                {{-- <div class="avatar avatar-sm me-2">
                                        <span class="avatar-initial rounded bg-label-dark">
                                            <i class="ti ti-report"></i>
                                        </span>
                                    </div> --}}
                                <small class="fw-medium text-black">المحذوفة</small>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="{{ route('admin.archives.index') }}"
                                        class="d-block p-2 text-center border rounded hover-bg-light text-decoration-none">
                                        <small class="text-muted">المستخدمين</small>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="{{ route('admin.archives.plans.index') }}"
                                        class="d-block p-2 text-center border rounded hover-bg-light text-decoration-none">
                                        <small class="text-muted">الباقات</small>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="{{ route('admin.archives.coupons.index') }}"
                                        class="d-block p-2 text-center border rounded hover-bg-light text-decoration-none">
                                        <small class="text-muted">الكوبونات</small>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="{{ route('admin.archives.tenants.index') }}"
                                        class="d-block p-2 text-center border rounded hover-bg-light text-decoration-none">
                                        <small class="text-muted">الشركات</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </li>


        <!-- Notification -->
        <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-2 me-xl-2">
            <a class="nav-link btn btn-text-secondary btn-icon rounded-pill dropdown-toggle hide-arrow"
                href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                aria-expanded="false">
                <span class="position-relative">
                    <i class="ti ti-bell ti-md"></i>
                    <span class="badge rounded-pill bg-danger badge-dot badge-notifications border"></span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end p-0" style="min-width: 350px;">
                <li class="dropdown-menu-header border-bottom">
                    <div class="dropdown-header d-flex align-items-center py-3">
                        <h6 class="mb-0 me-auto fw-bold">الإشعارات</h6>
                        <div class="d-flex align-items-center h6 mb-0">
                            <span class="badge bg-label-primary me-2"> جديد</span>
                            <a href="javascript:void(0)" class="btn btn-text-secondary rounded-pill btn-icon"
                                data-bs-toggle="tooltip" data-bs-placement="top" title="تحديد الكل كمقروء">
                                <i class="ti ti-mail-opened text-heading"></i>
                            </a>
                        </div>
                    </div>
                </li>


                <li class="dropdown-notifications-list scrollable-container overflow-auto" style="max-height: 300px;">
                    <ul class="list-group list-group-flush">
                        <!-- إشعار جديد 1 -->
                        {{-- <li class="list-group-item list-group-item-action dropdown-notifications-item">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar">
                                        <span class="avatar-initial rounded-circle bg-label-success">
                                            <i class="ti ti-check"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="small mb-1 fw-semibold">تم تحصيل مبلغ 15,000 ريال بنجاح</h6>
                                    <p class="mb-1 small text-muted">من عميل: شركة الرياض للتجارة</p>
                                    <small class="text-muted">منذ 5 دقائق</small>
                                </div>
                                <div class="flex-shrink-0 dropdown-notifications-actions">
                                    <span class="badge badge-dot bg-primary"></span>
                                    <a href="javascript:void(0)" class="dropdown-notifications-archive ms-2">
                                        <span class="ti ti-x"></span>
                                    </a>
                                </div>
                            </div>
                        </li> --}}


                    </ul>
                </li>


                <li class="border-top">
                    <div class="d-grid p-4">
                        <a class="btn btn-primary btn-sm d-flex" href="javascript:void(0);">
                            <small class="align-middle">عرض كل الإشعارات</small>
                        </a>
                    </div>
                </li>
            </ul>
        </li>
        <!-- /Notification -->


        {{-- @if ($configData['hasCustomizer'] === true)
            <!-- Style Switcher -->
            <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-2">
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
                    <img src="{{ Auth::user()->profile_photo_url ?? asset('static/brand/logos/logo.svg') }}"
                        alt="{{ Auth::user()->name }}" class="rounded-circle" width="40" height="40">
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item mt-0" href="#">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-online me-2">
                                <img src="{{ Auth::user()->profile_photo_url ?? asset('static/brand/logos/logo.svg') }}"
                                    alt="{{ Auth::user()->name }}" class="rounded-circle" width="40"
                                    height="40">
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">{{ Auth::user()->name }}</h6>
                                <small class="text-muted">{{ Auth::user()->getRoleNames()->implode(', ') }}</small>
                            </div>
                        </div>
                    </a>
                </li>
                {{-- <li>
                    <div class="dropdown-divider my-1 mx-n2"></div>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('admin.users.profile') }}">
                            <i class="ti ti-user me-3 ti-md"></i>
                            <span class="align-middle">الملف الشخصي</span>
                            </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.users.password.change') }}">
                            <i class="ti ti-settings me-3 ti-md"></i>
                            <span class="align-middle">تغيير كلمة المرور</span>
                        </a>
                    </li> --}}


                <li>
                    <div class="dropdown-divider my-1 mx-n2"></div>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('admin.two-factor.index') }}">
                        <i class="ti ti-shield-lock me-3 ti-md"></i>
                        <span class="align-middle">المصادقة الثنائية</span>
                    </a>
                </li>


                <li>
                    <div class="dropdown-divider my-1 mx-n2"></div>
                </li>
                <li>
                    <div class="d-grid px-2 pt-2 pb-1">

                        {{-- ← زر بدون onclick --}}
                        <button type="button" id="logout-btn"
                            class="btn btn-sm btn-danger d-flex align-items-center justify-content-center">
                            <small class="align-middle">تسجيل الخروج</small>
                            <i class="ti ti-logout ms-2 ti-14px"></i>
                        </button>

                        <form id="logout-form" method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                        </form>
                    </div>
                </li>

                <script>
                    document.getElementById('logout-btn')
                        .addEventListener('click', function() {
                            document.getElementById('logout-form').submit();
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
