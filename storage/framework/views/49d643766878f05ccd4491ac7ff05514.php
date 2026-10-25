<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'url',
    'title' => 'تعديل',
    'permission' => null,
    'icon' => 'ti ti-edit',
    'class' => 'btn-icon btn-label-primary bg-label-primary',
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
    'url',
    'title' => 'تعديل',
    'permission' => null,
    'icon' => 'ti ti-edit',
    'class' => 'btn-icon btn-label-primary bg-label-primary',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $permission): ?>
    <a href="<?php echo e($url); ?>" <?php echo e($attributes->merge(['class' => 'btn ' . $class, 'title' => $title, 'aria-label' => $title])); ?>>
        <i class="<?php echo e($icon); ?>" aria-hidden="true"></i>
    </a>
<?php else: ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($permission)): ?>
        <a href="<?php echo e($url); ?>" <?php echo e($attributes->merge(['class' => 'btn ' . $class, 'title' => $title, 'aria-label' => $title])); ?>>
            <i class="<?php echo e($icon); ?>" aria-hidden="true"></i>
        </a>
    <?php endif; ?>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH D:\Levan\resources\views/components/shared/actions/edit.blade.php ENDPATH**/ ?>