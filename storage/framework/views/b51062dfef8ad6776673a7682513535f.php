<?php $__env->startSection('title','عينات قوالب المزادات'); ?>
<?php $__env->startSection('content'); ?>
<div class="card"><div class="card-header d-flex justify-content-between"><h5>مكتبة عينات القوالب</h5><span class="text-muted">تُدار مركزيًا وتظهر حسب الصلاحية</span></div><div class="card-body"><div id="auction-template-samples-library" data-library-url="<?php echo e(route('admin.auction-template-samples.library')); ?>" class="row g-3"></div></div></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\resources\views/admin/auction-template-samples/index.blade.php ENDPATH**/ ?>