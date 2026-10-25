<?php if (isset($component)) { $__componentOriginal0f266f8ba3f7f8f59be865491e096a33 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0f266f8ba3f7f8f59be865491e096a33 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.layout.scripts-includes','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.layout.scripts-includes'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0f266f8ba3f7f8f59be865491e096a33)): ?>
<?php $attributes = $__attributesOriginal0f266f8ba3f7f8f59be865491e096a33; ?>
<?php unset($__attributesOriginal0f266f8ba3f7f8f59be865491e096a33); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0f266f8ba3f7f8f59be865491e096a33)): ?>
<?php $component = $__componentOriginal0f266f8ba3f7f8f59be865491e096a33; ?>
<?php unset($__componentOriginal0f266f8ba3f7f8f59be865491e096a33); ?>
<?php endif; ?>
<?php /**PATH D:\Levan\resources\views/admin/layouts/sections/scriptsIncludes.blade.php ENDPATH**/ ?>