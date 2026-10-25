<?php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
?>



<?php $__env->startSection('title', 'تسجيل الدخول - الإدارة'); ?>

<?php $__env->startSection('page-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/auth-layout.css']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/shared/auth-layout.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalb3d870cbd314e8a189299a20f1abf57a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3d870cbd314e8a189299a20f1abf57a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::auth.split-layout','data' => ['formTitle' => 'مرحباً بك مجدداً','formSubtitle' => 'تسجيل الدخول للوحة تحكم الإدارة المركزية','bgImage' => asset('static/auth/backgrounds/admin-login-bg.jpg'),'mediaTagline' => 'الإدارة المركزية | لوت LOT','mediaTitle' => 'منظومة الإدارة والتحكم الشامل | للمنصة العقارية','mediaDesc' => 'أدوات حصرية للإدارة العامة لمتابعة الشركات العقارية، إدارة التراخيص والاشتراكات، والتحكم في إعدادات المنظومة بدقة وكفاءة عالية.','features' => [
            ['icon' => 'ti ti-building-community', 'text' => 'إدارة الشركات والمستأجرين'],
            ['icon' => 'ti ti-chart-arrows', 'text' => 'مؤشرات وتقارير شمولية'],
            ['icon' => 'ti ti-shield-lock', 'text' => 'أمان ورقابة مركزية متقدمة'],
        ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('auth.split-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['form-title' => 'مرحباً بك مجدداً','form-subtitle' => 'تسجيل الدخول للوحة تحكم الإدارة المركزية','bg-image' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(asset('static/auth/backgrounds/admin-login-bg.jpg')),'media-tagline' => 'الإدارة المركزية | لوت LOT','media-title' => 'منظومة الإدارة والتحكم الشامل | للمنصة العقارية','media-desc' => 'أدوات حصرية للإدارة العامة لمتابعة الشركات العقارية، إدارة التراخيص والاشتراكات، والتحكم في إعدادات المنظومة بدقة وكفاءة عالية.','features' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
            ['icon' => 'ti ti-building-community', 'text' => 'إدارة الشركات والمستأجرين'],
            ['icon' => 'ti ti-chart-arrows', 'text' => 'مؤشرات وتقارير شمولية'],
            ['icon' => 'ti ti-shield-lock', 'text' => 'أمان ورقابة مركزية متقدمة'],
        ])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <form id="formAuthentication" action="<?php echo e(route('admin.login')); ?>" method="POST" novalidate data-form-validate
            data-validation-url="<?php echo e(route('admin.form-validation')); ?>">
            <?php echo csrf_field(); ?>
            <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::form.controls.input','data' => ['name' => 'email','label' => 'البريد الإلكتروني','placeholder' => 'أدخل البريد الإلكتروني','type' => 'email','value' => old('email'),'required' => true,'autocomplete' => 'username','col' => 'col-12 mb-3','icon' => 'ti ti-mail']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'email','label' => 'البريد الإلكتروني','placeholder' => 'أدخل البريد الإلكتروني','type' => 'email','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('email')),'required' => true,'autocomplete' => 'username','col' => 'col-12 mb-3','icon' => 'ti ti-mail']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $attributes = $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276)): ?>
<?php $component = $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276; ?>
<?php unset($__componentOriginal3dadacf08e1fd61e8049ea7dffda1276); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal6a58491022d9221e27dd03c346c4f73d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6a58491022d9221e27dd03c346c4f73d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::form.controls.password','data' => ['name' => 'password','label' => 'كلمة المرور','placeholder' => 'أدخل كلمة المرور','required' => true,'autocomplete' => 'current-password','col' => 'col-12 mb-3','icon' => 'ti ti-lock']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.password'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'password','label' => 'كلمة المرور','placeholder' => 'أدخل كلمة المرور','required' => true,'autocomplete' => 'current-password','col' => 'col-12 mb-3','icon' => 'ti ti-lock']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6a58491022d9221e27dd03c346c4f73d)): ?>
<?php $attributes = $__attributesOriginal6a58491022d9221e27dd03c346c4f73d; ?>
<?php unset($__attributesOriginal6a58491022d9221e27dd03c346c4f73d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6a58491022d9221e27dd03c346c4f73d)): ?>
<?php $component = $__componentOriginal6a58491022d9221e27dd03c346c4f73d; ?>
<?php unset($__componentOriginal6a58491022d9221e27dd03c346c4f73d); ?>
<?php endif; ?>

            <div class="auth-options">
                <div class="form-check d-flex align-items-center gap-2 mb-0">
                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember">
                    <label class="form-check-label" for="remember_me">تذكرني في هذا الجهاز</label>
                </div>
            </div>

            <button class="btn-auth-primary" type="submit">
                <span>تسجيل الدخول</span>
                <i class="ti ti-arrow-left" aria-hidden="true"></i>
            </button>
        </form>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb3d870cbd314e8a189299a20f1abf57a)): ?>
<?php $attributes = $__attributesOriginalb3d870cbd314e8a189299a20f1abf57a; ?>
<?php unset($__attributesOriginalb3d870cbd314e8a189299a20f1abf57a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb3d870cbd314e8a189299a20f1abf57a)): ?>
<?php $component = $__componentOriginalb3d870cbd314e8a189299a20f1abf57a; ?>
<?php unset($__componentOriginalb3d870cbd314e8a189299a20f1abf57a); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\resources\views/admin/auth/login.blade.php ENDPATH**/ ?>