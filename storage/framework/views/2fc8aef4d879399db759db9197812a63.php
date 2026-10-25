<?php $__env->startSection('title', 'العقارات'); ?>

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
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/shared/table-status-toggle.js']); ?>
    <script>
        window.routes = {
            massDelete: "<?php echo e(tenant_route_name('mass-delete')); ?>"
        };

        $(document).ready(function() {
            function initializeComponents() {
                if ($.fn.select2) {
                    $('.select2').not('.select2-hidden-accessible').each(function() {
                        $(this).select2({
                            placeholder: $(this).data('placeholder') || 'اختر',
                            allowClear: true,
                            width: '100%',
                            language: 'ar',
                            dir: 'rtl'
                        });
                    });
                }
            }
            initializeComponents();

            $('#properties-table').on('init.dt', function() {
                const table = $(this).DataTable();
                const tableWrapper = $('#properties-table_wrapper');

                tableWrapper.on('click', '#select-all', function() {
                    $('.row-checkbox', table.rows({
                        search: 'applied'
                    }).nodes()).prop('checked', this.checked);
                });

                tableWrapper.on('change', '.row-checkbox', function() {
                    if (!this.checked) {
                        $('#select-all').prop('checked', false);
                    }
                });
            });

            $('#properties-table').on('draw.dt', function() {
                $('#select-all').prop('checked', false);
            });
        });

        function handleMassDelete() {
            const selectedIds = [];
            $('.row-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                toastr.warning('يرجى تحديد عقار واحد على الأقل');
                return;
            }

            confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Property');
        }
        window.handleMassDelete = handleMassDelete;

        $(document).on('click', '.copyable-code-wrapper', function() {
            const code = $(this).data('code');
            if (code) {
                navigator.clipboard.writeText(code).then(() => {
                    toastr.success('تم النسخ: ' + code);
                });
            }
        });
    </script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php if (isset($component)) { $__componentOriginalfb5d8a43e6657040fc9bfa64e27d0a61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfb5d8a43e6657040fc9bfa64e27d0a61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::data-table.page','data' => ['tableId' => 'properties-table']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table.page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['table-id' => 'properties-table']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('stats', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'إجمالي العقارات','value' => $totalProperties,'subtitle' => 'جميع الحالات','icon' => 'ti-building','color' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'إجمالي العقارات','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalProperties),'subtitle' => 'جميع الحالات','icon' => 'ti-building','color' => 'primary']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'عقار نشط','value' => $activeProperties,'subtitle' => 'العقارات النشطة','icon' => 'ti-circle-check','color' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'عقار نشط','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activeProperties),'subtitle' => 'العقارات النشطة','icon' => 'ti-circle-check','color' => 'success']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'مكتملة البيانات','value' => $completedProperties,'subtitle' => 'جاهز للإسناد','icon' => 'ti-file-check','color' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'مكتملة البيانات','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($completedProperties),'subtitle' => 'جاهز للإسناد','icon' => 'ti-file-check','color' => 'info']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'غير مرتبطة بإسناد','value' => $unlinkedProperties,'subtitle' => 'غير مسند','icon' => 'ti-link-off','color' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'غير مرتبطة بإسناد','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($unlinkedProperties),'subtitle' => 'غير مسند','icon' => 'ti-link-off','color' => 'warning']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'تحتاج للمراجعة','value' => $needsReviewProperties,'subtitle' => 'بيانات غير مكتملة','icon' => 'ti-alert-triangle','color' => 'danger']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'تحتاج للمراجعة','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($needsReviewProperties),'subtitle' => 'بيانات غير مكتملة','icon' => 'ti-alert-triangle','color' => 'danger']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::data-table.filters','data' => ['table' => '#properties-table']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table.filters'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['table' => '#properties-table']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <?php if (isset($component)) { $__componentOriginal3721828986c0d93618ebcde1401641f8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3721828986c0d93618ebcde1401641f8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::filter.select','data' => ['id' => 'filter-assignment-status','filterKey' => 'assignment_status','table' => '#properties-table','label' => 'حالة الارتباط','placeholder' => 'الكل','enum' => \App\Enums\Tenants\Properties\PropertyAssignmentStatus::class]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filter.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'filter-assignment-status','filterKey' => 'assignment_status','table' => '#properties-table','label' => 'حالة الارتباط','placeholder' => 'الكل','enum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Enums\Tenants\Properties\PropertyAssignmentStatus::class)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::filter.select','data' => ['id' => 'filter-assignment','filterKey' => 'assignment_id','table' => '#properties-table','label' => 'الإسناد','placeholder' => 'جميع الإسنادات','options' => $assignments->pluck('name', 'id')->toArray()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filter.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'filter-assignment','filterKey' => 'assignment_id','table' => '#properties-table','label' => 'الإسناد','placeholder' => 'جميع الإسنادات','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($assignments->pluck('name', 'id')->toArray())]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::filter.select','data' => ['id' => 'filter-type','filterKey' => 'type','table' => '#properties-table','label' => 'نوع العقار','placeholder' => 'جميع الأنواع','enum' => \App\Enums\Tenants\Properties\PropertyType::class]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filter.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'filter-type','filterKey' => 'type','table' => '#properties-table','label' => 'نوع العقار','placeholder' => 'جميع الأنواع','enum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Enums\Tenants\Properties\PropertyType::class)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::filter.select','data' => ['id' => 'filter-data-status','filterKey' => 'data_status','table' => '#properties-table','label' => 'حالة البيانات','placeholder' => 'جميع الحالات','enum' => \App\Enums\Tenants\Properties\PropertyDataStatus::class]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filter.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'filter-data-status','filterKey' => 'data_status','table' => '#properties-table','label' => 'حالة البيانات','placeholder' => 'جميع الحالات','enum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Enums\Tenants\Properties\PropertyDataStatus::class)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::filter.select','data' => ['id' => 'filter-city','filterKey' => 'city','table' => '#properties-table','label' => 'المدينة','placeholder' => 'جميع المدن','options' => $cityOptions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filter.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'filter-city','filterKey' => 'city','table' => '#properties-table','label' => 'المدينة','placeholder' => 'جميع المدن','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cityOptions)]); ?>
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
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'properties-table'],
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

<?php echo $__env->make('tenant.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/tenant/properties/index.blade.php ENDPATH**/ ?>