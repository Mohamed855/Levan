<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'name',
    'label' => null,
    'options' => [],
    'enum' => null,
    'id' => null,
    'value' => null,
    'placeholder' => 'اختر خيارًا',
    'required' => false,
    'multiple' => false,
    'select2' => true,
    'help' => null,
    'col' => 'col-md-6',
    'append' => null,
    'controlName' => null,
    'wrapperId' => null,
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
    'options' => [],
    'enum' => null,
    'id' => null,
    'value' => null,
    'placeholder' => 'اختر خيارًا',
    'required' => false,
    'multiple' => false,
    'select2' => true,
    'help' => null,
    'col' => 'col-md-6',
    'append' => null,
    'controlName' => null,
    'wrapperId' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    if ($enum) {
        $options = \App\Support\SelectOptions::fromEnum($enum);
    }
?>

<?php
    $fieldId = $id ?: str_replace(['[', ']'], '-', $name);
    $fieldName = $multiple && ! str_ends_with($name, '[]') ? $name . '[]' : $name;
    $selectedValue = old($name, $value);
    $selectedValues = collect(is_array($selectedValue) ? $selectedValue : [$selectedValue])
        ->map(static fn ($item) => (string) $item);
    $errorId = str_replace(['.', '[', ']'], '-', $fieldId) . '-error';
?>

<?php if (isset($component)) { $__componentOriginalb0847e2c7581f7b89ace73fef2a6713e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb0847e2c7581f7b89ace73fef2a6713e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::form.layout.field','data' => ['name' => $name,'label' => $label,'id' => $fieldId,'required' => $required,'help' => $help,'col' => $col,'wrapperId' => $wrapperId]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.layout.field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($name),'label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($label),'id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($fieldId),'required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($required),'help' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($help),'col' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($col),'wrapper-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($wrapperId)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <select
        id="<?php echo e($fieldId); ?>"
        <?php if($controlName !== false): ?> name="<?php echo e($controlName ?? $fieldName); ?>" <?php endif; ?>
        <?php if($multiple): ?> multiple <?php endif; ?>
        data-form-control
        data-placeholder="<?php echo e($placeholder); ?>"
        aria-describedby="<?php echo e($errorId); ?>"
        aria-invalid="<?php echo e($errors->has($name) ? 'true' : 'false'); ?>"
        <?php echo e($attributes->class(['form-select', 'select2' => $select2, 'is-invalid' => $errors->has($name)])); ?>

    >
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $multiple && empty($slot->toHtml())): ?>
            <option value=""></option>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <?php
                if ($enum) {
                    $optionValue = $key;
                    $optionLabel = $option;
                    $optionDisabled = false;
                } elseif (is_object($option)) {
                    $optionValue = $option->id ?? $option->value ?? $key;
                    $optionLabel = $option->name ?? $option->label ?? $option->title ?? (string) $optionValue;
                    $optionDisabled = (bool) ($option->disabled ?? false);
                } elseif (is_array($option)) {
                    $optionValue = $option['value'] ?? $option['id'] ?? $key;
                    $optionLabel = $option['label'] ?? $option['name'] ?? $option['title'] ?? (string) $optionValue;
                    $optionDisabled = (bool) ($option['disabled'] ?? false);
                } elseif (is_array($options) && array_is_list($options)) {
                    $optionValue = $option;
                    $optionLabel = $option;
                    $optionDisabled = false;
                } else {
                    $optionValue = $key;
                    $optionLabel = $option;
                    $optionDisabled = false;
                }
            ?>
            <option value="<?php echo e($optionValue); ?>" <?php if($selectedValues->contains((string) $optionValue)): echo 'selected'; endif; ?> <?php if($optionDisabled): echo 'disabled'; endif; ?>>
                <?php echo e($optionLabel); ?>

            </option>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        <?php echo e($slot ?? ''); ?>

    </select>
    <?php echo $append ?? ''; ?>

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
<?php /**PATH D:\Levan\resources\views/components/shared/form/controls/select.blade.php ENDPATH**/ ?>