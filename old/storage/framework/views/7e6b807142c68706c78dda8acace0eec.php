<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name' => 'image',
    'label' => 'الصورة',
    'id' => null,
    'value' => null,
    'accept' => 'image/png,image/jpeg,image/jpg,image/gif',
    'hint' => 'JPG، PNG أو GIF.',
    'required' => false,
    'col' => 'col-md-6',
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
    'name' => 'image',
    'label' => 'الصورة',
    'id' => null,
    'value' => null,
    'accept' => 'image/png,image/jpeg,image/jpg,image/gif',
    'hint' => 'JPG، PNG أو GIF.',
    'required' => false,
    'col' => 'col-md-6',
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.layout.field','data' => ['name' => $name,'label' => $label,'id' => $fieldId,'required' => $required,'col' => $col]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.layout.field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($name),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($label),'id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fieldId),'required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($required),'col' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($col)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <div class="d-flex align-items-start align-items-sm-center gap-4 pb-4 border-bottom">
        <img src="<?php echo e($value ?: asset('static/brand/logos/logo.svg')); ?>" alt="<?php echo e($label); ?>" class="d-block w-px-100 h-px-100 rounded object-fit-cover"
            data-image-preview="<?php echo e($fieldId); ?>">
        <div class="button-wrapper">
            <label for="<?php echo e($fieldId); ?>" class="btn btn-primary mb-2" tabindex="0">
                <span class="d-none d-sm-block">تحديد صورة</span>
                <i class="ti ti-upload d-block d-sm-none" aria-hidden="true"></i>
            </label>
            <input
                id="<?php echo e($fieldId); ?>"
                name="<?php echo e($name); ?>"
                type="file"
                class="d-none"
                accept="<?php echo e($accept); ?>"
                data-form-control
                data-image-input="<?php echo e($fieldId); ?>"
                aria-describedby="<?php echo e($errorId); ?>"
                aria-invalid="<?php echo e($errors->has($name) ? 'true' : 'false'); ?>"
                <?php echo e($attributes); ?>

            >
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hint): ?>
                <div class="text-muted small"><?php echo e($hint); ?></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
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
<?php /**PATH D:\Levan\old\resources\views/components/shared/form/controls/image-upload.blade.php ENDPATH**/ ?>