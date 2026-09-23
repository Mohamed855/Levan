@php
    use Illuminate\Support\Facades\Route;
    $currentRouteName = Route::currentRouteName();
    $activeRoutes = ['front-pages-pricing', 'front-pages-demo', 'front-pages-quote', 'front-pages-help-center'];
    $activeClass = in_array($currentRouteName, $activeRoutes) ? 'active' : '';
@endphp
<!-- Navbar: Start -->
<nav class="layout-navbar shadow-none py-0">
    <div class="container">
        <div class="navbar navbar-expand-lg landing-navbar px-3 px-md-8">
            <!-- Menu logo wrapper: Start -->
            <div class="navbar-brand app-brand demo d-flex py-0 py-lg-2 me-4 me-xl-8">
                <!-- Mobile menu toggle: Start-->
                <button class="navbar-toggler border-0 px-0 me-4" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <i class="ti ti-menu-2 ti-lg align-middle text-heading fw-medium"></i>
                </button>
                <!-- Mobile menu toggle: End-->
                <a href="{{ url('/') }}" class="app-brand-link">
                    <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 20, 'withbg' => 'fill: #fff;'])</span>
                    <span class="app-brand-text demo menu-text fw-bold ms-2 ps-1">ليفـان</span>
                </a>
            </div>
            <!-- Menu logo wrapper: End -->
            <!-- Menu wrapper: Start -->
            <div class="collapse navbar-collapse landing-nav-menu" id="navbarSupportedContent">
                <button class="navbar-toggler border-0 text-heading position-absolute end-0 top-0 scaleX-n1-rtl"
                    type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="ti ti-x ti-lg"></i>
                </button>
                <ul class="navbar-nav me-auto">
                    {{-- <li class="nav-item">
                        <a class="nav-link fw-medium" aria-current="page" href="{{ route('site.home') }}">الرئيسية</a>
                    </li> --}}
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="{{ route('site.home') }}#landingFeatures">المميزات</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="{{ route('site.home') }}#landingPricing">الباقات و
                            الاسعار</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="{{ route('site.home') }}#landingFAQ">الأسئلة الشائعة</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="{{ route('site.home') }}#landingContact">تواصل معنا</a>
                    </li>

                </ul>
            </div>
            <div class="landing-menu-overlay d-lg-none"></div>
            <!-- Menu wrapper: End -->
            <!-- Toolbar: Start -->
            <ul class="navbar-nav flex-row align-items-center ms-auto">
                {{-- @if ($configData['hasCustomizer'] == true)
                    <!-- Style Switcher -->
                    <li class="nav-item dropdown-style-switcher dropdown me-2 me-xl-1">
                        <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);"
                            data-bs-toggle="dropdown">
                            <i class='ti ti-lg'></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-styles">
                            <li>
                                <a class="dropdown-item" href="javascript:void(0);" data-theme="light">
                                    <span class="align-middle"><i class='ti ti-sun me-3'></i>فاتح</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="javascript:void(0);" data-theme="dark">
                                    <span class="align-middle"><i class="ti ti-moon-stars me-3"></i>داكن</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="javascript:void(0);" data-theme="system">
                                    <span class="align-middle"><i
                                            class="ti ti-device-desktop-analytics me-3"></i>النظام</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <!-- / Style Switcher-->
                @endif --}}
                <!-- navbar button: Start -->

                <li>
                    <a href="{{ route('site.trial') }}" class="btn btn-primary">
                        جرب المنصة الان
                    </a>
                </li>

                <!-- navbar button: End -->
            </ul>
            <!-- Toolbar: End -->
        </div>
    </div>
</nav>
<!-- Navbar: End -->
