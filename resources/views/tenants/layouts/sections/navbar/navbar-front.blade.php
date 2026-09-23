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
                    <li class="nav-item">
                        <a class="nav-link fw-medium" aria-current="page" href="{{ url('/') }}">الرئيسية</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="#landingFeatures">المميزات</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="#landingFAQ">الأسئلة الشائعة</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="#landingContact">تواصل معنا</a>
                    </li>
                    {{-- <li class="nav-item mega-dropdown {{ $activeClass }}">
            <a href="javascript:void(0);"
              class="nav-link dropdown-toggle navbar-ex-14-mega-dropdown mega-dropdown fw-medium" aria-expanded="false"
              data-bs-toggle="mega-dropdown" data-trigger="hover">
              <span>الخدمات</span>
            </a>
            <div class="dropdown-menu p-4 p-xl-8">
              <div class="row gy-4">
                <div class="col-12 col-lg">
                  <div class="h6 d-flex align-items-center mb-3 mb-lg-5">
                    <div class="avatar flex-shrink-0 me-3">
                      <span class="avatar-initial rounded bg-label-primary"><i
                          class='ti ti-layout-grid ti-lg'></i></span>
                    </div>
                    <span class="ps-1">أقسام المنصة</span>
                  </div>
                  <ul class="nav flex-column">
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{url('front-pages/landing')}}#landingFeatures">
                        <i class='ti ti-circle me-1'></i>
                        <span>مميزات المنصة</span>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{url('front-pages/landing')}}#landingFAQ">
                        <i class='ti ti-circle me-1'></i>
                        <span>الأسئلة الشائعة</span>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{url('front-pages/landing')}}#landingCTA">
                        <i class='ti ti-circle me-1'></i>
                        <span>ابدأ التجربة المجانية</span>
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{url('front-pages/landing')}}#landingContact">
                        <i class='ti ti-circle me-1'></i>
                        <span>تواصل معنا</span>
                      </a>
                    </li>
                  </ul>
                </div>
                <div class="col-12 col-lg">
                  <div class="h6 d-flex align-items-center mb-3 mb-lg-5">
                    <div class="avatar flex-shrink-0 me-3">
                      <span class="avatar-initial rounded bg-label-success"><i
                          class='ti ti-user-check ti-lg'></i></span>
                    </div>
                    <span class="ps-1">حساب المؤسسة</span>
                  </div>
                  <ul class="nav flex-column">
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{url('/auth/login-cover')}}" target="_blank">
                        <i class='ti ti-circle me-1'></i>
                        دخول المؤسسات
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{url('/auth/register-multisteps')}}"
                        target="_blank">
                        <i class='ti ti-circle me-1'></i>
                        تسجيل مؤسسة جديدة
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{url('/auth/forgot-password-cover')}}"
                        target="_blank">
                        <i class='ti ti-circle me-1'></i>
                        استرداد كلمة المرور
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{url('/auth/verify-email-cover')}}" target="_blank">
                        <i class='ti ti-circle me-1'></i>
                        تفعيل الحساب
                      </a>
                    </li>
                  </ul>
                </div>
                <div class="col-12 col-lg">
                  <div class="h6 d-flex align-items-center mb-3 mb-lg-5">
                    <div class="avatar flex-shrink-0 me-3">
                      <span class="avatar-initial rounded bg-label-info"><i class='ti ti-file-text ti-lg'></i></span>
                    </div>
                    <span class="ps-1">الموارد والدعم</span>
                  </div>
                  <ul class="nav flex-column">
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{url('/front-pages/user-guide')}}" target="_blank">
                        <i class='ti ti-circle me-1'></i>
                        دليل المستخدم
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{url('/front-pages/technical-support')}}"
                        target="_blank">
                        <i class='ti ti-circle me-1'></i>
                        الدعم الفني
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{url('/front-pages/privacy-policy')}}"
                        target="_blank">
                        <i class='ti ti-circle me-1'></i>
                        سياسة الخصوصية
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link mega-dropdown-link" href="{{url('/front-pages/terms-of-service')}}"
                        target="_blank">
                        <i class='ti ti-circle me-1'></i>
                        شروط الخدمة
                      </a>
                    </li>
                  </ul>
                </div>
                <div class="col-lg-4 d-none d-lg-block">
                  <div class="bg-body nav-img-col p-2">
                    <img src="{{asset('assets/img/front-pages/misc/nav-item-col-img.png')}}" alt="منصة ليفـان"
                      class="w-100">
                  </div>
                </div>
              </div>
            </div>
          </li> --}}
                </ul>
            </div>
            <div class="landing-menu-overlay d-lg-none"></div>
            <!-- Menu wrapper: End -->
            <!-- Toolbar: Start -->
            <ul class="navbar-nav flex-row align-items-center ms-auto">
                @if ($configData['hasCustomizer'] == true)
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
                @endif
                <!-- navbar button: Start -->
                <li>
                    <a href="{{ tenant_route_name('login') }}" class="btn btn-primary" target="_blank"><span
                            class="tf-icons ti ti-login scaleX-n1-rtl me-md-1"></span><span
                            class="d-none d-md-block">دخول
                            المنصة</span></a>
                </li>
                <!-- navbar button: End -->
            </ul>
            <!-- Toolbar: End -->
        </div>
    </div>
</nav>
<!-- Navbar: End -->
