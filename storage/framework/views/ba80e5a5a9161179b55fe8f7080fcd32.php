<head>
    <!-- Meta Tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="Copyright" content="lot" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <!-- Page title -->
    <title>
        <?php echo $__env->yieldContent('title', 'لوت LOT - المنصة المتقدمة لإدارة المزادات والأصول'); ?>
    </title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('static/brand/logos/logo.svg')); ?>" />
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo e(asset('favicon-32x32.png')); ?>" />
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo e(asset('favicon-16x16.png')); ?>" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo e(asset('apple-touch-icon.png')); ?>" />
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo e(asset('favicon.ico')); ?>" />

    <!-- Brand Fonts: Almarai, Readex Pro, Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Almarai:wght@300;400;700;800&family=Outfit:wght@400;500;600;700;800&family=Readex+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Core Site Styles -->
    <?php echo app('Illuminate\Foundation\Vite')([
        'resources/assets/scss/web/site.scss',
        'resources/assets/vendor/fonts/tabler-icons.scss',
        'resources/assets/js/shared/input-guards.js'
    ]); ?>

    <?php echo $__env->yieldContent('page-style'); ?>
</head>
<?php /**PATH D:\Levan\resources\views/web/layouts/head.blade.php ENDPATH**/ ?>