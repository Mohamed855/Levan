<!-- Navbar -->
<header class="ic-navbar absolute right-0 top-0 z-40 flex w-full items-center bg-transparent" role="banner"
    aria-label="Navigation bar">
    <div class="container">
        <div class="ic-navbar-container relative -mx-5 flex items-center justify-end">
            <div class="w-60 lg:w-56 max-w-full px-5">
                <a href="{{ route('site.index') }}" class="ic-navbar-logo block w-full text-primary-color"
                    aria-label="ليفـــان">
                    <img src="{{ asset('assets/shared/images/logo-full.png') }}"
                        data-default-logo="{{ asset('assets/shared/images/logo-full.png') }}"
                        data-sticky-logo="{{ asset('assets/shared/images/logo-full-black.png') }}" class="h-16"
                        alt="ليفـــان">
                </a>
            </div>
            <div class="flex w-full items-center justify-around px-5">
                <div>
                    <button type="button"
                        class="ic-navbar-toggler absolute left-4 top-1/2 block -translate-y-1/2 rounded-md px-3 py-[6px] text-[22px]/none text-primary-color ring-primary focus:ring-2 lg:hidden"
                        data-web-toggle="navbar-collapse" data-web-target="navbarMenu" aria-expanded="false"
                        aria-label="Toggle navigation menu">
                        <i class="lni lni-menu"></i>
                    </button>

                    <nav id="navbarMenu"
                        class="ic-navbar-collapse absolute left-4 top-[70px] w-full max-w-[250px] rounded-lg hidden bg-primary-light-1 py-5 shadow-lg dark:bg-primary-dark-1 lg:static lg:block lg:w-full lg:max-w-full lg:bg-transparent lg:py-0 lg:shadow-none dark:lg:bg-transparent xl:px-6">
                        <ul class="block lg:flex" role="menu" aria-label="Navigation menu">
                            <li class="group relative">
                                <a href="{{ route('site.index') }}#home"
                                    class="ic-page-scroll mx-8 flex py-2 text-base font-medium text-body-light-12 group-hover:text-primary dark:text-body-dark-12 lg:mr-0 lg:inline-flex lg:px-0 lg:py-6 lg:text-primary-color lg:dark:text-primary-color lg:group-hover:text-primary-color lg:group-hover:opacity-70 active"
                                    role="menuitem">الرئيسية</a>
                            </li>

                            <li class="group relative">
                                <a href="{{ route('site.index') }}#services"
                                    class="ic-page-scroll mx-8 flex py-2 text-base font-medium text-body-light-12 group-hover:text-primary dark:text-body-dark-12 lg:mr-0 lg:inline-flex lg:px-0 lg:py-6 lg:text-primary-color lg:dark:text-primary-color lg:group-hover:text-primary-color lg:group-hover:opacity-70"
                                    role="menuitem">المميزات</a>
                            </li>

                            <li class="group relative">
                                <a href="{{ route('site.index') }}#pricing"
                                    class="ic-page-scroll mx-8 flex py-2 text-base font-medium text-body-light-12 group-hover:text-primary dark:text-body-dark-12 lg:mr-0 lg:inline-flex lg:px-0 lg:py-6 lg:text-primary-color lg:dark:text-primary-color lg:group-hover:text-primary-color lg:group-hover:opacity-70"
                                    role="menuitem">الباقات و الاسعار</a>
                            </li>
                            <li class="group relative">
                                <a href="{{ route('site.index') }}#portfolios"
                                    class="ic-page-scroll mx-8 flex py-2 text-base font-medium text-body-light-12 group-hover:text-primary dark:text-body-dark-12 lg:mr-0 lg:inline-flex lg:px-0 lg:py-6 lg:text-primary-color lg:dark:text-primary-color lg:group-hover:text-primary-color lg:group-hover:opacity-70"
                                    role="menuitem">المحافظ</a>
                            </li>
                            <li class="group relative">
                                <a href="{{ route('site.index') }}#clients"
                                    class="ic-page-scroll mx-8 flex py-2 text-base font-medium text-body-light-12 group-hover:text-primary dark:text-body-dark-12 lg:mr-0 lg:inline-flex lg:px-0 lg:py-6 lg:text-primary-color lg:dark:text-primary-color lg:group-hover:text-primary-color lg:group-hover:opacity-70"
                                    role="menuitem">عملائنا</a>
                            </li>


                            <li class="group relative">
                                <a href="{{ route('site.index') }}#faq"
                                    class="ic-page-scroll mx-8 flex py-2 text-base font-medium text-body-light-12 group-hover:text-primary dark:text-body-dark-12 lg:mr-0 lg:inline-flex lg:px-0 lg:py-6 lg:text-primary-color lg:dark:text-primary-color lg:group-hover:text-primary-color lg:group-hover:opacity-70"
                                    role="menuitem">الأسئلة الشائعة</a>
                            </li>

                            <li class="group relative">
                                <a href="{{ route('site.index') }}#contact"
                                    class="ic-page-scroll mx-8 flex py-2 text-base font-medium text-body-light-12 group-hover:text-primary dark:text-body-dark-12 lg:mr-0 lg:inline-flex lg:px-0 lg:py-6 lg:text-primary-color lg:dark:text-primary-color lg:group-hover:text-primary-color lg:group-hover:opacity-70"
                                    role="menuitem">تواصل معنا</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <div class="flex items-center justify-end pr-[52px] lg:pr-0">
                    <button type="button" class="inline-flex items-center text-primary-color text-[24px]/none"
                        aria-label="Switch theme" data-web-trigger="web-theme"></button>
                    <div class="hidden sm:flex">
                        <a href="{{ route('site.trial') }}"
                            class="btn-navbar mr-5 px-5 py-3 rounded-md bg-primary-color bg-opacity-20 text-base font-medium text-primary-color hover:bg-opacity-100 hover:text-primary"
                            role="button">اطلب النسخة التجريبية</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
