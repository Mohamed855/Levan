<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($pageConfigs)): ?>
<?php echo App\Helpers\Helpers::updatePageConfig($pageConfigs); ?>

<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php
$configData = App\Helpers\Helpers::appClasses();

/* Display elements */
$customizerHidden = ($customizerHidden ?? '');

?>



<?php $__env->startSection('layoutContent'); ?>

<!-- Content -->
<?php echo $__env->yieldContent('content'); ?>
<!--/ Content -->

<?php $__env->stopSection(); ?>

<?php echo $__env->make('tenant/layouts/commonMaster' , array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\resources\views/tenant/layouts/blankLayout.blade.php ENDPATH**/ ?>