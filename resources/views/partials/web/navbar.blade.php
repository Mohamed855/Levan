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
                <a href="{{ route('site.index') }}#home">الرئيسية</a>
                <a href="{{ route('site.index') }}#services">المميزات</a>
                <a href="{{ route('site.index') }}#pricing">الباقات</a>
                <a href="{{ route('site.index') }}#faq">الأسئلة الشائعة</a>
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