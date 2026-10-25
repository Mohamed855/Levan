<?php if (isset($component)) { $__componentOriginalfbf8733213279f38e3e5cffb6fc6d3ce = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfbf8733213279f38e3e5cffb6fc6d3ce = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::actions.row','data' => ['class' => 'justify-content-center gap-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('actions.row'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'justify-content-center gap-2']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <a href="<?php echo e(tenant_route_name($route . '.show', $row->id)); ?>" class="btn btn-icon btn-label-primary bg-label-primary" title="عرض التفاصيل">
        <i class="ti ti-eye"></i>
    </a>
    <?php if (isset($component)) { $__componentOriginala5850ce33dd80783cd1c12f8e9106fc2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala5850ce33dd80783cd1c12f8e9106fc2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::actions.edit','data' => ['url' => tenant_route_name($route . '.edit', $row->id),'title' => 'تعديل العقار','permission' => 'properties.edit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('actions.edit'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(tenant_route_name($route . '.edit', $row->id)),'title' => 'تعديل العقار','permission' => 'properties.edit']); ?>
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
    <?php if (isset($component)) { $__componentOriginal5614bc699f63fc8479350b7561a93f7e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5614bc699f63fc8479350b7561a93f7e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::actions.delete','data' => ['id' => $row->id,'url' => tenant_route_name($route . '.destroy', $row->id),'title' => 'حذف العقار','permission' => 'properties.delete']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('actions.delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($row->id),'url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(tenant_route_name($route . '.destroy', $row->id)),'title' => 'حذف العقار','permission' => 'properties.delete']); ?>
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
<?php /**PATH D:\Levan\old\resources\views/tenant/properties/action.blade.php ENDPATH**/ ?>