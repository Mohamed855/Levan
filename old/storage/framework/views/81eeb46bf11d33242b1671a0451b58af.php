<?php $__env->startSection('title', 'إضافة عقار جديد'); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('toastr'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/dataTable.css', 'resources/assets/scss/tenant/pages/assignment-create.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <script>
        window.routes = window.routes || {};
        window.propertyUnitStatuses = <?php echo json_encode(array_map(fn($case) => ['value' => $case->value, 'label' => $case->label()], \App\Enums\Tenants\Properties\PropertyUnitStatus::cases())) ?>;
        window.propertyUnitTypes = <?php echo json_encode(array_map(fn($case) => ['value' => $case->value, 'label' => $case->label()], \App\Enums\Tenants\Properties\PropertyUnitType::cases())) ?>;
        window.propertyUnitUsages = <?php echo json_encode(array_map(fn($case) => ['value' => $case->value, 'label' => $case->label()], \App\Enums\Tenants\Properties\PropertyUnitUsage::cases())) ?>;
    </script>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenant/properties/create.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $steps = [
            ['id' => 'property-basic-validation', 'title' => 'البيانات الأساسية'],
            ['id' => 'property-location-validation', 'title' => 'الموقع والحدود'],
            ['id' => 'property-ownership-validation', 'title' => 'الملكية والتفاصيل'],
            ['id' => 'property-files-validation', 'title' => 'المستندات والصور'],
            ['id' => 'property-review-validation', 'title' => 'المراجعة والحفظ'],
        ];
    ?>

    <?php if (isset($component)) { $__componentOriginalc72a709c96cbcb3f6949070279ea94e8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc72a709c96cbcb3f6949070279ea94e8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.stepper','data' => ['id' => 'wizard-validation','class' => 'mt-2','steps' => $steps]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.stepper'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'wizard-validation','class' => 'mt-2','steps' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($steps)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <li><?php echo e($error); ?></li>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </ul>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <form id="property-create-form" action="<?php echo e(tenant_route_name('properties.store')); ?>" method="POST"
            enctype="multipart/form-data" autocomplete="off" data-form-validate
            data-validation-url="<?php echo e(tenant_route_name('wizard-drafts.validate-step')); ?>"
            data-draft-url="<?php echo e(tenant_route_name('wizard-drafts.store')); ?>"
            data-draft-file-url="<?php echo e(tenant_route_name('wizard-drafts.file.destroy')); ?>"
            data-draft-resource-key="tenant.property-create">
            <?php echo csrf_field(); ?>

            <input type="hidden" name="property_number" value="<?php echo e($generatedPropertyNumber); ?>">

            
            <?php if (isset($component)) { $__componentOriginalff513ee38c008c02d1b14306a4fc4344 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalff513ee38c008c02d1b14306a4fc4344 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.step','data' => ['id' => 'property-basic-validation','fields' => ['name', 'type', 'usage_classification', 'area', 'is_active', 'short_description']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.step'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'property-basic-validation','fields' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['name', 'type', 'usage_classification', 'area', 'is_active', 'short_description'])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <div class="row g-4">
                    <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'name','label' => 'اسم العقار','placeholder' => 'مثال: فيلا النخيل','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'name','label' => 'اسم العقار','placeholder' => 'مثال: فيلا النخيل','required' => true,'col' => 'col-md-6']); ?>
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
                    <?php if (isset($component)) { $__componentOriginale539220c855c3bd8ed4510f56a521559 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale539220c855c3bd8ed4510f56a521559 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'type','label' => 'نوع العقار','required' => true,'col' => 'col-md-6','enum' => \App\Enums\Tenants\Properties\PropertyType::class]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'type','label' => 'نوع العقار','required' => true,'col' => 'col-md-6','enum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Enums\Tenants\Properties\PropertyType::class)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale539220c855c3bd8ed4510f56a521559)): ?>
<?php $attributes = $__attributesOriginale539220c855c3bd8ed4510f56a521559; ?>
<?php unset($__attributesOriginale539220c855c3bd8ed4510f56a521559); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale539220c855c3bd8ed4510f56a521559)): ?>
<?php $component = $__componentOriginale539220c855c3bd8ed4510f56a521559; ?>
<?php unset($__componentOriginale539220c855c3bd8ed4510f56a521559); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginale539220c855c3bd8ed4510f56a521559 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale539220c855c3bd8ed4510f56a521559 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'usage_classification','label' => 'تصنيف الاستخدام','required' => true,'col' => 'col-md-6','enum' => \App\Enums\Tenants\Properties\PropertyUsageClassification::class]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'usage_classification','label' => 'تصنيف الاستخدام','required' => true,'col' => 'col-md-6','enum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Enums\Tenants\Properties\PropertyUsageClassification::class)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale539220c855c3bd8ed4510f56a521559)): ?>
<?php $attributes = $__attributesOriginale539220c855c3bd8ed4510f56a521559; ?>
<?php unset($__attributesOriginale539220c855c3bd8ed4510f56a521559); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale539220c855c3bd8ed4510f56a521559)): ?>
<?php $component = $__componentOriginale539220c855c3bd8ed4510f56a521559; ?>
<?php unset($__componentOriginale539220c855c3bd8ed4510f56a521559); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'area','type' => 'number','step' => '0.01','label' => 'المساحة (م²)','placeholder' => 'مثال: 600','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'area','type' => 'number','step' => '0.01','label' => 'المساحة (م²)','placeholder' => 'مثال: 600','required' => true,'col' => 'col-md-6']); ?>
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
                    <?php if (isset($component)) { $__componentOriginale539220c855c3bd8ed4510f56a521559 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale539220c855c3bd8ed4510f56a521559 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'is_active','label' => 'حالة العقار','required' => true,'col' => 'col-md-6','enum' => \App\Enums\Shared\ActivationStatus::class,'value' => '1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'is_active','label' => 'حالة العقار','required' => true,'col' => 'col-md-6','enum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Enums\Shared\ActivationStatus::class),'value' => '1']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale539220c855c3bd8ed4510f56a521559)): ?>
