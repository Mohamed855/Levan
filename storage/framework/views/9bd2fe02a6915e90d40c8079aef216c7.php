<?php $__env->startSection('title', 'قوالب المزادات'); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js']); ?>
    <?php echo $dataTable->scripts(); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('toastr'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/css/shared/dataTable.css']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <script>
        window.routes = {
            massDelete: "<?php echo e(route('admin.mass-delete')); ?>"
        };
    </script>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/admin/auction-templates/index.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php if (isset($component)) { $__componentOriginalfb5d8a43e6657040fc9bfa64e27d0a61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfb5d8a43e6657040fc9bfa64e27d0a61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::data-table.page','data' => ['tableId' => 'auction-templates-table']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table.page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['table-id' => 'auction-templates-table']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('stats', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::ui.stat-card','data' => ['col' => 'col-sm-6 col-xl','title' => 'إجمالي القوالب','value' => $statistics['total'],'subtitle' => 'جميع القوالب المسجلة','icon' => 'ti-files','color' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['col' => 'col-sm-6 col-xl','title' => 'إجمالي القوالب','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statistics['total']),'subtitle' => 'جميع القوالب المسجلة','icon' => 'ti-files','color' => 'primary']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::ui.stat-card','data' => ['col' => 'col-sm-6 col-xl','title' => 'قوالب البروشور','value' => $statistics['brochure'],'subtitle' => 'قوالب الكتيبات التعريفية','icon' => 'ti-file-description','color' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['col' => 'col-sm-6 col-xl','title' => 'قوالب البروشور','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statistics['brochure']),'subtitle' => 'قوالب الكتيبات التعريفية','icon' => 'ti-file-description','color' => 'info']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::ui.stat-card','data' => ['col' => 'col-sm-6 col-xl','title' => 'اللوحات الإعلانية','value' => $statistics['billboard'],'subtitle' => 'قوالب اللوحات الخارجية','icon' => 'ti-ad','color' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['col' => 'col-sm-6 col-xl','title' => 'اللوحات الإعلانية','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statistics['billboard']),'subtitle' => 'قوالب اللوحات الخارجية','icon' => 'ti-ad','color' => 'warning']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::data-table.filters','data' => ['table' => '#auction-templates-table']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table.filters'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['table' => '#auction-templates-table']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <?php if (isset($component)) { $__componentOriginal3721828986c0d93618ebcde1401641f8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3721828986c0d93618ebcde1401641f8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::filter.select','data' => ['id' => 'filter-type','filterKey' => 'type','table' => '#auction-templates-table','label' => 'نوع القالب','placeholder' => 'جميع الأنواع','enum' => \App\Enums\Admin\AuctionTemplates\AuctionTemplateType::class]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filter.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'filter-type','filterKey' => 'type','table' => '#auction-templates-table','label' => 'نوع القالب','placeholder' => 'جميع الأنواع','enum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Enums\Admin\AuctionTemplates\AuctionTemplateType::class)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '96feafc28d79875225b51ba37e96068a::filter.select','data' => ['id' => 'filter-status','filterKey' => 'is_active','table' => '#auction-templates-table','label' => 'حالة القالب','placeholder' => 'جميع الحالات','options' => $statusFilterOptions ?? []]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filter.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'filter-status','filterKey' => 'is_active','table' => '#auction-templates-table','label' => 'حالة القالب','placeholder' => 'جميع الحالات','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statusFilterOptions ?? [])]); ?>
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
            <?php echo $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'auction-templates-table'],
                false,
            ); ?>

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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\resources\views/admin/auction-templates/index.blade.php ENDPATH**/ ?>