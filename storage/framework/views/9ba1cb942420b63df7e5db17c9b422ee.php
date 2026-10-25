<template data-create-draft-button-template>
    <?php if (isset($component)) { $__componentOriginal0281d78d2d16329c5a422ee3e125697d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0281d78d2d16329c5a422ee3e125697d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.draft-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.draft-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0281d78d2d16329c5a422ee3e125697d)): ?>
<?php $attributes = $__attributesOriginal0281d78d2d16329c5a422ee3e125697d; ?>
<?php unset($__attributesOriginal0281d78d2d16329c5a422ee3e125697d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0281d78d2d16329c5a422ee3e125697d)): ?>
<?php $component = $__componentOriginal0281d78d2d16329c5a422ee3e125697d; ?>
<?php unset($__componentOriginal0281d78d2d16329c5a422ee3e125697d); ?>
<?php endif; ?>
</template>
<?php /**PATH D:\Levan\old\resources\views/components/shared/form/wizard/draft-template.blade.php ENDPATH**/ ?>