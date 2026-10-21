<div class="d-flex justify-content-center gap-2">
    <?php if (isset($component)) { $__componentOriginala5850ce33dd80783cd1c12f8e9106fc2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala5850ce33dd80783cd1c12f8e9106fc2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::actions.edit','data' => ['url' => route('admin.roles.edit', $role),'title' => 'تعديل الدور','permission' => 'roles.edit']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('actions.edit'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.roles.edit', $role)),'title' => 'تعديل الدور','permission' => 'roles.edit']); ?>
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

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($role->name !== 'SuperAdmin'): ?>
        <?php if (isset($component)) { $__componentOriginal5614bc699f63fc8479350b7561a93f7e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5614bc699f63fc8479350b7561a93f7e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::actions.delete','data' => ['id' => $role->id,'url' => route('admin.roles.destroy', $role),'title' => 'حذف الدور','permission' => 'roles.delete']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('actions.delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($role->id),'url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.roles.destroy', $role)),'title' => 'حذف الدور','permission' => 'roles.delete']); ?>
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
</div>
<?php /**PATH D:\Levan\old\resources\views/admin/roles/action.blade.php ENDPATH**/ ?>