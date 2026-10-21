<?php $__env->startSection('title', 'إضافة مستخدم جديد'); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('toastr'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/shared/form-validation.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $steps = [
            [
                'id' => 'basic-info-validation',
                'title' => 'المعلومات الأساسية',
            ],
            [
                'id' => 'security-info-validation',
                'title' => 'كلمة المرور والصورة',
            ],
        ];
    ?>

    <div class="row">
        <div class="col-12 mb-6">
            <?php if (isset($component)) { $__componentOriginalc72a709c96cbcb3f6949070279ea94e8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc72a709c96cbcb3f6949070279ea94e8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.stepper','data' => ['id' => 'user-wizard','steps' => $steps]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.stepper'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'user-wizard','steps' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($steps)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <?php if (isset($component)) { $__componentOriginal1c89c99137e0e04323f601c81f5a3f1c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1c89c99137e0e04323f601c81f5a3f1c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.validation-errors','data' => ['class' => 'mb-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.validation-errors'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mb-4']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1c89c99137e0e04323f601c81f5a3f1c)): ?>
<?php $attributes = $__attributesOriginal1c89c99137e0e04323f601c81f5a3f1c; ?>
<?php unset($__attributesOriginal1c89c99137e0e04323f601c81f5a3f1c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1c89c99137e0e04323f601c81f5a3f1c)): ?>
<?php $component = $__componentOriginal1c89c99137e0e04323f601c81f5a3f1c; ?>
<?php unset($__componentOriginal1c89c99137e0e04323f601c81f5a3f1c); ?>
<?php endif; ?>

                <form id="user-wizard-form" action="<?php echo e(route('admin.users.store')); ?>" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="<?php echo e(route('admin.wizard-drafts.validate-step')); ?>"
                    data-draft-url="<?php echo e(route('admin.wizard-drafts.store')); ?>" data-draft-resource-key="admin.user-create">
                    <?php echo csrf_field(); ?>

                    <?php if (isset($component)) { $__componentOriginalff513ee38c008c02d1b14306a4fc4344 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalff513ee38c008c02d1b14306a4fc4344 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.step','data' => ['id' => 'basic-info-validation','fields' => ['name', 'email', 'phone']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.step'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'basic-info-validation','fields' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['name', 'email', 'phone'])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        <div class="row g-4">
                            <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'name','label' => 'الاسم الكامل','placeholder' => 'ادخل الاسم الكامل','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'name','label' => 'الاسم الكامل','placeholder' => 'ادخل الاسم الكامل','required' => true,'col' => 'col-md-6']); ?>
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
                            <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'email','type' => 'email','label' => 'البريد الإلكتروني','placeholder' => 'ادخل البريد الإلكتروني','autocomplete' => 'off','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'email','type' => 'email','label' => 'البريد الإلكتروني','placeholder' => 'ادخل البريد الإلكتروني','autocomplete' => 'off','required' => true,'col' => 'col-md-6']); ?>
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
                            <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'phone','label' => 'رقم الهاتف','placeholder' => '05xxxxxxxx','maxlength' => '10','inputmode' => 'numeric','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'phone','label' => 'رقم الهاتف','placeholder' => '05xxxxxxxx','maxlength' => '10','inputmode' => 'numeric','required' => true,'col' => 'col-md-6']); ?>
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
                        </div>

                        <?php if (isset($component)) { $__componentOriginal5577b9b6837ac6a3fb049e5d2e21d52b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5577b9b6837ac6a3fb049e5d2e21d52b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.actions','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            <?php if (isset($component)) { $__componentOriginal279ed4aee8936495973b201156bb1c1c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal279ed4aee8936495973b201156bb1c1c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.previous-button','data' => ['href' => route('admin.users.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.previous-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.users.index'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal279ed4aee8936495973b201156bb1c1c)): ?>
<?php $attributes = $__attributesOriginal279ed4aee8936495973b201156bb1c1c; ?>
<?php unset($__attributesOriginal279ed4aee8936495973b201156bb1c1c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal279ed4aee8936495973b201156bb1c1c)): ?>
<?php $component = $__componentOriginal279ed4aee8936495973b201156bb1c1c; ?>
<?php unset($__componentOriginal279ed4aee8936495973b201156bb1c1c); ?>
<?php endif; ?>
                            <div class="d-flex gap-2">
                                <?php if (isset($component)) { $__componentOriginal0281d78d2d16329c5a422ee3e125697d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0281d78d2d16329c5a422ee3e125697d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.draft-button','data' => ['step' => '1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.draft-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['step' => '1']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0281d78d2d16329c5a422ee3e125697d)): ?>
