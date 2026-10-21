<?php $__env->startSection('title', 'لوحة التحكم الرئيسية - LOT'); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('toastr'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/dataTable.css', 'resources/assets/scss/admin/pages/dashboard.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <script>
        window.dashboardData = {
            subscriptionStatus: <?php echo json_encode($subscriptionStatus, 15, 512) ?>,
            platformActivity: <?php echo json_encode($platformActivity, 15, 512) ?>,
            platformActivityUrl: "<?php echo e(route('admin.dashboard.platform-activity')); ?>"
        };
    </script>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/admin/dashboard/index.js', 'resources/assets/js/shared/chart/chart.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row g-4 mb-4">
        <?php if (isset($component)) { $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'طلبات الاشتراك الجديدة','value' => number_format($kpis['new_subscription_requests']),'subtitle' => 'طلبات قيد المراجعة','icon' => 'ti-user-plus','color' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'طلبات الاشتراك الجديدة','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($kpis['new_subscription_requests'])),'subtitle' => 'طلبات قيد المراجعة','icon' => 'ti-user-plus','color' => 'primary']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42)): ?>
<?php $attributes = $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42; ?>
<?php unset($__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42)): ?>
<?php $component = $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42; ?>
<?php unset($__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'الشركات المشتركة','value' => number_format($kpis['subscribed_companies']),'subtitle' => 'شركات لديها اشتراك نشط','icon' => 'ti-building','color' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'الشركات المشتركة','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($kpis['subscribed_companies'])),'subtitle' => 'شركات لديها اشتراك نشط','icon' => 'ti-building','color' => 'success']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42)): ?>
<?php $attributes = $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42; ?>
<?php unset($__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42)): ?>
<?php $component = $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42; ?>
<?php unset($__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'الاشتراكات النشطة','value' => number_format($kpis['active_subscriptions']),'subtitle' => 'اشتراكات فعّالة حالياً','icon' => 'ti-file-text','color' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'الاشتراكات النشطة','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($kpis['active_subscriptions'])),'subtitle' => 'اشتراكات فعّالة حالياً','icon' => 'ti-file-text','color' => 'info']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42)): ?>
<?php $attributes = $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42; ?>
<?php unset($__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42)): ?>
<?php $component = $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42; ?>
<?php unset($__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'الاشتراكات المعلقة','value' => number_format($kpis['suspended_subscriptions']),'subtitle' => 'اشتراكات موقوفة مؤقتاً','icon' => 'ti-clock','color' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'الاشتراكات المعلقة','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($kpis['suspended_subscriptions'])),'subtitle' => 'اشتراكات موقوفة مؤقتاً','icon' => 'ti-clock','color' => 'warning']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42)): ?>
<?php $attributes = $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42; ?>
<?php unset($__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42)): ?>
<?php $component = $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42; ?>
<?php unset($__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'القوالب المركزية','value' => number_format($kpis['central_templates']),'subtitle' => 'قالب متاح للتطبيق','icon' => 'ti-template','color' => 'danger']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'القوالب المركزية','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($kpis['central_templates'])),'subtitle' => 'قالب متاح للتطبيق','icon' => 'ti-template','color' => 'danger']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42)): ?>
<?php $attributes = $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42; ?>
<?php unset($__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42)): ?>
<?php $component = $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42; ?>
<?php unset($__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'المدفوعات هذا الشهر','value' => number_format($kpis['monthly_payments'], 0),'subtitle' => 'ر.س إجمالي هذا الشهر','icon' => 'ti-wallet','color' => 'dark']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'المدفوعات هذا الشهر','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(number_format($kpis['monthly_payments'], 0)),'subtitle' => 'ر.س إجمالي هذا الشهر','icon' => 'ti-wallet','color' => 'dark']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42)): ?>
<?php $attributes = $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42; ?>
<?php unset($__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42)): ?>
<?php $component = $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42; ?>
<?php unset($__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42); ?>
<?php endif; ?>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <?php if (isset($component)) { $__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.dashboard-panel','data' => ['class' => 'h-100','title' => 'استخدام المنصة','icon' => 'ti-chart-bar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.dashboard-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-100','title' => 'استخدام المنصة','icon' => 'ti-chart-bar']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                 <?php $__env->slot('headerAction', null, []); ?> 
                    <select id="platform-activity-range" class="form-select select2 chart-range-select">
                        <?php if (isset($component)) { $__componentOriginal7f6bdf735af0a3d44188aae34f98c42a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7f6bdf735af0a3d44188aae34f98c42a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.enum-options','data' => ['enum' => \App\Enums\Admin\Dashboard\ActivityRange::class,'selected' => null]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.enum-options'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['enum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Enums\Admin\Dashboard\ActivityRange::class),'selected' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(null)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7f6bdf735af0a3d44188aae34f98c42a)): ?>
<?php $attributes = $__attributesOriginal7f6bdf735af0a3d44188aae34f98c42a; ?>
<?php unset($__attributesOriginal7f6bdf735af0a3d44188aae34f98c42a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7f6bdf735af0a3d44188aae34f98c42a)): ?>
<?php $component = $__componentOriginal7f6bdf735af0a3d44188aae34f98c42a; ?>
<?php unset($__componentOriginal7f6bdf735af0a3d44188aae34f98c42a); ?>
<?php endif; ?>
                    </select>
                 <?php $__env->endSlot(); ?>

                <div class="platform-activity-chart-wrap">
                    <canvas id="platformActivityChart"></canvas>
                </div>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c)): ?>
