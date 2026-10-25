<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'id',
    'title',
    'icon' => null,
    'size' => null,
    'centered' => true,
    'scrollable' => false,
    'staticBackdrop' => false,
    'labelledBy' => null,
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
    'id',
    'title',
    'icon' => null,
    'size' => null,
    'centered' => true,
    'scrollable' => false,
    'staticBackdrop' => false,
    'labelledBy' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $titleId = $labelledBy ?: $id . 'Label';
    $dialogClasses = trim('modal-dialog' . ($size ? ' modal-' . $size : '') . ($centered ? ' modal-dialog-centered' : '') . ($scrollable ? ' modal-dialog-scrollable' : ''));
?>

<div id="<?php echo e($id); ?>" class="modal fade" tabindex="-1" aria-labelledby="<?php echo e($titleId); ?>" aria-hidden="true"
    <?php if($staticBackdrop): ?> data-bs-backdrop="static" data-bs-keyboard="false" <?php endif; ?>>
    <div class="<?php echo e($dialogClasses); ?>">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="<?php echo e($titleId); ?>" class="modal-title d-flex align-items-center">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($icon): ?>
                        <i class="ti <?php echo e($icon); ?> me-2 text-primary"></i>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <span><?php echo e($title); ?></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>

            <div class="modal-body">
                <?php echo e($slot); ?>

            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($footer) && ! $footer->isEmpty()): ?>
                <div class="modal-footer">
                    <?php echo e($footer); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH D:\Levan\old\resources\views/components/shared/bootstrap/modal.blade.php ENDPATH**/ ?>