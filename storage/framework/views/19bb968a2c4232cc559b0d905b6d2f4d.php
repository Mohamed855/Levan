<?php $__env->startSection('title', 'المستخدمين'); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/shepherd/shepherd.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/shepherd/shepherd.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('toastr'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/dataTable.css']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <script>
        // متغيرات JavaScript العامة
        window.routes = {
            usersIndex: "<?php echo e(tenant_route_name('users.index')); ?>",
            usersCreate: "<?php echo e(tenant_route_name('users.create')); ?>",
            toggleStatus: "<?php echo e(tenant_route_name('users.toggle-status', ['user' => '___ID___'])); ?>",
            massDelete: "<?php echo e(tenant_route_name('mass-delete')); ?>"
        };

        window.assets = {
            arabicDataTable: "<?php echo e(asset('static/i18n/ar.json')); ?>"
        };

        window.licenseRoutes = {
            assign: "<?php echo e(tenant_route_name('licenses.assign')); ?>",
            release: "<?php echo e(tenant_route_name('licenses.release')); ?>",
            subscriptions: "<?php echo e(tenant_route_name('subscriptions.index')); ?>"
        };

        window.csrfToken = "<?php echo e(csrf_token()); ?>";
    </script>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/tenant/user-management/user/index.js', 'resources/assets/js/tenant/user-management/user/license.js']); ?>

    <?php echo $dataTable->scripts(); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <li><?php echo e($error); ?></li>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </ul>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <div id="top_card" class="row g-4 mb-4">
        <?php if (isset($component)) { $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::ui.stat-card','data' => ['col' => 'col-sm-6 col-xl-3','title' => 'المستخدمين','value' => $totalUsers,'subtitle' => 'إجمالي المستخدمين','icon' => 'ti-users','color' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['col' => 'col-sm-6 col-xl-3','title' => 'المستخدمين','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalUsers),'subtitle' => 'إجمالي المستخدمين','icon' => 'ti-users','color' => 'primary']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::ui.stat-card','data' => ['col' => 'col-sm-6 col-xl-3','title' => 'المستخدمين النشطين','value' => $activeUsers,'subtitle' => 'المستخدمين النشطين','icon' => 'ti-user-check','color' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['col' => 'col-sm-6 col-xl-3','title' => 'المستخدمين النشطين','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activeUsers),'subtitle' => 'المستخدمين النشطين','icon' => 'ti-user-check','color' => 'success']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::ui.stat-card','data' => ['col' => 'col-sm-6 col-xl-3','title' => 'المستخدمين غير النشطين','value' => $inactiveUsers,'subtitle' => 'المستخدمين غير النشطين','icon' => 'ti-user-off','color' => 'danger']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['col' => 'col-sm-6 col-xl-3','title' => 'المستخدمين غير النشطين','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($inactiveUsers),'subtitle' => 'المستخدمين غير النشطين','icon' => 'ti-user-off','color' => 'danger']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::ui.stat-card','data' => ['col' => 'col-sm-6 col-xl-3','title' => 'التراخيص','value' => ($subscription_formatted['used_users'] ?? 0) . ' / ' . ($subscription_formatted['total_users'] ?? 0),'subtitle' => 'متبقي ' .
            (($subscription_formatted['total_users'] ?? 0) - ($subscription_formatted['used_users'] ?? 0)) .
            ' ترخيص','icon' => 'ti-license','color' => 'primary','valueClass' => 'badge bg-label-primary rounded-pill']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['col' => 'col-sm-6 col-xl-3','title' => 'التراخيص','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(($subscription_formatted['used_users'] ?? 0) . ' / ' . ($subscription_formatted['total_users'] ?? 0)),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('متبقي ' .
            (($subscription_formatted['total_users'] ?? 0) - ($subscription_formatted['used_users'] ?? 0)) .
            ' ترخيص'),'icon' => 'ti-license','color' => 'primary','valueClass' => 'badge bg-label-primary rounded-pill']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <div class="text-end">
                <?php echo e($subscription_formatted['total_users'] > 0 ? round(($subscription_formatted['used_users'] / $subscription_formatted['total_users']) * 100) : 0); ?>%
            </div>
            <div class="progress" style="height: 0.5rem;">
                <div class="progress-bar bg-primary" role="progressbar"
                    style="width: <?php echo e($subscription_formatted['total_users'] > 0 ? ($subscription_formatted['used_users'] / $subscription_formatted['total_users']) * 100 : 0); ?>%"
                    aria-valuenow="<?php echo e($subscription_formatted['used_users'] ?? 0); ?>" aria-valuemin="0"
                    aria-valuemax="<?php echo e($subscription_formatted['total_users'] ?? 0); ?>">
                </div>
            </div>
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


    
    <?php if (isset($component)) { $__componentOriginal74d5498727cf9b815b5e58ed36f23d91 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74d5498727cf9b815b5e58ed36f23d91 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::data-table.filters','data' => ['table' => '#users-table','resetId' => 'btn-reset-filters']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table.filters'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['table' => '#users-table','reset-id' => 'btn-reset-filters']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <div>
            <label class="form-label" for="filter-status">حالة المستخدم</label>
            <select id="filter-status" class="form-control select2" data-datatable-filter="true" data-filter-key="status"
                data-placeholder="اختر حالة المستخدم">
                <option value=""></option>
                <?php if (isset($component)) { $__componentOriginal7f6bdf735af0a3d44188aae34f98c42a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7f6bdf735af0a3d44188aae34f98c42a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::form.controls.enum-options','data' => ['enum' => \App\Enums\Shared\ActivityStatus::class]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.enum-options'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['enum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Enums\Shared\ActivityStatus::class)]); ?>
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
        </div>

        <div>
            <label class="form-label" for="filter-role">دور المستخدم</label>
            <select id="filter-role" class="form-control select2" data-datatable-filter="true" data-filter-key="role"
                data-placeholder="اختر الدور">
                <option value=""></option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <option value="<?php echo e($role->id); ?>"><?php echo e($role->name); ?></option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </select>
        </div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74d5498727cf9b815b5e58ed36f23d91)): ?>
<?php $attributes = $__attributesOriginal74d5498727cf9b815b5e58ed36f23d91; ?>
<?php unset($__attributesOriginal74d5498727cf9b815b5e58ed36f23d91); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74d5498727cf9b815b5e58ed36f23d91)): ?>
<?php $component = $__componentOriginal74d5498727cf9b815b5e58ed36f23d91; ?>
<?php unset($__componentOriginal74d5498727cf9b815b5e58ed36f23d91); ?>
<?php endif; ?>

    <div class="card" id="card-users">
        <div class="card-body">
            <?php echo $dataTable->table(['class' => 'table table-striped table-bordered w-100']); ?>

        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('tenant.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\resources\views/tenant/user-management/users/index.blade.php ENDPATH**/ ?>