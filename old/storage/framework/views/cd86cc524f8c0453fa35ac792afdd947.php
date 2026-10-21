<?php $__env->startSection('title', 'إنشاء قالب مزاد'); ?>

<?php $__env->startSection('page-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/scss/shared/template-designer.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <form method="POST" action="<?php echo e(route('admin.auction-templates.store')); ?>" id="auction-template-create-form" data-form-validate="true" data-wizard-resource="admin.auction-templates.create" data-draft-resource-key="admin.auction-templates.create" data-wizard-url="<?php echo e(route('admin.wizard-drafts.index')); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="_wizard_draft_id" data-wizard-draft-id>
        <input type="hidden" name="_wizard_resource_key" value="admin.auction-templates.create">
        <?php echo $__env->make('admin.auction-templates.partials.form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </form>
    <?php if (isset($component)) { $__componentOriginal79bd8f308c2a741acd5126b45ccc15bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal79bd8f308c2a741acd5126b45ccc15bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.wizard.draft-modal','data' => ['resourceKey' => 'admin.auction-templates.create']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.wizard.draft-modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['resource-key' => 'admin.auction-templates.create']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal79bd8f308c2a741acd5126b45ccc15bb)): ?>
<?php $attributes = $__attributesOriginal79bd8f308c2a741acd5126b45ccc15bb; ?>
<?php unset($__attributesOriginal79bd8f308c2a741acd5126b45ccc15bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal79bd8f308c2a741acd5126b45ccc15bb)): ?>
<?php $component = $__componentOriginal79bd8f308c2a741acd5126b45ccc15bb; ?>
<?php unset($__componentOriginal79bd8f308c2a741acd5126b45ccc15bb); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')([
        'resources/assets/js/admin/auction-templates/designer.js',
        'resources/assets/js/shared/wizard-draft.js'
    ]); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/admin/auction-templates/create.blade.php ENDPATH**/ ?>