<?php $attributes = $__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c; ?>
<?php unset($__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c)): ?>
<?php $component = $__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c; ?>
<?php unset($__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c); ?>
<?php endif; ?>
        </div>

        <div class="col-md-6">
            <?php if (isset($component)) { $__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.dashboard-panel','data' => ['class' => 'h-100','title' => 'توزيع حالات الاشتراكات','icon' => 'ti-donut','iconColor' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.dashboard-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-100','title' => 'توزيع حالات الاشتراكات','icon' => 'ti-donut','icon-color' => 'info']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <div class="row g-0 row-gap-3 h-100">
                    
                    <div class="col-sm-6 col-lg-12 col-xxl-6 d-flex flex-column justify-content-center pe-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $subscriptionStatus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div class="status-list-row">
                                <span class="status-name-wrap">
                                    <span class="status-dot status-dot-<?php echo e($item['color']); ?>"></span>
                                    <span class="fw-medium status-label"><?php echo e($item['label']); ?></span>
                                </span>
                                <span class="status-pct"><?php echo e($item['percentage']); ?>%</span>
                                <span class="status-count"><?php echo e(number_format($item['count'])); ?></span>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                    <div class="col-sm-6 col-lg-12 col-xxl-6 d-flex align-items-center justify-content-center">
                        <div class="subscription-status-chart-wrap">
                            <canvas id="subscriptionStatusChart"></canvas>
                        </div>
                    </div>
                </div>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c)): ?>
<?php $attributes = $__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c; ?>
<?php unset($__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c)): ?>
<?php $component = $__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c; ?>
<?php unset($__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c); ?>
<?php endif; ?>
        </div>
    </div>

    
    <div class="row g-4 mb-4">
        <div class="col-lg-6 d-flex flex-column gap-3">
            
            <?php if (isset($component)) { $__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.dashboard-panel','data' => ['title' => 'إجراءات سريعة','icon' => 'ti-bolt']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.dashboard-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'إجراءات سريعة','icon' => 'ti-bolt']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <div class="row g-2">
                    <div class="col-4">
                        <a href="<?php echo e(route('admin.clients.create')); ?>" class="quick-action-box">
                            <div class="avatar avatar-md mb-4">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-building fs-4"></i>
                                </span>
                            </div>
                            <span class="action-title">إضافة عميل</span>
                        </a>
                    </div>
                    <div class="col-4">
                        <a href="<?php echo e(route('admin.users.create')); ?>" class="quick-action-box">
                            <div class="avatar avatar-md mb-4">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="ti ti-user-cog fs-4"></i>
                                </span>
                            </div>
                            <span class="action-title">إضافة مستخدم</span>
                        </a>
                    </div>
                    <div class="col-4">
                        <a href="<?php echo e(route('admin.plans.create')); ?>" class="quick-action-box">
                            <div class="avatar avatar-md mb-4">
                                <span class="avatar-initial rounded bg-label-info">
                                    <i class="ti ti-package fs-4"></i>
                                </span>
                            </div>
                            <span class="action-title">إضافة باقة</span>
                        </a>
                    </div>
                    <div class="col-4">
                        <a href="<?php echo e(route('admin.coupons.create')); ?>" class="quick-action-box">
                            <div class="avatar avatar-md mb-4">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class="ti ti-ticket fs-4"></i>
                                </span>
                            </div>
                            <span class="action-title">إضافة كوبون</span>
                        </a>
                    </div>
                    <div class="col-4">
                        <a href="<?php echo e(route('admin.subscriptionManage.expired')); ?>" class="quick-action-box">
                            <div class="avatar avatar-md mb-4">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class="ti ti-timeline-event fs-4"></i>
                                </span>
                            </div>
                            <span class="action-title">تفعيل اشتراك</span>
                        </a>
                    </div>
                    <div class="col-4">
                        <a href="<?php echo e(route('admin.tickets.index')); ?>" class="quick-action-box">
                            <div class="avatar avatar-md mb-4">
                                <span class="avatar-initial rounded bg-label-dark">
                                    <i class="ti ti-messages fs-4"></i>
                                </span>
                            </div>
                            <span class="action-title">الدعم الفنى</span>
                        </a>
                    </div>
                </div>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c)): ?>
