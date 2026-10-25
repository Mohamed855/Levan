<!-- Site Header & Glassmorphism Navbar -->
<header class="site-header" role="banner" aria-label="Navigation bar">
    <div class="container">
        <div class="ic-navbar-floating">
            <!-- Brand Logo -->
            <a href="<?php echo e(route('site.index')); ?>" class="site-logo" aria-label="لوت LOT">
                <img src="<?php echo e(asset('static/brand/logos/lot/trans-profile.png')); ?>" alt="لوت LOT">
            </a>

            <!-- Desktop Navigation Links -->
            <nav class="site-nav" aria-label="التنقل الرئيسي">
                <a href="<?php echo e(route('site.index')); ?>#home">الرئيسية</a>
                <a href="<?php echo e(route('site.index')); ?>#services">المميزات</a>
                <a href="<?php echo e(route('site.index')); ?>#pricing">الباقات</a>
                <a href="<?php echo e(route('site.index')); ?>#faq">الأسئلة الشائعة</a>
            </nav>

            <!-- Actions: Hamburger on Mobile + Contact on Desktop -->
            <div class="site-nav-actions">
                <button type="button" class="site-hamburger" aria-label="افتح القائمة" aria-expanded="false">
                    <i class="ti ti-menu-2"></i>
                </button>

                <a href="<?php echo e(route('site.trial')); ?>" class="btn-navbar-float" role="button">
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
                <a href="<?php echo e(route('site.index')); ?>" class="site-logo">
                    <img src="<?php echo e(asset('static/brand/logos/lot/trans-profile.png')); ?>" alt="لوت LOT"
                        style="height: 2.2rem;">
                </a>
                <button type="button" class="site-mobile-close" aria-label="إغلاق القائمة">
                    <i class="ti ti-x"></i>
                </button>
            </div>

            <nav class="site-mobile-nav">
                <a href="<?php echo e(route('site.index')); ?>#home">الرئيسية</a>
                <a href="<?php echo e(route('site.index')); ?>#services">المميزات</a>
                <a href="<?php echo e(route('site.index')); ?>#pricing">الباقات والأسعار</a>
                <a href="<?php echo e(route('site.index')); ?>#faq">الأسئلة الشائعة</a>
                <a href="<?php echo e(route('site.index')); ?>#contact">تواصل معنا</a>
            </nav>
        </div>

        <div class="site-mobile-footer pt-4 border-top">
            <a href="<?php echo e(route('site.trial')); ?>" class="btn-hero-primary w-100 justify-content-center text-center">
                <span>طلب تجربة مجانية</span>
                <i class="ti ti-arrow-left"></i>
            </a>
        </div>
    </div>
</div>
<?php /**PATH D:\Levan\resources\views/web/layouts/header.blade.php ENDPATH**/ ?>