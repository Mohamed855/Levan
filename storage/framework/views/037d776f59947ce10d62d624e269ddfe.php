<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title', 'step', 'bodyId' => null]));

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

foreach (array_filter((['title', 'step', 'bodyId' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<article <?php echo e($attributes->class(['card', 'wizard-review-card'])); ?>>
    <div class="card-header d-flex align-items-center justify-content-between gap-3">
        <h5 class="mb-0"><?php echo e($title); ?></h5>
        <button type="button" class="btn btn-sm btn-icon btn-outline-primary" data-edit-step="<?php echo e($step); ?>"
            aria-label="الانتقال إلى خطوة <?php echo e($title); ?> للتعديل" title="تعديل <?php echo e($title); ?>">
            <i class="ti ti-edit" aria-hidden="true"></i>
        </button>
    </div>
    <div <?php if($bodyId): ?> id="<?php echo e($bodyId); ?>" <?php endif; ?>
        class="card-body d-flex flex-column gap-3">
        <?php echo e($slot); ?>

    </div>
</article>
<?php /**PATH D:\Levan\old\resources\views/components/shared/form/wizard/review-card.blade.php ENDPATH**/ ?>