<?php $attributes = $__attributesOriginale539220c855c3bd8ed4510f56a521559; ?>
<?php unset($__attributesOriginale539220c855c3bd8ed4510f56a521559); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale539220c855c3bd8ed4510f56a521559)): ?>
<?php $component = $__componentOriginale539220c855c3bd8ed4510f56a521559; ?>
<?php unset($__componentOriginale539220c855c3bd8ed4510f56a521559); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.textarea','data' => ['name' => 'short_description','label' => 'وصف العقار','placeholder' => 'أدخل وصفاً للعقار...','required' => true,'col' => 'col-md-6','rows' => '4','maxlength' => '500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'short_description','label' => 'وصف العقار','placeholder' => 'أدخل وصفاً للعقار...','required' => true,'col' => 'col-md-6','rows' => '4','maxlength' => '500']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d)): ?>
<?php $attributes = $__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d; ?>
<?php unset($__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d)): ?>
<?php $component = $__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d; ?>
<?php unset($__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d); ?>
<?php endif; ?>

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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.previous-button','data' => ['href' => tenant_route_name('properties.index'),'text' => 'العودة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.previous-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(tenant_route_name('properties.index')),'text' => 'العودة']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.draft-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.draft-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.next-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.next-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
                </div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.step','data' => ['id' => 'property-location-validation','fields' => [
                'city',
                'district',
                'administrative_region',
                'plan_number',
                'parcel_number',
                'aerial_image',
                'boundary_geojson',
                'boundaries',
            ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.step'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'property-location-validation','fields' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
                'city',
                'district',
                'administrative_region',
                'plan_number',
                'parcel_number',
                'aerial_image',
                'boundary_geojson',
                'boundaries',
            ])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <input type="hidden" name="latitude" id="latitude" value="<?php echo e(old('latitude')); ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?php echo e(old('longitude')); ?>">
                <?php
                    $defaultBorders = [
                        [
                            'direction' => 'north',
                            'border_type' => 'street',
                            'border_description' => '',
                            'length' => 0,
                        ],
                        [
                            'direction' => 'south',
                            'border_type' => 'parcel',
                            'border_description' => '',
                            'length' => 0,
                        ],
                        [
                            'direction' => 'east',
                            'border_type' => 'parcel',
                            'border_description' => '',
                            'length' => 0,
                        ],
                        [
                            'direction' => 'west',
                            'border_type' => 'street',
                            'border_description' => '',
                            'length' => 0,
                        ],
                    ];
                    $bordersData = old('boundaries', $defaultBorders);
                ?>

                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="card assignment-properties-table-card h-100">
                            <div class="card-body">
                                <div class="row g-4">
                                    <?php if (isset($component)) { $__componentOriginale539220c855c3bd8ed4510f56a521559 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale539220c855c3bd8ed4510f56a521559 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'city','label' => 'المدينة','required' => true,'col' => 'col-md-6','options' => $cityOptions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'city','label' => 'المدينة','required' => true,'col' => 'col-md-6','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cityOptions)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale539220c855c3bd8ed4510f56a521559)): ?>
<?php $attributes = $__attributesOriginale539220c855c3bd8ed4510f56a521559; ?>
<?php unset($__attributesOriginale539220c855c3bd8ed4510f56a521559); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale539220c855c3bd8ed4510f56a521559)): ?>
<?php $component = $__componentOriginale539220c855c3bd8ed4510f56a521559; ?>
<?php unset($__componentOriginale539220c855c3bd8ed4510f56a521559); ?>
<?php endif; ?>
                                    <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'district','label' => 'الحي / النطاق','placeholder' => 'مثال: حي العارض','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'district','label' => 'الحي / النطاق','placeholder' => 'مثال: حي العارض','required' => true,'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'administrative_region','label' => 'المنطقة الإدارية','placeholder' => 'مثال: منطقة الرياض','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'administrative_region','label' => 'المنطقة الإدارية','placeholder' => 'مثال: منطقة الرياض','required' => true,'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'plan_number','type' => 'number','step' => '1','min' => '0','label' => 'رقم المخطط','placeholder' => 'مثال: 2357','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'plan_number','type' => 'number','step' => '1','min' => '0','label' => 'رقم المخطط','placeholder' => 'مثال: 2357','required' => true,'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'parcel_number','type' => 'number','step' => '1','min' => '0','label' => 'رقم القطعة','placeholder' => 'مثال: 1288','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'parcel_number','type' => 'number','step' => '1','min' => '0','label' => 'رقم القطعة','placeholder' => 'مثال: 1288','required' => true,'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'google_maps_url','label' => 'رابط الموقع','placeholder' => 'https://maps.google.com','required' => false,'col' => 'col-md-6','help' => 'رابط موقع العقار على خرائط جوجل إن وُجد.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'google_maps_url','label' => 'رابط الموقع','placeholder' => 'https://maps.google.com','required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'col' => 'col-md-6','help' => 'رابط موقع العقار على خرائط جوجل إن وُجد.']); ?>
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
                                <?php echo $__env->make('tenant.properties._boundaries-table', [
                                    'bordersData' => $bordersData,
                                ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <?php echo $__env->make('tenant.properties._location-map-card', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.draft-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.draft-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.next-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.next-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
                </div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.step','data' => ['id' => 'property-ownership-validation','fields' => ['deed_number', 'deed_date', 'issuing_authority', 'ownership_type', 'owners']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.step'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'property-ownership-validation','fields' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['deed_number', 'deed_date', 'issuing_authority', 'ownership_type', 'owners'])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <div class="row g-4">
                    <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'deed_number','type' => 'number','step' => '1','min' => '0','label' => 'رقم الصك','placeholder' => 'أدخل رقم الصك','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'deed_number','type' => 'number','step' => '1','min' => '0','label' => 'رقم الصك','placeholder' => 'أدخل رقم الصك','required' => true,'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'deed_date','type' => 'date','label' => 'تاريخ الصك','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'deed_date','type' => 'date','label' => 'تاريخ الصك','required' => true,'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'issuing_authority','label' => 'جهة الإصدار','placeholder' => 'مثال: كتابة العدل الأولى بالرياض','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'issuing_authority','label' => 'جهة الإصدار','placeholder' => 'مثال: كتابة العدل الأولى بالرياض','required' => true,'col' => 'col-md-6']); ?>
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
                    <?php if (isset($component)) { $__componentOriginale539220c855c3bd8ed4510f56a521559 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale539220c855c3bd8ed4510f56a521559 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'ownership_type','label' => 'نوع الملكية','required' => true,'col' => 'col-md-6','enum' => \App\Enums\Tenants\Properties\PropertyOwnershipType::class]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'ownership_type','label' => 'نوع الملكية','required' => true,'col' => 'col-md-6','enum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Enums\Tenants\Properties\PropertyOwnershipType::class)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale539220c855c3bd8ed4510f56a521559)): ?>
<?php $attributes = $__attributesOriginale539220c855c3bd8ed4510f56a521559; ?>
<?php unset($__attributesOriginale539220c855c3bd8ed4510f56a521559); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale539220c855c3bd8ed4510f56a521559)): ?>
<?php $component = $__componentOriginale539220c855c3bd8ed4510f56a521559; ?>
<?php unset($__componentOriginale539220c855c3bd8ed4510f56a521559); ?>
<?php endif; ?>

                    
                    <div class="col-12">
                        <div class="card assignment-properties-table-card">
                            <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-0"><i class="ti ti-users me-2 text-primary"></i>بيانات الملاك
                                    </h5>
                                    <small class="text-muted">يمكنك إضافة مالك واحد أو أكثر للعقار</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary" id="btn-add-owner">
                                    <i class="ti ti-plus me-1"></i> إضافة مالك آخر
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="owners-list" data-form-field data-field-name="owners">
                                    <?php
                                        $ownersData = old('owners', [['owner_name' => '', 'national_id_or_cr' => '']]);
                                    ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $ownersData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $owner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <div class="owner-item card border p-3 mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0 text-muted owner-number">المالك #<?php echo e($idx + 1); ?></h6>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($idx > 0): ?>
                                                    <button type="button"
                                                        class="btn btn-icon btn-sm btn-label-danger btn-remove-owner"
                                                        aria-label="حذف المالك">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6" data-form-field
                                                    data-field-name="owners[<?php echo e($idx); ?>][owner_name]">
                                                    <label class="form-label">اسم المالك <span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="owners[<?php echo e($idx); ?>][owner_name]"
                                                        class="form-control" value="<?php echo e($owner['owner_name'] ?? ''); ?>"
                                                        placeholder="أدخل اسم المالك" data-form-control required>
                                                    <div class="invalid-feedback" data-form-error hidden></div>
                                                </div>
                                                <div class="col-md-6" data-form-field
                                                    data-field-name="owners[<?php echo e($idx); ?>][national_id_or_cr]">
                                                    <label class="form-label">رقم الهوية / السجل التجاري <span
                                                            class="text-danger">*</span></label>
                                                    <input type="number" step="1" min="0"
                                                        name="owners[<?php echo e($idx); ?>][national_id_or_cr]"
                                                        class="form-control"
                                                        value="<?php echo e($owner['national_id_or_cr'] ?? ''); ?>"
                                                        placeholder="أدخل رقم الهوية أو السجل" data-form-control required>
                                                    <div class="invalid-feedback" data-form-error hidden></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'agent_name','label' => 'اسم الوكيل (اختياري)','placeholder' => 'أدخل اسم الوكيل','required' => false,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'agent_name','label' => 'اسم الوكيل (اختياري)','placeholder' => 'أدخل اسم الوكيل','required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'agent_national_id','type' => 'number','step' => '1','min' => '0','label' => 'رقم هوية الوكيل (اختياري)','placeholder' => 'أدخل رقم هوية الوكيل','required' => false,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'agent_national_id','type' => 'number','step' => '1','min' => '0','label' => 'رقم هوية الوكيل (اختياري)','placeholder' => 'أدخل رقم هوية الوكيل','required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'agency_number','type' => 'number','step' => '1','min' => '0','label' => 'رقم الوكالة (اختياري)','placeholder' => 'أدخل رقم الوكالة','required' => false,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'agency_number','type' => 'number','step' => '1','min' => '0','label' => 'رقم الوكالة (اختياري)','placeholder' => 'أدخل رقم الوكالة','required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'agency_date','type' => 'date','label' => 'تاريخ الوكالة (اختياري)','required' => false,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'agency_date','type' => 'date','label' => 'تاريخ الوكالة (اختياري)','required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'col' => 'col-md-6']); ?>
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

                    
                    <div class="col-12">
                        <div class="card assignment-properties-table-card">
                            <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-0"><i class="ti ti-layout-grid me-2 text-primary"></i>وحدات
                                        العقار (اختياري)</h5>
                                    <small class="text-muted">إذا كان العقار يحتوي على وحدات متعددة (شقق، محلات،
                                        ملحقات)</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary" id="btn-add-unit">
                                    <i class="ti ti-plus me-1"></i> إضافة وحدة جديدة
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" data-form-field data-field-name="units">
                                    <table class="table table-striped table-bordered w-100">
                                        <thead>
                                            <tr>
                                                <th style="width: 15%;">رقم الوحدة</th>
                                                <th style="width: 20%;">نوع الوحدة</th>
                                                <th style="width: 15%;">المساحة (م²)</th>
                                                <th style="width: 20%;">الاستخدام الحالي</th>
                                                <th style="width: 20%;">الحالة</th>
                                                <th style="width: 10%;" class="text-center">حذف</th>
                                            </tr>
                                        </thead>
                                        <tbody id="units-list">
                                            <?php
                                                $unitsData = old('units', []);
                                            ?>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $unitsData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <tr class="unit-row">
                                                    <td><input type="text"
                                                            name="units[<?php echo e($idx); ?>][unit_number]"
                                                            class="form-control form-control-sm"
                                                            value="<?php echo e($unit['unit_number'] ?? ''); ?>"
                                                            placeholder="وحدة ..." data-form-control></td>
                                                    <td><select name="units[<?php echo e($idx); ?>][unit_type]"
                                                            class="form-select form-select-sm select2"
                                                            data-select2-search="false" data-form-control>
                                                            <option value=""></option>
                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Enums\Tenants\Properties\PropertyUnitType::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unitType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                                <option value="<?php echo e($unitType->value); ?>"
                                                                    <?php if(($unit['unit_type'] ?? '') === $unitType->value): echo 'selected'; endif; ?>><?php echo e($unitType->label()); ?>

                                                                </option>
                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                        </select></td>
                                                    <td><input type="number" step="0.01"
                                                            name="units[<?php echo e($idx); ?>][area]"
                                                            class="form-control form-control-sm"
                                                            value="<?php echo e($unit['area'] ?? ''); ?>" placeholder="المساحة"
                                                            data-form-control></td>
                                                    <td><select name="units[<?php echo e($idx); ?>][current_use]"
                                                            class="form-select form-select-sm select2"
                                                            data-select2-search="false" data-form-control>
                                                            <option value=""></option>
                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Enums\Tenants\Properties\PropertyUnitUsage::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unitUsage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                                <option value="<?php echo e($unitUsage->value); ?>"
                                                                    <?php if(($unit['current_use'] ?? '') === $unitUsage->value): echo 'selected'; endif; ?>><?php echo e($unitUsage->label()); ?>

                                                                </option>
                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                        </select></td>
                                                    <td>
                                                        <select name="units[<?php echo e($idx); ?>][status]"
                                                            class="form-select form-select-sm select2"
                                                            data-select2-search="false" data-form-control>
                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = \App\Enums\Tenants\Properties\PropertyUnitStatus::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unitStatus): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                                <option value="<?php echo e($unitStatus->value); ?>"
                                                                    <?php echo e(($unit['status'] ?? '') === $unitStatus->value ? 'selected' : ''); ?>>
                                                                    <?php echo e($unitStatus->label()); ?>

                                                                </option>
                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                        </select>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button"
                                                            class="btn btn-icon btn-sm btn-label-danger btn-remove-unit"
                                                            aria-label="حذف الوحدة"><i class="ti ti-trash"></i></button>
                                                    </td>
                                                </tr>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.draft-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.draft-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.next-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.next-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
                </div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.step','data' => ['id' => 'property-files-validation','fields' => ['main_image', 'deed_document', 'cadastral_plan', 'coordinates_statement', 'building_permit']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.step'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'property-files-validation','fields' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['main_image', 'deed_document', 'cadastral_plan', 'coordinates_statement', 'building_permit'])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <div class="row g-4">
                    <?php if (isset($component)) { $__componentOriginal11b5b9178a9c1eda35daaf55682d0789 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal11b5b9178a9c1eda35daaf55682d0789 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.image-upload','data' => ['name' => 'main_image','label' => 'الصورة الرئيسية للعقار','required' => true,'accept' => 'image/jpeg,image/png,image/webp','hint' => 'JPG أو PNG أو WebP، بحد أقصى 4MB','col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.image-upload'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'main_image','label' => 'الصورة الرئيسية للعقار','required' => true,'accept' => 'image/jpeg,image/png,image/webp','hint' => 'JPG أو PNG أو WebP، بحد أقصى 4MB','col' => 'col-md-6']); ?>
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
                    <?php if (isset($component)) { $__componentOriginal72797368dd93e7a440c907f094db4622 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal72797368dd93e7a440c907f094db4622 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.file','data' => ['name' => 'deed_document','label' => 'صك الملكية','required' => true,'accept' => '.pdf,image/*','help' => 'PDF أو صورة، بحد أقصى 10MB','col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'deed_document','label' => 'صك الملكية','required' => true,'accept' => '.pdf,image/*','help' => 'PDF أو صورة، بحد أقصى 10MB','col' => 'col-md-6']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal72797368dd93e7a440c907f094db4622)): ?>
<?php $attributes = $__attributesOriginal72797368dd93e7a440c907f094db4622; ?>
<?php unset($__attributesOriginal72797368dd93e7a440c907f094db4622); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal72797368dd93e7a440c907f094db4622)): ?>
<?php $component = $__componentOriginal72797368dd93e7a440c907f094db4622; ?>
<?php unset($__componentOriginal72797368dd93e7a440c907f094db4622); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal72797368dd93e7a440c907f094db4622 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal72797368dd93e7a440c907f094db4622 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.file','data' => ['name' => 'cadastral_plan','label' => 'مخطط العقار','required' => true,'accept' => '.pdf,image/*','help' => 'PDF أو صورة، بحد أقصى 10MB','col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'cadastral_plan','label' => 'مخطط العقار','required' => true,'accept' => '.pdf,image/*','help' => 'PDF أو صورة، بحد أقصى 10MB','col' => 'col-md-6']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal72797368dd93e7a440c907f094db4622)): ?>
<?php $attributes = $__attributesOriginal72797368dd93e7a440c907f094db4622; ?>
<?php unset($__attributesOriginal72797368dd93e7a440c907f094db4622); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal72797368dd93e7a440c907f094db4622)): ?>
<?php $component = $__componentOriginal72797368dd93e7a440c907f094db4622; ?>
<?php unset($__componentOriginal72797368dd93e7a440c907f094db4622); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal72797368dd93e7a440c907f094db4622 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal72797368dd93e7a440c907f094db4622 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.file','data' => ['name' => 'coordinates_statement','label' => 'كشف الإحداثيات','required' => true,'accept' => '.pdf,image/*','help' => 'PDF أو صورة، بحد أقصى 10MB','col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'coordinates_statement','label' => 'كشف الإحداثيات','required' => true,'accept' => '.pdf,image/*','help' => 'PDF أو صورة، بحد أقصى 10MB','col' => 'col-md-6']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal72797368dd93e7a440c907f094db4622)): ?>
<?php $attributes = $__attributesOriginal72797368dd93e7a440c907f094db4622; ?>
<?php unset($__attributesOriginal72797368dd93e7a440c907f094db4622); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal72797368dd93e7a440c907f094db4622)): ?>
<?php $component = $__componentOriginal72797368dd93e7a440c907f094db4622; ?>
<?php unset($__componentOriginal72797368dd93e7a440c907f094db4622); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal72797368dd93e7a440c907f094db4622 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal72797368dd93e7a440c907f094db4622 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.file','data' => ['name' => 'building_permit','label' => 'رخصة البناء (اختياري)','accept' => '.pdf,image/*','help' => 'PDF أو صورة، بحد أقصى 10MB','required' => false,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'building_permit','label' => 'رخصة البناء (اختياري)','accept' => '.pdf,image/*','help' => 'PDF أو صورة، بحد أقصى 10MB','required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'col' => 'col-md-6']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal72797368dd93e7a440c907f094db4622)): ?>
<?php $attributes = $__attributesOriginal72797368dd93e7a440c907f094db4622; ?>
<?php unset($__attributesOriginal72797368dd93e7a440c907f094db4622); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal72797368dd93e7a440c907f094db4622)): ?>
<?php $component = $__componentOriginal72797368dd93e7a440c907f094db4622; ?>
<?php unset($__componentOriginal72797368dd93e7a440c907f094db4622); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal72797368dd93e7a440c907f094db4622 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal72797368dd93e7a440c907f094db4622 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.file','data' => ['name' => 'assignment_letter_image','label' => 'صورة الإسناد (اختياري)','accept' => 'image/*','help' => 'صورة فقط بحد أقصى 4MB','required' => false,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'assignment_letter_image','label' => 'صورة الإسناد (اختياري)','accept' => 'image/*','help' => 'صورة فقط بحد أقصى 4MB','required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'col' => 'col-md-6']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal72797368dd93e7a440c907f094db4622)): ?>
<?php $attributes = $__attributesOriginal72797368dd93e7a440c907f094db4622; ?>
<?php unset($__attributesOriginal72797368dd93e7a440c907f094db4622); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal72797368dd93e7a440c907f094db4622)): ?>
<?php $component = $__componentOriginal72797368dd93e7a440c907f094db4622; ?>
<?php unset($__componentOriginal72797368dd93e7a440c907f094db4622); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal72797368dd93e7a440c907f094db4622 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal72797368dd93e7a440c907f094db4622 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.file','data' => ['name' => 'property_images[]','label' => 'صور إضافية للمعرض (اختياري)','multiple' => true,'accept' => 'image/*','help' => 'يمكن اختيار عدة صور للمعرض','required' => false,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'property_images[]','label' => 'صور إضافية للمعرض (اختياري)','multiple' => true,'accept' => 'image/*','help' => 'يمكن اختيار عدة صور للمعرض','required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'col' => 'col-md-6']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal72797368dd93e7a440c907f094db4622)): ?>
<?php $attributes = $__attributesOriginal72797368dd93e7a440c907f094db4622; ?>
<?php unset($__attributesOriginal72797368dd93e7a440c907f094db4622); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal72797368dd93e7a440c907f094db4622)): ?>
<?php $component = $__componentOriginal72797368dd93e7a440c907f094db4622; ?>
<?php unset($__componentOriginal72797368dd93e7a440c907f094db4622); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal72797368dd93e7a440c907f094db4622 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal72797368dd93e7a440c907f094db4622 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.file','data' => ['name' => 'additional_documents[]','label' => 'مستندات إضافية (اختياري)','multiple' => true,'accept' => '.pdf,image/*','help' => 'يمكن اختيار عدة ملفات','required' => false,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'additional_documents[]','label' => 'مستندات إضافية (اختياري)','multiple' => true,'accept' => '.pdf,image/*','help' => 'يمكن اختيار عدة ملفات','required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'col' => 'col-md-6']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal72797368dd93e7a440c907f094db4622)): ?>
<?php $attributes = $__attributesOriginal72797368dd93e7a440c907f094db4622; ?>
<?php unset($__attributesOriginal72797368dd93e7a440c907f094db4622); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal72797368dd93e7a440c907f094db4622)): ?>
<?php $component = $__componentOriginal72797368dd93e7a440c907f094db4622; ?>
<?php unset($__componentOriginal72797368dd93e7a440c907f094db4622); ?>
<?php endif; ?>

                    <div class="col-md-6">
                        <section class="assignment-properties-table-card h-100" aria-live="polite">
                            <header class="assignment-properties-table-card__header">
                                <h6 class="mb-0">صور إضافية للمعرض</h6>
                                <span id="property-images-count" class="badge bg-label-primary">0 ملف</span>
                            </header>
                            <div id="property-images-cards" class="assignment-file-list p-3">
                                <p class="text-muted mb-0">لم يتم إرفاق ملفات بعد.</p>
                            </div>
                        </section>
                    </div>
                    <div class="col-md-6">
                        <section class="assignment-properties-table-card h-100" aria-live="polite">
                            <header class="assignment-properties-table-card__header">
                                <h6 class="mb-0">مستندات إضافية</h6>
                                <span id="additional-documents-count" class="badge bg-label-primary">0 ملف</span>
                            </header>
                            <div id="additional-documents-cards" class="assignment-file-list p-3">
                                <p class="text-muted mb-0">لم يتم إرفاق ملفات بعد.</p>
                            </div>
                        </section>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.draft-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.draft-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.next-button','data' => ['text' => 'المراجعة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.next-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['text' => 'المراجعة']); ?>
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
                </div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.step','data' => ['id' => 'property-review-validation','fields' => []]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.step'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'property-review-validation','fields' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <div class="row g-4 assignment-review">
                    <div class="col-lg-9 d-flex flex-column gap-4">
                        <?php if (isset($component)) { $__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.review-card','data' => ['title' => 'البيانات الأساسية','step' => '1','bodyId' => 'review-basic','class' => 'assignment-review__card']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.review-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'البيانات الأساسية','step' => '1','body-id' => 'review-basic','class' => 'assignment-review__card']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f)): ?>
<?php $attributes = $__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f; ?>
<?php unset($__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f)): ?>
<?php $component = $__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f; ?>
<?php unset($__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.review-card','data' => ['title' => 'الموقع والحدود','step' => '2','bodyId' => 'review-location','class' => 'assignment-review__card']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.review-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'الموقع والحدود','step' => '2','body-id' => 'review-location','class' => 'assignment-review__card']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f)): ?>
<?php $attributes = $__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f; ?>
<?php unset($__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f)): ?>
<?php $component = $__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f; ?>
<?php unset($__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.review-card','data' => ['title' => 'الملكية والتفاصيل','step' => '3','bodyId' => 'review-ownership','class' => 'assignment-review__card']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.review-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'الملكية والتفاصيل','step' => '3','body-id' => 'review-ownership','class' => 'assignment-review__card']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f)): ?>
<?php $attributes = $__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f; ?>
<?php unset($__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f)): ?>
<?php $component = $__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f; ?>
<?php unset($__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.review-card','data' => ['title' => 'الملفات والمستندات','step' => '4','bodyId' => 'review-files','class' => 'assignment-review__card']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.review-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'الملفات والمستندات','step' => '4','body-id' => 'review-files','class' => 'assignment-review__card']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            <div id="review-files" class="assignment-file-list"></div>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f)): ?>
<?php $attributes = $__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f; ?>
<?php unset($__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f)): ?>
<?php $component = $__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f; ?>
<?php unset($__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f); ?>
<?php endif; ?>
                        <div class="card assignment-review__card">
                            <div class="card-body">
                                <?php if (isset($component)) { $__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.textarea','data' => ['name' => 'notes','label' => 'ملاحظات إضافية (اختياري)','placeholder' => 'أدخل أي ملاحظات إدارية أو توجيهات إضافية للعقار...','required' => false,'col' => 'col-md-6','rows' => '3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'notes','label' => 'ملاحظات إضافية (اختياري)','placeholder' => 'أدخل أي ملاحظات إدارية أو توجيهات إضافية للعقار...','required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'col' => 'col-md-6','rows' => '3']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d)): ?>
<?php $attributes = $__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d; ?>
<?php unset($__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d)): ?>
<?php $component = $__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d; ?>
<?php unset($__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d); ?>
<?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <article class="card assignment-preview h-100">
                            <img id="review-image" src="<?php echo e(asset('static/brand/logos/logo.svg')); ?>"
                                alt="معاينة صورة العقار" class="assignment-preview__image">
                            <div class="card-body">
                                <h5 id="review-name">اسم العقار</h5>
                                <p id="review-description" class="text-muted mb-0">وصف العقار</p>
                            </div>
                        </article>
                    </div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.draft-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.draft-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.save-button','data' => ['text' => 'حفظ العقار']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.save-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['text' => 'حفظ العقار']); ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('tenant.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/tenant/properties/create.blade.php ENDPATH**/ ?>