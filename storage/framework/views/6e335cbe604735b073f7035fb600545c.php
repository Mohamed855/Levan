<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'logoUrl' => null,
    'formTitle' => '',
    'formSubtitle' => '',
    'status' => null,
    'formMaxWidth' => '440px',
    'bgImage' => asset('static/auth/backgrounds/tenant-login-bg.jpg'),
    'mediaTagline' => 'شريككم في تطوير الفرص العقارية',
    'mediaTitle' => "منصة رقمية متكاملة\nلإدارة المزادات العقارية",
    'mediaDesc' => 'نساعد شركاءنا على إدارة المزادات بكفاءة من خلال أدوات ذكية ومخرجات تسويقية احترافية.',
    'features' => [['icon' => 'ti ti-chart-bar', 'text' => 'إدارة مزادات أكثر كفاءة'], ['icon' => 'ti ti-file-text', 'text' => 'مخرجات تسويقية جاهزة للاستخدام'], ['icon' => 'ti ti-shield-check', 'text' => 'بيانات دقيقة وموثوقة']],
    'showFooter' => true,
    'backUrl' => null,
    'backText' => 'العودة لتسجيل الدخول',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'logoUrl' => null,
    'formTitle' => '',
    'formSubtitle' => '',
    'status' => null,
    'formMaxWidth' => '440px',
    'bgImage' => asset('static/auth/backgrounds/tenant-login-bg.jpg'),
    'mediaTagline' => 'شريككم في تطوير الفرص العقارية',
    'mediaTitle' => "منصة رقمية متكاملة\nلإدارة المزادات العقارية",
    'mediaDesc' => 'نساعد شركاءنا على إدارة المزادات بكفاءة من خلال أدوات ذكية ومخرجات تسويقية احترافية.',
    'features' => [['icon' => 'ti ti-chart-bar', 'text' => 'إدارة مزادات أكثر كفاءة'], ['icon' => 'ti ti-file-text', 'text' => 'مخرجات تسويقية جاهزة للاستخدام'], ['icon' => 'ti ti-shield-check', 'text' => 'بيانات دقيقة وموثوقة']],
    'showFooter' => true,
    'backUrl' => null,
    'backText' => 'العودة لتسجيل الدخول',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    use App\Helpers\Tenants\Settings\SettingsHelper;

    if (!$logoUrl) {
        $tenantLogo =
            class_exists(SettingsHelper::class) && function_exists('tenant') && tenant()
                ? SettingsHelper::get('image')
                : null;
        $logoUrl = $tenantLogo ? tasset($tenantLogo) : asset('static/brand/logos/lot/trans-profile.png');
    }
?>

<div class="auth-split-container">
    <!-- Right Side - Form -->
    <div class="auth-form-side">
        <!-- Brand Watermark -->
        <img class="auth-brand-watermark" src="<?php echo e(asset('static/brand/logos/lot/trans-icon.png')); ?>" alt="" aria-hidden="true">

        <div class="auth-form-inner" style="max-width: <?php echo e($formMaxWidth); ?>;">
            <div class="auth-logo-wrapper">
                <img src="<?php echo e($logoUrl); ?>" alt="Logo" class="auth-logo-img">
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($formTitle): ?>
                <h2 class="auth-form-title"><?php echo e($formTitle); ?></h2>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($formSubtitle): ?>
                <p class="auth-form-subtitle"><?php echo e($formSubtitle); ?></p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($status || session('status')): ?>
                <div class="alert alert-success py-2 px-3 mb-4 text-center" role="alert">
                    <?php echo e($status ?? session('status')); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
                <div class="alert alert-success py-2 px-3 mb-4 text-center" role="alert">
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
                <div class="alert alert-danger py-2 px-3 mb-4 text-center" role="alert">
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php echo e($slot); ?>


            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($backUrl): ?>
                <div class="text-center mt-4">
                    <a href="<?php echo e($backUrl); ?>" class="auth-back-link">
                        <i class="ti ti-arrow-right me-1"></i>
                        <?php echo e($backText); ?>

                    </a>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showFooter): ?>
            <div class="auth-form-footer">
                <p class="mb-1">جميع الحقوق محفوظة 2026 ©</p>
                <a href="https://e-tec.sa" target="_blank" rel="noopener noreferrer" class="auth-footer-link">
                    <span>إتمام لتقنية نظم المعلومات</span>
                    <img src="<?php echo e(asset('static/brand/logos/logo-footer.png')); ?>" alt="Logo" class="footer-logo">
                </a>
                <p class="mb-0 text-muted small">V.<?php echo e(config('app.version')); ?></p>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <!-- Left Side - Media & Brand Overview -->
    <div class="auth-media-side" style="background-image: url('<?php echo e($bgImage); ?>');">
        <div class="auth-media-overlay"></div>

        <div class="auth-media-content">
            <div class="auth-media-header">
                <div class="auth-header-line"></div>
                <div class="auth-header-tagline">
                    <?php echo nl2br(e($mediaTagline)); ?>

                </div>
            </div>

            <div class="auth-media-body">
                <h1 class="auth-media-title">
                    <?php echo nl2br(e($mediaTitle)); ?>

                </h1>
                <p class="auth-media-desc">
                    <?php echo nl2br(e($mediaDesc)); ?>

                </p>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($features)): ?>
                <div class="auth-media-features">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $features; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feature): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="auth-feature-card">
                            <div class="auth-feature-icon">
                                <i class="<?php echo e($feature['icon']); ?>" aria-hidden="true"></i>
                            </div>
                            <div class="auth-feature-text"><?php echo e($feature['text']); ?></div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH D:\Levan\resources\views/components/shared/auth/split-layout.blade.php ENDPATH**/ ?>