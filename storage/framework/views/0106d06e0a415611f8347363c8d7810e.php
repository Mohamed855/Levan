<!DOCTYPE html>
<html lang="ar" dir="rtl">

<?php echo $__env->make('web.layouts.head', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<body class="site-shell">
    <?php if (isset($component)) { $__componentOriginal6b87f1c3934507cb1cefc59c88809aff = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6b87f1c3934507cb1cefc59c88809aff = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.shared.toastr','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shared.toastr'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6b87f1c3934507cb1cefc59c88809aff)): ?>
<?php $attributes = $__attributesOriginal6b87f1c3934507cb1cefc59c88809aff; ?>
<?php unset($__attributesOriginal6b87f1c3934507cb1cefc59c88809aff); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6b87f1c3934507cb1cefc59c88809aff)): ?>
<?php $component = $__componentOriginal6b87f1c3934507cb1cefc59c88809aff; ?>
<?php unset($__componentOriginal6b87f1c3934507cb1cefc59c88809aff); ?>
<?php endif; ?>

    <!-- Page loading indicator -->
    <div class="page-loading" role="status" aria-live="polite" aria-atomic="true" aria-label="Loading...">
        <div class="grid-loader">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>

    <?php echo $__env->make('web.layouts.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo $__env->yieldContent('content'); ?>

    <?php echo $__env->make('web.layouts.footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <!-- Back to top button -->
    <button type="button" class="back-to-top" aria-label="الرجوع للأعلى">
        <i class="ti ti-chevron-up"></i>
    </button>

    <?php echo $__env->make('web.layouts.script', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo $__env->yieldContent('toastr'); ?>

    <?php echo $__env->yieldContent('page-script'); ?>
</body>

</html>
<?php /**PATH D:\Levan\resources\views/web/layouts/main.blade.php ENDPATH**/ ?>