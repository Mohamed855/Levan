<?php $__env->startSection('title', 'المستحدمين '); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js']); ?>
    <?php echo $dataTable->scripts(); ?>

<?php $__env->stopSection(); ?>


<?php $__env->startSection('page-script'); ?>
    <script>
        window.routes = {
            massDelete: "<?php echo e(route('admin.users.bulk-delete')); ?>",
            getUserRoles: "<?php echo e(route('admin.roles.get-user-roles', ':id')); ?>",
            updateUserRole: "<?php echo e(route('admin.roles.update-user-role', ':id')); ?>"
        };
    </script>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/admin/users/index.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('toastr'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/dataTable.css']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalfb5d8a43e6657040fc9bfa64e27d0a61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfb5d8a43e6657040fc9bfa64e27d0a61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::data-table.page','data' => ['tableId' => 'users-table']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table.page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['table-id' => 'users-table']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('stats', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['col' => 'col-sm-6 col-xl','title' => 'إجمالي المستخدمين','value' => $totalUsers,'subtitle' => 'إجمالي المستخدمين','icon' => 'ti-users','color' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['col' => 'col-sm-6 col-xl','title' => 'إجمالي المستخدمين','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalUsers),'subtitle' => 'إجمالي المستخدمين','icon' => 'ti-users','color' => 'primary']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['col' => 'col-sm-6 col-xl','title' => 'المستخدمين النشطين','value' => $activeUsers,'subtitle' => 'المستخدمين النشطين','icon' => 'ti-user-check','color' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['col' => 'col-sm-6 col-xl','title' => 'المستخدمين النشطين','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activeUsers),'subtitle' => 'المستخدمين النشطين','icon' => 'ti-user-check','color' => 'success']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['col' => 'col-sm-6 col-xl','title' => 'مدراء النظام','value' => $superAdminUsers,'subtitle' => 'صلاحيات كاملة','icon' => 'ti-shield-lock','color' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['col' => 'col-sm-6 col-xl','title' => 'مدراء النظام','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($superAdminUsers),'subtitle' => 'صلاحيات كاملة','icon' => 'ti-shield-lock','color' => 'info']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['col' => 'col-sm-6 col-xl','title' => 'المستخدمين غير النشطين','value' => $inactiveUsers,'subtitle' => 'المستخدمين غير النشطين','icon' => 'ti-user-off','color' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['col' => 'col-sm-6 col-xl','title' => 'المستخدمين غير النشطين','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($inactiveUsers),'subtitle' => 'المستخدمين غير النشطين','icon' => 'ti-user-off','color' => 'warning']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['col' => 'col-sm-6 col-xl','title' => 'المستخدمين المؤرشفين','value' => $archivedUsers,'subtitle' => 'يمكن استعادتها من الأرشيف','icon' => 'ti-archive','color' => 'danger']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['col' => 'col-sm-6 col-xl','title' => 'المستخدمين المؤرشفين','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($archivedUsers),'subtitle' => 'يمكن استعادتها من الأرشيف','icon' => 'ti-archive','color' => 'danger']); ?>
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
         <?php $__env->endSlot(); ?>

         <?php $__env->slot('filters', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginal74d5498727cf9b815b5e58ed36f23d91 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74d5498727cf9b815b5e58ed36f23d91 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::data-table.filters','data' => ['table' => '#users-table']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table.filters'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['table' => '#users-table']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <?php if (isset($component)) { $__componentOriginal3721828986c0d93618ebcde1401641f8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3721828986c0d93618ebcde1401641f8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::filter.select','data' => ['id' => 'filter-status','filterKey' => 'status','table' => '#users-table','label' => 'حالة المستخدمين','placeholder' => 'اختر حالة المستخدم','options' => $filterOptions['statuses']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filter.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'filter-status','filterKey' => 'status','table' => '#users-table','label' => 'حالة المستخدمين','placeholder' => 'اختر حالة المستخدم','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filterOptions['statuses'])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3721828986c0d93618ebcde1401641f8)): ?>
<?php $attributes = $__attributesOriginal3721828986c0d93618ebcde1401641f8; ?>
<?php unset($__attributesOriginal3721828986c0d93618ebcde1401641f8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3721828986c0d93618ebcde1401641f8)): ?>
<?php $component = $__componentOriginal3721828986c0d93618ebcde1401641f8; ?>
<?php unset($__componentOriginal3721828986c0d93618ebcde1401641f8); ?>
<?php endif; ?>
                <?php if (isset($component)) { $__componentOriginal3721828986c0d93618ebcde1401641f8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3721828986c0d93618ebcde1401641f8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::filter.select','data' => ['id' => 'filter-role','filterKey' => 'role','table' => '#users-table','label' => 'دور المستخدم','placeholder' => 'اختر الدور','options' => $filterOptions['roles']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filter.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'filter-role','filterKey' => 'role','table' => '#users-table','label' => 'دور المستخدم','placeholder' => 'اختر الدور','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($filterOptions['roles'])]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3721828986c0d93618ebcde1401641f8)): ?>
<?php $attributes = $__attributesOriginal3721828986c0d93618ebcde1401641f8; ?>
<?php unset($__attributesOriginal3721828986c0d93618ebcde1401641f8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3721828986c0d93618ebcde1401641f8)): ?>
<?php $component = $__componentOriginal3721828986c0d93618ebcde1401641f8; ?>
<?php unset($__componentOriginal3721828986c0d93618ebcde1401641f8); ?>
<?php endif; ?>
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
         <?php $__env->endSlot(); ?>

         <?php $__env->slot('table', null, []); ?> 
            <?php echo $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'users-table'], false); ?>

         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfb5d8a43e6657040fc9bfa64e27d0a61)): ?>
