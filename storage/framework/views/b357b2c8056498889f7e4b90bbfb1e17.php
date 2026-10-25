<?php if (isset($component)) { $__componentOriginalb7ab3f8978c2d311d703279f6a6a8697 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb7ab3f8978c2d311d703279f6a6a8697 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.layout.footer','data' => ['supportLink' => '#']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.layout.footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['support-link' => '#']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb7ab3f8978c2d311d703279f6a6a8697)): ?>
<?php $attributes = $__attributesOriginalb7ab3f8978c2d311d703279f6a6a8697; ?>
<?php unset($__attributesOriginalb7ab3f8978c2d311d703279f6a6a8697); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb7ab3f8978c2d311d703279f6a6a8697)): ?>
<?php $component = $__componentOriginalb7ab3f8978c2d311d703279f6a6a8697; ?>
<?php unset($__componentOriginalb7ab3f8978c2d311d703279f6a6a8697); ?>
<?php endif; ?>
<?php /**PATH D:\Levan\resources\views/admin/layouts/sections/footer/footer.blade.php ENDPATH**/ ?>