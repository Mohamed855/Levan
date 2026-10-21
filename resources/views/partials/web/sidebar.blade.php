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
                <a href="{{ route('site.index') }}#home">الرئيسية</a>
                <a href="{{ route('site.index') }}#services">المميزات</a>
                <a href="{{ route('site.index') }}#pricing">الباقات والأسعار</a>
                <a href="{{ route('site.index') }}#faq">الأسئلة الشائعة</a>
                <a href="{{ route('site.index') }}#contact">تواصل معنا</a>
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