<?php $attributes = $__attributesOriginal0281d78d2d16329c5a422ee3e125697d; ?>
<?php unset($__attributesOriginal0281d78d2d16329c5a422ee3e125697d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0281d78d2d16329c5a422ee3e125697d)): ?>
<?php $component = $__componentOriginal0281d78d2d16329c5a422ee3e125697d; ?>
<?php unset($__componentOriginal0281d78d2d16329c5a422ee3e125697d); ?>
<?php endif; ?>
                                <?php if (isset($component)) { $__componentOriginald8384fc4bda10eddedaeeb73d5c4c0d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald8384fc4bda10eddedaeeb73d5c4c0d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.next-button','data' => ['disabled' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.next-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['disabled' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald8384fc4bda10eddedaeeb73d5c4c0d4)): ?>
<?php $attributes = $__attributesOriginald8384fc4bda10eddedaeeb73d5c4c0d4; ?>
<?php unset($__attributesOriginald8384fc4bda10eddedaeeb73d5c4c0d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald8384fc4bda10eddedaeeb73d5c4c0d4)): ?>
<?php $component = $__componentOriginald8384fc4bda10eddedaeeb73d5c4c0d4; ?>
<?php unset($__componentOriginald8384fc4bda10eddedaeeb73d5c4c0d4); ?>
<?php endif; ?>
                            </div>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5577b9b6837ac6a3fb049e5d2e21d52b)): ?>
<?php $attributes = $__attributesOriginal5577b9b6837ac6a3fb049e5d2e21d52b; ?>
<?php unset($__attributesOriginal5577b9b6837ac6a3fb049e5d2e21d52b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5577b9b6837ac6a3fb049e5d2e21d52b)): ?>
<?php $component = $__componentOriginal5577b9b6837ac6a3fb049e5d2e21d52b; ?>
<?php unset($__componentOriginal5577b9b6837ac6a3fb049e5d2e21d52b); ?>
<?php endif; ?>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalff513ee38c008c02d1b14306a4fc4344)): ?>
<?php $attributes = $__attributesOriginalff513ee38c008c02d1b14306a4fc4344; ?>
<?php unset($__attributesOriginalff513ee38c008c02d1b14306a4fc4344); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalff513ee38c008c02d1b14306a4fc4344)): ?>
<?php $component = $__componentOriginalff513ee38c008c02d1b14306a4fc4344; ?>
<?php unset($__componentOriginalff513ee38c008c02d1b14306a4fc4344); ?>
<?php endif; ?>

                    <?php if (isset($component)) { $__componentOriginalff513ee38c008c02d1b14306a4fc4344 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalff513ee38c008c02d1b14306a4fc4344 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.step','data' => ['id' => 'security-info-validation','fields' => ['password', 'password_confirmation', 'image']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.step'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'security-info-validation','fields' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['password', 'password_confirmation', 'image'])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        <div class="row g-4">
                            <?php if (isset($component)) { $__componentOriginal6a58491022d9221e27dd03c346c4f73d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6a58491022d9221e27dd03c346c4f73d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.password','data' => ['name' => 'password','label' => 'كلمة المرور','placeholder' => 'ادخل كلمة المرور','dataValidationDependents' => 'password_confirmation','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.password'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'password','label' => 'كلمة المرور','placeholder' => 'ادخل كلمة المرور','data-validation-dependents' => 'password_confirmation','required' => true,'col' => 'col-md-6']); ?>
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
                            <?php if (isset($component)) { $__componentOriginal6a58491022d9221e27dd03c346c4f73d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6a58491022d9221e27dd03c346c4f73d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.password','data' => ['name' => 'password_confirmation','label' => 'تأكيد كلمة المرور','placeholder' => 'أعد إدخال كلمة المرور','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.password'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'password_confirmation','label' => 'تأكيد كلمة المرور','placeholder' => 'أعد إدخال كلمة المرور','required' => true,'col' => 'col-md-6']); ?>
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
                            <?php if (isset($component)) { $__componentOriginal11b5b9178a9c1eda35daaf55682d0789 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal11b5b9178a9c1eda35daaf55682d0789 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.image-upload','data' => ['name' => 'image','label' => 'الصورة الشخصية','col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.image-upload'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'image','label' => 'الصورة الشخصية','col' => 'col-md-6']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal11b5b9178a9c1eda35daaf55682d0789)): ?>
<?php $attributes = $__attributesOriginal11b5b9178a9c1eda35daaf55682d0789; ?>
<?php unset($__attributesOriginal11b5b9178a9c1eda35daaf55682d0789); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal11b5b9178a9c1eda35daaf55682d0789)): ?>
<?php $component = $__componentOriginal11b5b9178a9c1eda35daaf55682d0789; ?>
<?php unset($__componentOriginal11b5b9178a9c1eda35daaf55682d0789); ?>
<?php endif; ?>
                        </div>

                        <?php if (isset($component)) { $__componentOriginal5577b9b6837ac6a3fb049e5d2e21d52b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5577b9b6837ac6a3fb049e5d2e21d52b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.actions','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            <?php if (isset($component)) { $__componentOriginal279ed4aee8936495973b201156bb1c1c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal279ed4aee8936495973b201156bb1c1c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.previous-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.previous-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal279ed4aee8936495973b201156bb1c1c)): ?>
<?php $attributes = $__attributesOriginal279ed4aee8936495973b201156bb1c1c; ?>
<?php unset($__attributesOriginal279ed4aee8936495973b201156bb1c1c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal279ed4aee8936495973b201156bb1c1c)): ?>
<?php $component = $__componentOriginal279ed4aee8936495973b201156bb1c1c; ?>
<?php unset($__componentOriginal279ed4aee8936495973b201156bb1c1c); ?>
<?php endif; ?>
                            <div class="d-flex gap-2">
                                <?php if (isset($component)) { $__componentOriginal0281d78d2d16329c5a422ee3e125697d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0281d78d2d16329c5a422ee3e125697d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.draft-button','data' => ['step' => '2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.draft-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['step' => '2']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0281d78d2d16329c5a422ee3e125697d)): ?>
