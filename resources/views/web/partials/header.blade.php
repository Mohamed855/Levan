<!-- Site Header & Glassmorphism Navbar -->
<header class="site-header" role="banner" aria-label="Navigation bar">
    <div class="container">
        <div class="ic-navbar-floating">
            <!-- Brand Logo -->
            <a href="{{ route('site.index') }}" class="site-logo" aria-label="لوت LOT">
                <img src="{{ asset('static/brand/logos/lot/trans-profile.png') }}" alt="لوت LOT">
            </a>

            <!-- Desktop Navigation Links -->
            <nav class="site-nav" aria-label="التنقل الرئيسي">
                <a href="{{ route('site.index') }}">الرئيسية</a>
                <a href="/#services">المميزات</a>
                <a href="/#pricing">الباقات</a>
                <a href="/#faq">الأسئلة الشائعة</a>
                <a href="/#contact">تواصل معنا</a>
            </nav>

            <!-- Actions: Hamburger on Mobile + Contact on Desktop -->
            <div class="site-nav-actions">
                <button type="button" class="site-hamburger" aria-label="افتح القائمة" aria-expanded="false">
                    <i class="ti ti-menu-2"></i>
                </button>

                <a href="{{ route('site.trial') }}" class="btn-navbar-float" role="button">
                    <span>تجربة مجانية</span>
                    <i class="ti ti-arrow-left"></i>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Mobile Navigation Drawer -->
<div class="site-mobile-drawer" role="dialog" aria-modal="true" aria-label="قائمة التنقل">
    <div class="site-mobile-content">
        <div>
            <div class="d-flex align-items-center justify-content-between mb-4">
                <a href="{{ route('site.index') }}" class="site-logo">
                    <img src="{{ asset('static/brand/logos/lot/trans-profile.png') }}" alt="لوت LOT"
                        style="height: 2.2rem;">
                </a>
                <button type="button" class="site-mobile-close" aria-label="إغلاق القائمة">
                    <i class="ti ti-x"></i>
                </button>
            </div>

            <nav class="site-mobile-nav">
                <a href="{{ route('site.index') }}">الرئيسية</a>
                <a href="/#services">المميزات</a>
                <a href="/#pricing">الباقات والأسعار</a>
                <a href="/#faq">الأسئلة الشائعة</a>
                <a href="/#contact">تواصل معنا</a>
            </nav>
        </div>

        <div class="site-mobile-footer pt-4 border-top">
            <a href="{{ route('site.trial') }}" class="btn-hero-primary w-100 justify-content-center text-center">
                <span>طلب تجربة مجانية</span>
                <i class="ti ti-arrow-left"></i>
            </a>
        </div>
    </div>
</div>
