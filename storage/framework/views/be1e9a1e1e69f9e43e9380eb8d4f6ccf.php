<!DOCTYPE html>
<?php
$menuFixed =
$configData['layout'] === 'vertical'
? $menuFixed ?? ''
: ($configData['layout'] === 'front'
? ''
: $configData['headerType']);
$navbarType =
$configData['layout'] === 'vertical'
? $configData['navbarType'] ?? ''
: ($configData['layout'] === 'front'
? 'layout-navbar-fixed'
: '');
$isFront = ($isFront ?? '') == true ? 'Front' : '';
$contentLayout = isset($container) ? ($container === 'container-xxl' ? 'layout-compact' : 'layout-wide') : '';
?>

<html lang="<?php echo e(session()->get('locale') ?? app()->getLocale()); ?>"
    class="<?php echo e($configData['style']); ?>-style <?php echo e($contentLayout ?? ''); ?> <?php echo e($navbarType ?? ''); ?> <?php echo e($menuFixed ?? ''); ?> <?php echo e($menuCollapsed ?? ''); ?> <?php echo e($menuFlipped ?? ''); ?> <?php echo e($menuOffcanvas ?? ''); ?> <?php echo e($footerFixed ?? ''); ?> <?php echo e($customizerHidden ?? ''); ?>"
    dir="<?php echo e($configData['textDirection']); ?>" data-theme="<?php echo e($configData['theme']); ?>"
    data-assets-path="<?php echo e(asset('/static') . '/'); ?>" data-base-url="<?php echo e(url('/')); ?>" data-framework="laravel"
    data-template="<?php echo e($configData['layout'] . '-menu-' . $configData['themeOpt'] . '-' . $configData['styleOpt']); ?>"
    data-style="<?php echo e($configData['styleOptVal']); ?>">

<head>

    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

    <title><?php echo $__env->yieldContent('title'); ?> | لـــوت LOT </title>

    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('static/brand/logos/logo.svg')); ?>" />
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(asset('favicon-32x32.png')); ?>" />
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo e(asset('favicon-16x16.png')); ?>" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(asset('apple-touch-icon.png')); ?>" />
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo e(asset('favicon.ico')); ?>" />

    <?php echo $__env->make('admin.layouts.sections.styles' . $isFront, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!isset($excludeJquery) || !$excludeJquery): ?>
        <script src="<?php echo e(asset('static/shared/js/cdn/jquery-init.js')); ?>"></script>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php echo $__env->make('admin.layouts.sections.scriptsIncludes' . $isFront, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

</head>


<body
    data-flash-success="<?php echo e(Session::get('success')); ?>"
    data-flash-error="<?php echo e(Session::get('error')); ?>"
    data-flash-info="<?php echo e(Session::get('info')); ?>"
    data-flash-warning="<?php echo e(Session::get('warning')); ?>"
    data-username="<?php echo e(Auth::guard('admin')->user()->name ?? ''); ?>"
    data-app-boundary="admin"
    data-wizard-drafts-url="<?php echo e(route('admin.wizard-drafts.index')); ?>"
>

    <!-- Layout Content -->
    <?php echo $__env->yieldContent('layoutContent'); ?>

    <?php if (isset($component)) { $__componentOriginal6f1d55ca6ce9fbdd5b222c47862d795b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6f1d55ca6ce9fbdd5b222c47862d795b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::form.wizard.draft-template','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.draft-template'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6f1d55ca6ce9fbdd5b222c47862d795b)): ?>
<?php $attributes = $__attributesOriginal6f1d55ca6ce9fbdd5b222c47862d795b; ?>
<?php unset($__attributesOriginal6f1d55ca6ce9fbdd5b222c47862d795b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6f1d55ca6ce9fbdd5b222c47862d795b)): ?>
<?php $component = $__componentOriginal6f1d55ca6ce9fbdd5b222c47862d795b; ?>
<?php unset($__componentOriginal6f1d55ca6ce9fbdd5b222c47862d795b); ?>
<?php endif; ?>

    <?php echo $__env->make('admin.layouts.sections.scripts' . $isFront, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

</body>

</html>
<?php /**PATH D:\Levan\resources\views/admin/layouts/commonMaster.blade.php ENDPATH**/ ?>