<?php $attributes = $__attributesOriginal0281d78d2d16329c5a422ee3e125697d; ?>
<?php unset($__attributesOriginal0281d78d2d16329c5a422ee3e125697d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0281d78d2d16329c5a422ee3e125697d)): ?>
<?php $component = $__componentOriginal0281d78d2d16329c5a422ee3e125697d; ?>
<?php unset($__componentOriginal0281d78d2d16329c5a422ee3e125697d); ?>
<?php endif; ?>
                                <?php if (isset($component)) { $__componentOriginalb20595fa0342c8b5948bf38523b09578 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb20595fa0342c8b5948bf38523b09578 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.save-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.save-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb20595fa0342c8b5948bf38523b09578)): ?>
<?php $attributes = $__attributesOriginalb20595fa0342c8b5948bf38523b09578; ?>
<?php unset($__attributesOriginalb20595fa0342c8b5948bf38523b09578); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb20595fa0342c8b5948bf38523b09578)): ?>
<?php $component = $__componentOriginalb20595fa0342c8b5948bf38523b09578; ?>
<?php unset($__componentOriginalb20595fa0342c8b5948bf38523b09578); ?>
<?php endif; ?>
                            </div>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5577b9b6837ac6a3fb049e5d2e21d52b)): ?>
<?php $attributes = $__attributesOriginal5577b9b6837ac6a3fb049e5d2e21d52b; ?>
<?php unset($__attributesOriginal5577b9b6837ac6a3fb049e5d2e21d52b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5577b9b6837ac6a3fb049e5d2e21d52b)): ?>
<?php $component = $__componentOriginal5577b9b6837ac6a3fb049e5d2e21d52b; ?>
<?php unset($__componentOriginal5577b9b6837ac6a3fb049e5d2e21d52b); ?>
<?php endif; ?>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalff513ee38c008c02d1b14306a4fc4344)): ?>
<?php $attributes = $__attributesOriginalff513ee38c008c02d1b14306a4fc4344; ?>
<?php unset($__attributesOriginalff513ee38c008c02d1b14306a4fc4344); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalff513ee38c008c02d1b14306a4fc4344)): ?>
<?php $component = $__componentOriginalff513ee38c008c02d1b14306a4fc4344; ?>
<?php unset($__componentOriginalff513ee38c008c02d1b14306a4fc4344); ?>
<?php endif; ?>
                </form>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc72a709c96cbcb3f6949070279ea94e8)): ?>
<?php $attributes = $__attributesOriginalc72a709c96cbcb3f6949070279ea94e8; ?>
<?php unset($__attributesOriginalc72a709c96cbcb3f6949070279ea94e8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc72a709c96cbcb3f6949070279ea94e8)): ?>
<?php $component = $__componentOriginalc72a709c96cbcb3f6949070279ea94e8; ?>
<?php unset($__componentOriginalc72a709c96cbcb3f6949070279ea94e8); ?>
<?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/admin/users/create.blade.php ENDPATH**/ ?>