<?php $attributes = $__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c; ?>
<?php unset($__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c)): ?>
<?php $component = $__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c; ?>
<?php unset($__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c); ?>
<?php endif; ?>

            
            <?php if (isset($component)) { $__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.dashboard-panel','data' => ['class' => 'flex-grow-1','title' => 'أحدث الأنشطة','icon' => 'ti-clock','bodyClass' => 'p-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.dashboard-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'flex-grow-1','title' => 'أحدث الأنشطة','icon' => 'ti-clock','body-class' => 'p-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                 <?php $__env->slot('headerAction', null, []); ?> 
                    <a href="<?php echo e(route('admin.activity-logs.index')); ?>" class="btn btn-sm btn-label-primary">
                        عرض الكل
                    </a>
                 <?php $__env->endSlot(); ?>

                <div class="table-responsive text-nowrap">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>المستخدم</th>
                                <th>النشاط</th>
                                <th>الوقت</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentActivities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php
                                                $causer = $activity->causer;
                                                $hasCauserImage =
                                                    filled($causer?->image) || filled($causer?->profile_picture);
                                            ?>
                                            <div class="avatar avatar-xs me-2">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasCauserImage && method_exists($causer, 'getProfilePhotoUrlAttribute')): ?>
                                                    <img src="<?php echo e($causer->profile_photo_url); ?>" alt=""
                                                        class="rounded-circle object-fit-cover">
                                                <?php else: ?>
                                                    <span class="avatar-initial rounded-circle bg-secondary">
                                                        <i class="ti ti-user ti-xs"></i>
                                                    </span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                            <span class="fw-medium text-heading small"><?php echo e($activity->causer_name); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <span class="badge bg-label-primary small dashboard-activity-event"
                                                title="<?php echo e($activity->formatted_event); ?>"><?php echo e($activity->formatted_event); ?></span>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activity->formatted_module): ?>
                                                <span class="text-muted small">(<?php echo e($activity->formatted_module); ?>)</span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo e($activity->time_ago); ?></small>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        <i class="ti ti-info-circle fs-4 d-block mb-1"></i>
                                        لا توجد أنشطة حديثة
                                    </td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c)): ?>
<?php $attributes = $__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c; ?>
<?php unset($__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c)): ?>
<?php $component = $__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c; ?>
<?php unset($__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c); ?>
<?php endif; ?>
        </div>

        
        <div class="col-md-6">
            <?php if (isset($component)) { $__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.dashboard-panel','data' => ['class' => 'h-100','title' => 'أحدث طلبات الإشتراك','icon' => 'ti-list','bodyClass' => 'p-0']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.dashboard-panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-100','title' => 'أحدث طلبات الإشتراك','icon' => 'ti-list','body-class' => 'p-0']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                 <?php $__env->slot('headerAction', null, []); ?> 
                    <a href="<?php echo e(route('admin.subscriptionManage.pending')); ?>" class="btn btn-sm btn-label-primary">
                        عرض الكل
                    </a>
                 <?php $__env->endSlot(); ?>

                <div class="table-responsive text-nowrap">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>اسم الشركة</th>
                                <th>الباقة</th>
                                <th>تاريخ الطلب</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $latestSubscriptionRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $request): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr>
                                    <td>
                                        <span class="fw-medium text-heading">
                                            <?php echo e($request->tenant->company_name ?? ($request->tenant_id ?? '-')); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-info">
                                            <?php echo e($request->plan->name ?? '-'); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo e($request->created_at?->format('Y-m-d')); ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?php echo e(number_format($request->price, 2)); ?> ر.س</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-warning">قيد المراجعة</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo e(route('admin.subscriptionManage.pending')); ?>"
                                            class="btn btn-icon btn-label-primary" title="عرض في الطلبات">
                                            <i class="ti ti-arrow-left"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="ti ti-inbox fs-2 d-block mb-2"></i>
                                        لا توجد طلبات اشتراك معلقة حالياً
                                    </td>
                                </tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c)): ?>
<?php $attributes = $__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c; ?>
<?php unset($__attributesOriginal2676f3d4f3dbe60b4c52d287da3df35c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c)): ?>
<?php $component = $__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c; ?>
<?php unset($__componentOriginal2676f3d4f3dbe60b4c52d287da3df35c); ?>
<?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/admin/dashboard/index.blade.php ENDPATH**/ ?>