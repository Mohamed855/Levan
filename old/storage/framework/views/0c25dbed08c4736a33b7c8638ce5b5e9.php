 

<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/fonts/tabler-icons.scss', 'resources/assets/vendor/fonts/fontawesome.scss', 'resources/assets/vendor/fonts/flag-icons.scss', 'resources/assets/vendor/libs/node-waves/node-waves.scss']); ?>
<!-- Core CSS -->
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/scss/rtl/core.scss', 'resources/assets/vendor/scss/rtl/theme-semi-dark.scss']); ?>


<!-- Vendor Styles -->
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/demo.css',
'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss',
 'resources/assets/vendor/libs/typeahead-js/typeahead.scss' , 
  'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
  'resources/assets/css/shared/main.css']); ?>
<?php echo $__env->yieldContent('vendor-style'); ?>

<!-- Custom Design System -->
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/scss/admin/app.scss']); ?>

<!-- Page Styles -->
<?php echo $__env->yieldContent('page-style'); ?>
<?php /**PATH D:\Levan\old\resources\views/admin/layouts/sections/styles.blade.php ENDPATH**/ ?>