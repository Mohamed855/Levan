<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($pageConfigs)): ?>
    <?php echo App\Helpers\Helpers::updatePageConfig($pageConfigs); ?>

<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php
    $configData = App\Helpers\Helpers::appClasses();
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!isset($excludeJquery) || !$excludeJquery): ?>
    <script src="<?php echo e(asset('static/shared/js/cdn/jquery-init.js')); ?>"></script>
    
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($configData['layout'])): ?>
    <?php echo $__env->make(
        $configData['layout'] === 'horizontal'
            ? 'tenant.layouts.horizontalLayout'
            : ($configData['layout'] === 'blank'
                ? 'tenant.layouts.blankLayout'
                : ($configData['layout'] === 'front'
                    ? 'tenant.layouts.layoutFront'
                    : 'tenant.layouts.contentNavbarLayout')), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>


<?php /**PATH D:\Levan\old\resources\views/tenant/layouts/layoutMaster.blade.php ENDPATH**/ ?>