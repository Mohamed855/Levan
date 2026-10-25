<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'id',
    'url',
    'title' => 'حذف',
    'permission' => null,
    'icon' => 'ti ti-trash',
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
    'id',
    'url',
    'title' => 'حذف',
    'permission' => null,
    'icon' => 'ti ti-trash',
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
    <button type="button" data-id="<?php echo e($id); ?>"
        <?php echo e($attributes->merge(['class' => 'btn ' . $class . ' js-delete-btn', 'title' => $title, 'aria-label' => $title])); ?>>
        <i class="<?php echo e($icon); ?>" aria-hidden="true"></i>
    </button>

    <form id="delete-form-<?php echo e($id); ?>" action="<?php echo e($url); ?>" method="POST" class="d-none">
        <?php echo csrf_field(); ?>
        <?php echo method_field('DELETE'); ?>
    </form>
<?php else: ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check($permission)): ?>
        <button type="button" data-id="<?php echo e($id); ?>"
            <?php echo e($attributes->merge(['class' => 'btn ' . $class . ' js-delete-btn', 'title' => $title, 'aria-label' => $title])); ?>>
            <i class="<?php echo e($icon); ?>" aria-hidden="true"></i>
        </button>

        <form id="delete-form-<?php echo e($id); ?>" action="<?php echo e($url); ?>" method="POST" class="d-none">
            <?php echo csrf_field(); ?>
            <?php echo method_field('DELETE'); ?>
        </form>
    <?php endif; ?>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH D:\Levan\resources\views/components/shared/actions/delete.blade.php ENDPATH**/ ?>