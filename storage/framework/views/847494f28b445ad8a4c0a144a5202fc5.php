<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'supportLink' => null,
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
    'supportLink' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $configData = App\Helpers\Helpers::appClasses();
    $containerFooter = isset($configData['contentLayout']) && $configData['contentLayout'] === 'wide'
        ? 'container-fluid'
        : 'container-xxl';
?>

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme no-print">
    <div class="<?php echo e($containerFooter); ?>">
        <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
            <div class="text-body text-center">
                جميع الحقوق محفوظة <?php echo e(date('Y')); ?> © . <a href="https://e-tec.sa" target="_blank" rel="noopener noreferrer" class="footer-link">إتمام لتقنية نظم المعلومات ❤️</a>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($supportLink): ?>
                <div class="d-none d-lg-inline-block">
                    <a href="<?php echo e($supportLink); ?>" id="footer-support" class="footer-link d-none d-sm-inline-block">الدعم الفني</a>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</footer>
<!--/ Footer-->
<?php /**PATH D:\Levan\resources\views/components/shared/layout/footer.blade.php ENDPATH**/ ?>