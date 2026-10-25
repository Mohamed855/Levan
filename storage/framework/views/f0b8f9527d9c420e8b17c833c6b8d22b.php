<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name',
    'label' => null,
    'id' => null,
    'required' => false,
    'help' => null,
    'col' => 'col-md-6',
    'wrapperId' => null,
    'wrapperStyle' => null,
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
    'name',
    'label' => null,
    'id' => null,
    'required' => false,
    'help' => null,
    'col' => 'col-md-6',
    'wrapperId' => null,
    'wrapperStyle' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
    $hasError = $errors->has($name);
?>

<div <?php if($wrapperId): ?> id="<?php echo e($wrapperId); ?>" <?php endif; ?> <?php if($wrapperStyle): ?> style="<?php echo e($wrapperStyle); ?>" <?php endif; ?> <?php echo e($attributes->class([$col, 'is-invalid' => $hasError])); ?> data-form-field data-field-name="<?php echo e($name); ?>">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($label): ?>
        <label class="form-label" for="<?php echo e($fieldId); ?>">
            <?php echo e($label); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($required): ?><span class="text-danger" aria-hidden="true"> *</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </label>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php echo e($slot); ?>


    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($help): ?>
        <div class="form-text"><?php echo e($help); ?></div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div id="<?php echo e($errorId); ?>" class="invalid-feedback d-block" data-form-error role="alert" <?php if(! $hasError): ?> hidden <?php endif; ?>>
        <?php echo e($errors->first($name)); ?>

    </div>
</div>
<?php /**PATH D:\Levan\old\resources\views/components/shared/form/layout/field.blade.php ENDPATH**/ ?>