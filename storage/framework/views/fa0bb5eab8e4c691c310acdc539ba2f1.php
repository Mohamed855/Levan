<?php if (isset($component)) { $__componentOriginalea9b70efbe0e9f03794819ce9ea60a58 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalea9b70efbe0e9f03794819ce9ea60a58 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.layout.scripts','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.layout.scripts'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalea9b70efbe0e9f03794819ce9ea60a58)): ?>
<?php $attributes = $__attributesOriginalea9b70efbe0e9f03794819ce9ea60a58; ?>
<?php unset($__attributesOriginalea9b70efbe0e9f03794819ce9ea60a58); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalea9b70efbe0e9f03794819ce9ea60a58)): ?>
<?php $component = $__componentOriginalea9b70efbe0e9f03794819ce9ea60a58; ?>
<?php unset($__componentOriginalea9b70efbe0e9f03794819ce9ea60a58); ?>
<?php endif; ?>
<?php /**PATH D:\Levan\old\resources\views/admin/layouts/sections/scripts.blade.php ENDPATH**/ ?>