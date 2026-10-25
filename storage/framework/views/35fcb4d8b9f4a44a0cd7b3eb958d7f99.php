<?php if (isset($component)) { $__componentOriginalfbf8733213279f38e3e5cffb6fc6d3ce = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfbf8733213279f38e3e5cffb6fc6d3ce = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::actions.row','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('actions.row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <?php if (isset($component)) { $__componentOriginala5850ce33dd80783cd1c12f8e9106fc2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala5850ce33dd80783cd1c12f8e9106fc2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::actions.edit','data' => ['url' => tenant_route_name($route . '.edit', $id),'title' => 'تعديل المستخدم','permission' => 'users.edit','class' => 'btn-icon btn-label-primary bg-label-primary me-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('actions.edit'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(tenant_route_name($route . '.edit', $id)),'title' => 'تعديل المستخدم','permission' => 'users.edit','class' => 'btn-icon btn-label-primary bg-label-primary me-1']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala5850ce33dd80783cd1c12f8e9106fc2)): ?>
<?php $attributes = $__attributesOriginala5850ce33dd80783cd1c12f8e9106fc2; ?>
<?php unset($__attributesOriginala5850ce33dd80783cd1c12f8e9106fc2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala5850ce33dd80783cd1c12f8e9106fc2)): ?>
<?php $component = $__componentOriginala5850ce33dd80783cd1c12f8e9106fc2; ?>
<?php unset($__componentOriginala5850ce33dd80783cd1c12f8e9106fc2); ?>
<?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) $id !== 1): ?>
        <?php if (isset($component)) { $__componentOriginal5614bc699f63fc8479350b7561a93f7e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5614bc699f63fc8479350b7561a93f7e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::actions.delete','data' => ['id' => $id,'url' => tenant_route_name($route . '.destroy', $id),'title' => 'حذف المستخدم','permission' => 'users.delete']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('actions.delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($id),'url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(tenant_route_name($route . '.destroy', $id)),'title' => 'حذف المستخدم','permission' => 'users.delete']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5614bc699f63fc8479350b7561a93f7e)): ?>
<?php $attributes = $__attributesOriginal5614bc699f63fc8479350b7561a93f7e; ?>
<?php unset($__attributesOriginal5614bc699f63fc8479350b7561a93f7e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5614bc699f63fc8479350b7561a93f7e)): ?>
<?php $component = $__componentOriginal5614bc699f63fc8479350b7561a93f7e; ?>
<?php unset($__componentOriginal5614bc699f63fc8479350b7561a93f7e); ?>
<?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfbf8733213279f38e3e5cffb6fc6d3ce)): ?>
<?php $attributes = $__attributesOriginalfbf8733213279f38e3e5cffb6fc6d3ce; ?>
<?php unset($__attributesOriginalfbf8733213279f38e3e5cffb6fc6d3ce); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfbf8733213279f38e3e5cffb6fc6d3ce)): ?>
<?php $component = $__componentOriginalfbf8733213279f38e3e5cffb6fc6d3ce; ?>
<?php unset($__componentOriginalfbf8733213279f38e3e5cffb6fc6d3ce); ?>
<?php endif; ?>
<?php /**PATH D:\Levan\resources\views/tenant/user-management/users/action.blade.php ENDPATH**/ ?>