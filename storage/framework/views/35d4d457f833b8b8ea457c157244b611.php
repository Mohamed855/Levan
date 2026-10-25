<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name' => 'password',
    'label' => 'كلمة المرور',
    'id' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
    'col' => 'col-md-6',
    'autocomplete' => 'new-password',
    'icon' => null,
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
    'name' => 'password',
    'label' => 'كلمة المرور',
    'id' => null,
    'placeholder' => null,
    'required' => false,
    'help' => null,
    'col' => 'col-md-6',
    'autocomplete' => 'new-password',
    'icon' => null,
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
?>

<?php if (isset($component)) { $__componentOriginalb0847e2c7581f7b89ace73fef2a6713e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb0847e2c7581f7b89ace73fef2a6713e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::form.layout.field','data' => ['name' => $name,'label' => $label,'id' => $fieldId,'required' => $required,'help' => $help,'col' => $col]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.layout.field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($name),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($label),'id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fieldId),'required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($required),'help' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($help),'col' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($col)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
        'input-group input-group-merge',
        'is-invalid' => $errors->has($name),
    ]); ?>">
        <input id="<?php echo e($fieldId); ?>" name="<?php echo e($name); ?>" type="password" autocomplete="<?php echo e($autocomplete); ?>"
            <?php if($placeholder): ?> placeholder="<?php echo e($placeholder); ?>" <?php endif; ?> <?php if($required): echo 'required'; endif; ?>
            data-form-control aria-describedby="<?php echo e($errorId); ?>"
            aria-invalid="<?php echo e($errors->has($name) ? 'true' : 'false'); ?>"
            <?php echo e($attributes->class(['form-control', 'is-invalid' => $errors->has($name)])); ?>>
        <button type="button" class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'input-group-text cursor-pointer',
            'is-invalid' => $errors->has($name),
        ]); ?>" data-password-toggle="<?php echo e($fieldId); ?>"
            aria-label="إظهار كلمة المرور">
            <i class="ti ti-eye-off" aria-hidden="true"></i>
        </button>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb0847e2c7581f7b89ace73fef2a6713e)): ?>
<?php $attributes = $__attributesOriginalb0847e2c7581f7b89ace73fef2a6713e; ?>
<?php unset($__attributesOriginalb0847e2c7581f7b89ace73fef2a6713e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb0847e2c7581f7b89ace73fef2a6713e)): ?>
<?php $component = $__componentOriginalb0847e2c7581f7b89ace73fef2a6713e; ?>
<?php unset($__componentOriginalb0847e2c7581f7b89ace73fef2a6713e); ?>
<?php endif; ?>
<?php /**PATH D:\Levan\resources\views/components/shared/form/controls/password.blade.php ENDPATH**/ ?>