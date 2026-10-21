<?php $__env->startSection('title', 'تعديل قالب مزاد'); ?>

<?php $__env->startSection('page-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/scss/shared/template-designer.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/admin/auction-templates/designer.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <h4 class="mb-4">تعديل قالب المزاد: <span class="text-primary"><?php echo e($template->name); ?></span></h4>
    <form method="POST" action="<?php echo e(route('admin.auction-templates.update', $template)); ?>" id="auction-template-edit-form" data-form-validate="true">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <?php echo $__env->make('admin.auction-templates.partials.form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/admin/auction-templates/edit.blade.php ENDPATH**/ ?>