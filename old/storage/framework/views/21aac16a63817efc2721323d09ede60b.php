<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title',
    'icon' => null,
    'iconColor' => 'primary',
    'bodyClass' => null,
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
    'title',
    'icon' => null,
    'iconColor' => 'primary',
    'bodyClass' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php if (isset($component)) { $__componentOriginalccf68d41223ed3833517d5b3348deb11 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalccf68d41223ed3833517d5b3348deb11 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.dashboard-panel','data' => ['attributes' => $attributes,'title' => $title,'icon' => $icon,'iconColor' => $iconColor,'bodyClass' => $bodyClass]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.dashboard-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes),'title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($title),'icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($icon),'icon-color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($iconColor),'body-class' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($bodyClass)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($headerAction)): ?>
         <?php $__env->slot('headerAction', null, []); ?> <?php echo e($headerAction); ?> <?php $__env->endSlot(); ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php echo e($slot); ?>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalccf68d41223ed3833517d5b3348deb11)): ?>
<?php $attributes = $__attributesOriginalccf68d41223ed3833517d5b3348deb11; ?>
<?php unset($__attributesOriginalccf68d41223ed3833517d5b3348deb11); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalccf68d41223ed3833517d5b3348deb11)): ?>
<?php $component = $__componentOriginalccf68d41223ed3833517d5b3348deb11; ?>
<?php unset($__componentOriginalccf68d41223ed3833517d5b3348deb11); ?>
<?php endif; ?>
<?php /**PATH D:\Levan\old\resources\views/components/admin/dashboard-panel.blade.php ENDPATH**/ ?>