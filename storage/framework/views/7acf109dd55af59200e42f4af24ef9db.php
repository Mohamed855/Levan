<?php $__env->startSection('title', 'إنشاء مزاد جديد'); ?>

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
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenant/auctions/create.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $steps = [
            ['id' => 'step-basic-info', 'title' => 'البيانات الأساسية'],
            ['id' => 'step-assignment', 'title' => 'الإسناد والمنصة'],
            ['id' => 'step-properties', 'title' => 'العقارات المرتبطة'],
            ['id' => 'step-settings', 'title' => 'الإعدادات والملفات'],
            ['id' => 'step-review', 'title' => 'المراجعة والإرسال'],
        ];
    ?>

    <div class="row">
        <div class="col-12 mb-6">
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

                <form id="wizard-validation-form" action="<?php echo e(tenant_route_name('auctions.store')); ?>" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="<?php echo e(tenant_route_name('wizard-drafts.validate-step')); ?>"
                    data-draft-url="<?php echo e(tenant_route_name('wizard-drafts.store')); ?>"
                    data-draft-file-url="<?php echo e(tenant_route_name('wizard-drafts.file.destroy')); ?>"
                    data-assignment-properties-url="<?php echo e(tenant_route_name('assignments.properties', ':id')); ?>"
                    data-property-image-fallback="<?php echo e(asset('static/brand/logos/logo.svg')); ?>"
                    data-draft-resource-key="tenant.auction-create">
                    <?php echo csrf_field(); ?>

                    
                    <?php if (isset($component)) { $__componentOriginalff513ee38c008c02d1b14306a4fc4344 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalff513ee38c008c02d1b14306a4fc4344 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.step','data' => ['id' => 'step-basic-info','fields' => [
                        'name',
                        'description',
                        'start_date',
                        'start_time',
                        'end_date',
                        'end_time',
                        'type',
                        'category',
                        'city',
                        'expected_properties_count',
                    ]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.step'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'step-basic-info','fields' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
                        'name',
                        'description',
                        'start_date',
                        'start_time',
                        'end_date',
                        'end_time',
                        'type',
                        'category',
                        'city',
                        'expected_properties_count',
                    ])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        <div class="row g-4">
                            <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'name','label' => 'اسم المزاد','placeholder' => 'مثال: مزاد جوار الحرم','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'name','label' => 'اسم المزاد','placeholder' => 'مثال: مزاد جوار الحرم','required' => true,'col' => 'col-md-6']); ?>
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

                            <?php if (isset($component)) { $__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.textarea','data' => ['name' => 'description','label' => 'وصف المزاد','placeholder' => 'اكتب وصفاً توضيحياً للمزاد...','required' => true,'col' => 'col-md-6','rows' => '3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'description','label' => 'وصف المزاد','placeholder' => 'اكتب وصفاً توضيحياً للمزاد...','required' => true,'col' => 'col-md-6','rows' => '3']); ?>
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

                            <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'start_date','type' => 'date','label' => 'تاريخ بداية المزاد','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'start_date','type' => 'date','label' => 'تاريخ بداية المزاد','required' => true,'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'start_time','type' => 'time','label' => 'وقت البداية','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'start_time','type' => 'time','label' => 'وقت البداية','required' => true,'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'end_date','type' => 'date','label' => 'تاريخ نهاية المزاد','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'end_date','type' => 'date','label' => 'تاريخ نهاية المزاد','required' => true,'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'end_time','type' => 'time','label' => 'وقت النهاية','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'end_time','type' => 'time','label' => 'وقت النهاية','required' => true,'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'type','label' => 'نوع المزاد','required' => true,'col' => 'col-md-6','enum' => \App\Enums\Tenants\Auctions\AuctionType::class]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'type','label' => 'نوع المزاد','required' => true,'col' => 'col-md-6','enum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Enums\Tenants\Auctions\AuctionType::class)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'category','label' => 'تصنيف المزاد','required' => true,'col' => 'col-md-6','enum' => \App\Enums\Tenants\Auctions\AuctionCategory::class]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'category','label' => 'تصنيف المزاد','required' => true,'col' => 'col-md-6','enum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Enums\Tenants\Auctions\AuctionCategory::class)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'city','label' => 'المدينة الرئيسية','required' => true,'col' => 'col-md-6','options' => $cityOptions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'city','label' => 'المدينة الرئيسية','required' => true,'col' => 'col-md-6','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cityOptions)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'expected_properties_count','type' => 'number','label' => 'عدد العقارات المتوقع','value' => 1,'min' => '1','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'expected_properties_count','type' => 'number','label' => 'عدد العقارات المتوقع','value' => 1,'min' => '1','required' => true,'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.previous-button','data' => ['href' => tenant_route_name('auctions.index'),'text' => 'العودة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.previous-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(tenant_route_name('auctions.index')),'text' => 'العودة']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.step','data' => ['id' => 'step-assignment','fields' => ['assignment_id', 'supervising_entity', 'platform_name', 'platform_url', 'notes']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.step'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'step-assignment','fields' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['assignment_id', 'supervising_entity', 'platform_name', 'platform_url', 'notes'])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        <div class="row g-4">
                            <?php if (isset($component)) { $__componentOriginale539220c855c3bd8ed4510f56a521559 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale539220c855c3bd8ed4510f56a521559 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'assignment_id','id' => 'assignment_id','label' => 'الإسناد المرتبط بالمزاد','required' => true,'col' => 'col-md-6','placeholder' => 'اختر الإسناد المسجل...','options' => $assignments
                                    ->map(
                                        fn($a) => [
                                            'value' => $a->id,
                                            'label' => $a->name . ' (' . $a->assignment_number . ')',
                                        ],
                                    )
                                    ->toArray()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'assignment_id','id' => 'assignment_id','label' => 'الإسناد المرتبط بالمزاد','required' => true,'col' => 'col-md-6','placeholder' => 'اختر الإسناد المسجل...','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($assignments
                                    ->map(
                                        fn($a) => [
                                            'value' => $a->id,
                                            'label' => $a->name . ' (' . $a->assignment_number . ')',
                                        ],
                                    )
                                    ->toArray())]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                 <?php $__env->slot('append', null, []); ?> 
                                    
                                    <div id="selected-assignment-card"
                                        class="card bg-label-primary border-primary mt-3 d-none">
                                        <div class="card-body p-3">
                                            <h6 class="fw-bold mb-2"><i class="ti ti-file-certificate me-1"></i>بيانات
                                                الإسناد
                                                المختار:</h6>
                                            <div class="small">
                                                <div><strong>رقم الإسناد:</strong> <span id="asg-summary-num">-</span></div>
                                                <div><strong>الجهة المسندة:</strong> <span id="asg-summary-entity">-</span>
                                                </div>
                                                <div><strong>الحالة:</strong> <span id="asg-summary-status">-</span></div>
                                            </div>
                                        </div>
                                    </div>
                                 <?php $__env->endSlot(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'supervising_entity','id' => 'supervising_entity','label' => 'الجهة المشرفة على المزاد','placeholder' => 'مثال: مركز الإسناد والتصفية - إنفاذ','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'supervising_entity','id' => 'supervising_entity','label' => 'الجهة المشرفة على المزاد','placeholder' => 'مثال: مركز الإسناد والتصفية - إنفاذ','required' => true,'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'platform_name','label' => 'منصة المزاد','placeholder' => 'مثال: منصة مباشر للمزادات','required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'platform_name','label' => 'منصة المزاد','placeholder' => 'مثال: منصة مباشر للمزادات','required' => true,'col' => 'col-md-6']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'platform_url','type' => 'url','label' => 'رابط المزاد على المنصة','placeholder' => 'https://mubasher.sa/auctions/...','col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'platform_url','type' => 'url','label' => 'رابط المزاد على المنصة','placeholder' => 'https://mubasher.sa/auctions/...','col' => 'col-md-6']); ?>
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

                            <?php if (isset($component)) { $__componentOriginal88a864a7ea1ec37963d2b2fde69fd17d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal88a864a7ea1ec37963d2b2fde69fd17d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.textarea','data' => ['name' => 'notes','label' => 'ملاحظات الجهة والمنصة','placeholder' => 'أي تفاصيل أو اشتراطات خاصة بالجهة أو المنصة...','col' => 'col-md-6','rows' => '3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.textarea'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'notes','label' => 'ملاحظات الجهة والمنصة','placeholder' => 'أي تفاصيل أو اشتراطات خاصة بالجهة أو المنصة...','col' => 'col-md-6','rows' => '3']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.step','data' => ['id' => 'step-properties','fields' => ['property_ids']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.step'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'step-properties','fields' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['property_ids'])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        <div data-form-field data-field-name="property_ids">
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">العقارات المرتبطة بالإسناد المختار</h5>
                                    <small class="text-muted">حدد العقارات التي سيتم إدراجها رسمياً في هذا المزاد.</small>
                                </div>
                                <div class="d-flex gap-3 text-start">
                                    <div class="badge bg-label-primary fs-6 p-2">المحددة: <span
                                            id="selected-props-count">0</span></div>
                                    <div class="badge bg-label-info fs-6 p-2">إجمالي المساحة: <span
                                            id="selected-props-area">0
                                            م²</span></div>
                                    <div class="badge bg-label-success fs-6 p-2">إجمالي القيمة: <span
                                            id="selected-props-val">0 ر.س</span></div>
                                </div>
                            </div>

                            <div class="table-responsive border rounded mb-4">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 2.5rem;">
                                                <input type="checkbox" id="select-all-properties"
                                                    class="form-check-input">
                                            </th>
                                            <th>رقم العقار</th>
                                            <th>اسم العقار</th>
                                            <th>المدينة / الحي</th>
                                            <th>المساحة</th>
                                            <th>القيمة التقديرية</th>
                                        </tr>
                                    </thead>
                                    <tbody id="properties-container">
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                يرجى اختيار الإسناد في الخطوة السابقة لعرض عقاراته التابعة
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="property_ids-error" class="invalid-feedback d-block" data-form-error role="alert"
                                hidden></div>
                        </div>

                        <section class="assignment-selection mt-4" aria-live="polite">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="mb-0">العقارات المحددة</h5>
                                <span class="badge bg-label-primary"><span id="selected-properties-count">0</span>
                                    عقار</span>
                            </div>
                            <div id="selected-properties-cards" class="assignment-selection__items">
                                <p class="text-muted mb-0" data-empty>لم يتم اختيار عقارات بعد.</p>
                            </div>
                        </section>

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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.step','data' => ['id' => 'step-settings','fields' => ['template_versions', 'cover_image', 'attachments']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.step'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'step-settings','fields' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['template_versions', 'cover_image', 'attachments'])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        <div class="row g-4">
                            <div class="col-12">
                                <h6 class="fw-bold mb-3"><i class="ti ti-palette me-1"></i>القوالب التسويقية المنشورة
                                    والمعتمدة:
                                </h6>
                            </div>

                            <?php if (isset($component)) { $__componentOriginale539220c855c3bd8ed4510f56a521559 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale539220c855c3bd8ed4510f56a521559 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'template_versions[brochure]','id' => 'template_versions_brochure','label' => 'قالب البروشور المعتمد','col' => 'col-md-4','options' => $templateOptions['brochure'] ?? $brochureTemplates,'placeholder' => 'اختر نسخة قالب البروشور...']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'template_versions[brochure]','id' => 'template_versions_brochure','label' => 'قالب البروشور المعتمد','col' => 'col-md-4','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($templateOptions['brochure'] ?? $brochureTemplates),'placeholder' => 'اختر نسخة قالب البروشور...']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'template_versions[billboard]','id' => 'template_versions_billboard','label' => 'قالب اللوحة الإعلانية','col' => 'col-md-4','options' => $templateOptions['billboard'] ?? $billboardTemplates,'placeholder' => 'اختر نسخة قالب اللوحة...']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'template_versions[billboard]','id' => 'template_versions_billboard','label' => 'قالب اللوحة الإعلانية','col' => 'col-md-4','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($templateOptions['billboard'] ?? $billboardTemplates),'placeholder' => 'اختر نسخة قالب اللوحة...']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'template_versions[social]','id' => 'template_versions_social','label' => 'قالب منصات التواصل','col' => 'col-md-4','options' => $templateOptions['social'] ?? $socialTemplates,'placeholder' => 'اختر نسخة قالب منصات التواصل...']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'template_versions[social]','id' => 'template_versions_social','label' => 'قالب منصات التواصل','col' => 'col-md-4','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($templateOptions['social'] ?? $socialTemplates),'placeholder' => 'اختر نسخة قالب منصات التواصل...']); ?>
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
                                <?php if (isset($component)) { $__componentOriginal72797368dd93e7a440c907f094db4622 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal72797368dd93e7a440c907f094db4622 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.file','data' => ['name' => 'cover_image','label' => 'صورة غلاف المزاد (للاستخدام في القوالب)','accept' => 'image/*','col' => 'col-md-6','help' => 'صورة تظهر في مخرجات المزاد والقوالب التسويقية']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'cover_image','label' => 'صورة غلاف المزاد (للاستخدام في القوالب)','accept' => 'image/*','col' => 'col-md-6','help' => 'صورة تظهر في مخرجات المزاد والقوالب التسويقية']); ?>
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
                            </div>

                            <div class="col-12 mt-4 border-top pt-3">
                                <h6 class="fw-bold mb-3"><i class="ti ti-paperclip me-1"></i>مرفقات ووثائق المزاد:</h6>
                                <?php if (isset($component)) { $__componentOriginal72797368dd93e7a440c907f094db4622 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal72797368dd93e7a440c907f094db4622 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.file','data' => ['name' => 'attachments[]','label' => 'ملفات إضافية','multiple' => true,'required' => false,'accept' => '.pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx','help' => 'الحد الأقصى لكل ملف: 10MB','col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.file'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'attachments[]','label' => 'ملفات إضافية','multiple' => true,'required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'accept' => '.pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx','help' => 'الحد الأقصى لكل ملف: 10MB','col' => 'col-md-6']); ?>
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
                                <section class="assignment-properties-table-card mt-3" aria-live="polite">
                                    <header class="assignment-properties-table-card__header">
                                        <h6 class="mb-0">المرفقات والوثائق</h6>
                                        <span id="auction-attachments-count" class="badge bg-label-primary">0 ملف</span>
                                    </header>
                                    <div id="auction-attachments-cards" class="assignment-file-list p-3">
                                        <p class="text-muted mb-0">لم يتم إرفاق ملفات بعد.</p>
                                    </div>
                                </section>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.step','data' => ['id' => 'step-review','fields' => ['status']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.step'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'step-review','fields' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['status'])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        <div class="row g-4 mb-4 assignment-review">
                            <div class="col-lg-9 d-flex flex-column gap-4">
                                <?php if (isset($component)) { $__componentOriginalb029f2f1dd8fab9d9155f29ff58aec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb029f2f1dd8fab9d9155f29ff58aec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.review-card','data' => ['title' => 'البيانات الأساسية','step' => '1','class' => 'assignment-review__card']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.review-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'البيانات الأساسية','step' => '1','class' => 'assignment-review__card']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                    <div><strong>اسم المزاد:</strong> <span id="review-name">-</span></div>
                                    <div><strong>النوع والتصنيف:</strong> <span id="review-type">-</span> / <span
                                            id="review-category">-</span></div>
                                    <div><strong>المدينة:</strong> <span id="review-city">-</span></div>
                                    <div><strong>الفترة:</strong> <span id="review-dates">-</span></div>
                                    <div><strong>الوصف:</strong> <span id="review-description">-</span></div>
                                    <div><strong>عدد العقارات المتوقع:</strong> <span
                                            id="review-expected-properties">-</span></div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.review-card','data' => ['title' => 'الإسناد والمنصة','step' => '2','class' => 'assignment-review__card']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.review-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'الإسناد والمنصة','step' => '2','class' => 'assignment-review__card']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                    <div><strong>الإسناد:</strong> <span id="review-assignment-name">-</span></div>
                                    <div><strong>الجهة المشرفة:</strong> <span id="review-supervising">-</span></div>
                                    <div><strong>المنصة:</strong> <span id="review-platform">-</span></div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.review-card','data' => ['title' => 'العقارات المشمولة','step' => '3','class' => 'assignment-review__card']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.review-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'العقارات المشمولة','step' => '3','class' => 'assignment-review__card']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                    <div><strong>العقارات المحددة:</strong> <span id="review-selected-props-count">0
                                            عقارات</span></div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.review-card','data' => ['title' => 'الإعدادات والملفات','step' => '4','class' => 'assignment-review__card']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.review-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'الإعدادات والملفات','step' => '4','class' => 'assignment-review__card']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                    <div><strong>قالب البروشور:</strong> <span id="review-brochure-template">-</span></div>
                                    <div><strong>قالب اللوحة الإعلانية:</strong> <span
                                            id="review-billboard-template">-</span></div>
                                    <div><strong>قالب منصات التواصل:</strong> <span id="review-social-template">-</span>
                                    </div>
                                    <div><strong>المرفقات والوثائق:</strong> <span id="review-attachments-count">0
                                            ملف</span></div>
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
                            </div>
                            <div class="col-lg-3">
                                <article class="card assignment-preview h-100">
                                    <div
                                        class="assignment-preview__image bg-primary d-flex flex-column justify-content-center p-4 text-white">
                                        <h5 class="text-white mb-1" id="preview-auction-name">اسم المزاد</h5>
                                        <small class="text-white-50" id="preview-auction-category">التصنيف</small>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1" id="preview-auction-city">المدينة</p>
                                        <p class="text-muted mb-0" id="preview-auction-date">تاريخ الانطلاق</p>
                                    </div>
                                </article>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
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
                                <button type="submit" name="status" value="pending_approval" class="btn btn-primary">
                                    <i class="ti ti-send me-1"></i> إرسال للاعتماد
                                </button>
                            </div>
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

<?php echo $__env->make('tenant.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/tenant/auctions/create.blade.php ENDPATH**/ ?>