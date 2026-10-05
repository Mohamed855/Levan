<!-- Navbar -->
<header class="ic-navbar fixed right-0 top-4 z-40 flex w-full items-center bg-transparent" role="banner"
    aria-label="Navigation bar">
    <div class="container">
        <div class="ic-navbar-floating mx-auto flex items-center justify-between px-5 py-4 rounded-full shadow-lg"
            style="background-color: #e3d8d1;">
            <!-- Logo (right) -->
            <a href="{{ route('site.index') }}" class="ic-navbar-logo block text-primary-color" aria-label="ليفـــان">
                <img src="{{ asset('static/brand/logos/Levan-logo.svg') }}" data-default-logo="{{ asset('static/brand/logos/Levan-logo.svg') }}"
                    data-sticky-logo="{{ asset('static/brand/logos/Levan-logo.svg') }}"
                    data-light-logo="{{ asset('static/brand/logos/Levan-logo.svg') }}"
                    data-dark-logo="{{ asset('static/brand/logos/Levan-logo-light.svg') }}" class="h-10" alt="ليفـــان">
            </a>

            <!-- Left side: Theme toggle + Contact button -->
            <div class="flex items-center gap-3">
                {{-- <!-- Theme Toggle -->
                <button type="button" class="inline-flex items-center text-primary text-[22px]/none"
                    aria-label="Switch theme" data-web-trigger="web-theme"></button> --}}

                <!-- Contact button -->
                <a href="#contact" id="navbar-contact-btn"
                    class="btn-navbar-float px-5 py-2 rounded-full text-sm font-semibold text-white transition-all duration-200 hover:text-[#e3d8d1]"
                    style="background-color: var(--color-primary);" role="button">تواصل معنا</a>
            </div>
        </div>
    </div>
</header>

{{-- ============================================================
navbarMenu — Old full navigation (commented for future use)
============================================================
<nav id="navbarMenu" class="ic-navbar-collapse ...">
    <ul>
        <li><a href="#home" class="ic-page-scroll ...">الرئيسية</a></li>
        <li><a href="#services" class="ic-page-scroll ...">المميزات</a></li>
        <li><a href="#pricing" class="ic-page-scroll ...">الباقات و الاسعار</a></li>
        <li><a href="#portfolios" class="ic-page-scroll ...">المحافظ</a></li>
        <li><a href="#clients" class="ic-page-scroll ...">عملائنا</a></li>
        <li><a href="#faq" class="ic-page-scroll ...">الأسئلة الشائعة</a></li>
        <li><a href="#contact" class="ic-page-scroll ...">تواصل معنا</a></li>
    </ul>
</nav>
--}}