<?php $attributes = $__attributesOriginalfb5d8a43e6657040fc9bfa64e27d0a61; ?>
<?php unset($__attributesOriginalfb5d8a43e6657040fc9bfa64e27d0a61); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfb5d8a43e6657040fc9bfa64e27d0a61)): ?>
<?php $component = $__componentOriginalfb5d8a43e6657040fc9bfa64e27d0a61; ?>
<?php unset($__componentOriginalfb5d8a43e6657040fc9bfa64e27d0a61); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal9e53668333b26fec389b3b94e7223def = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9e53668333b26fec389b3b94e7223def = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::bootstrap.modal','data' => ['id' => 'editUserRoleModal','title' => 'تعديل دور المشرف','icon' => 'ti-user-shield']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('bootstrap.modal'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'editUserRoleModal','title' => 'تعديل دور المشرف','icon' => 'ti-user-shield']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <form id="updateUserRoleForm">
            <input id="role-assignment-user-id" type="hidden" name="user_id">
            <?php if (isset($component)) { $__componentOriginale539220c855c3bd8ed4510f56a521559 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale539220c855c3bd8ed4510f56a521559 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.select','data' => ['name' => 'role','id' => 'role-assignment-role','label' => 'الدور','options' => $roles,'placeholder' => 'اختر الدور','required' => true,'col' => 'col-12']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'role','id' => 'role-assignment-role','label' => 'الدور','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($roles),'placeholder' => 'اختر الدور','required' => true,'col' => 'col-12']); ?>
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
        </form>

         <?php $__env->slot('footer', null, []); ?> 
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="submit" form="updateUserRoleForm" class="btn btn-primary">حفظ التغيير</button>
         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9e53668333b26fec389b3b94e7223def)): ?>
<?php $attributes = $__attributesOriginal9e53668333b26fec389b3b94e7223def; ?>
<?php unset($__attributesOriginal9e53668333b26fec389b3b94e7223def); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9e53668333b26fec389b3b94e7223def)): ?>
<?php $component = $__componentOriginal9e53668333b26fec389b3b94e7223def; ?>
<?php unset($__componentOriginal9e53668333b26fec389b3b94e7223def); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/admin/users/index.blade.php ENDPATH**/ ?>