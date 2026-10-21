<?php $__env->startSection('title', 'إدارة المزادات'); ?>

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

            $('#auctions-table').on('init.dt', function() {
                const table = $(this).DataTable();
                const tableWrapper = $('#auctions-table_wrapper');

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

            $('#auctions-table').on('draw.dt', function() {
                $('#select-all').prop('checked', false);
            });
        });

        function handleMassDelete() {
            const selectedIds = [];
            $('.row-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                toastr.warning('يرجى تحديد مزاد واحد على الأقل');
                return;
            }

            confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Auction');
        }
        window.handleMassDelete = handleMassDelete;
    </script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <?php if (isset($component)) { $__componentOriginalfb5d8a43e6657040fc9bfa64e27d0a61 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfb5d8a43e6657040fc9bfa64e27d0a61 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::data-table.page','data' => ['tableId' => 'auctions-table']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table.page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['table-id' => 'auctions-table']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('stats', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal51ca8f9b5f2319ee98f72ec28ea4da42 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'إجمالي المزادات','value' => $totalAuctions,'subtitle' => 'مزاد في جميع الحالات','icon' => 'ti-gavel','color' => 'primary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'إجمالي المزادات','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalAuctions),'subtitle' => 'مزاد في جميع الحالات','icon' => 'ti-gavel','color' => 'primary']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'مزادات نشطة','value' => $activeAuctions,'subtitle' => 'قيد التنفيذ حالياً','icon' => 'ti-player-play','color' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'مزادات نشطة','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activeAuctions),'subtitle' => 'قيد التنفيذ حالياً','icon' => 'ti-player-play','color' => 'success']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'قيد الإعداد','value' => $inPreparationAuctions,'subtitle' => 'تحت التجهيز','icon' => 'ti-clock','color' => 'warning']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'قيد الإعداد','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($inPreparationAuctions),'subtitle' => 'تحت التجهيز','icon' => 'ti-clock','color' => 'warning']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'بانتظار الاعتماد','value' => $pendingAuctions,'subtitle' => 'في انتظار المراجعة','icon' => 'ti-file-check','color' => 'info']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'بانتظار الاعتماد','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($pendingAuctions),'subtitle' => 'في انتظار المراجعة','icon' => 'ti-file-check','color' => 'info']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::ui.stat-card','data' => ['title' => 'تحتاج مراجعة','value' => $needsReviewAuctions,'subtitle' => 'مزادات معلقة','icon' => 'ti-alert-triangle','color' => 'danger']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'تحتاج مراجعة','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($needsReviewAuctions),'subtitle' => 'مزادات معلقة','icon' => 'ti-alert-triangle','color' => 'danger']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::data-table.filters','data' => ['table' => '#auctions-table']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table.filters'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['table' => '#auctions-table']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <?php if (isset($component)) { $__componentOriginal3721828986c0d93618ebcde1401641f8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3721828986c0d93618ebcde1401641f8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::filter.select','data' => ['id' => 'filter-type','filterKey' => 'type','table' => '#auctions-table','label' => 'نوع المزاد','placeholder' => 'جميع الأنواع','enum' => \App\Enums\Tenants\Auctions\AuctionType::class]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filter.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'filter-type','filterKey' => 'type','table' => '#auctions-table','label' => 'نوع المزاد','placeholder' => 'جميع الأنواع','enum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Enums\Tenants\Auctions\AuctionType::class)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::filter.select','data' => ['id' => 'filter-status','filterKey' => 'status','table' => '#auctions-table','label' => 'الحالة','placeholder' => 'جميع الحالات','enum' => \App\Enums\Tenants\Auctions\AuctionStatus::class]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filter.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'filter-status','filterKey' => 'status','table' => '#auctions-table','label' => 'الحالة','placeholder' => 'جميع الحالات','enum' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(\App\Enums\Tenants\Auctions\AuctionStatus::class)]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::filter.select','data' => ['id' => 'filter-assignment','filterKey' => 'assignment_id','table' => '#auctions-table','label' => 'الإسناد','placeholder' => 'جميع الإسنادات','options' => $assignments->pluck('name', 'id')->all()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filter.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'filter-assignment','filterKey' => 'assignment_id','table' => '#auctions-table','label' => 'الإسناد','placeholder' => 'جميع الإسنادات','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($assignments->pluck('name', 'id')->all())]); ?>
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
                <div class="col-md-6 col-lg-3">
                    <label class="form-label" for="filter-start-date">تاريخ البداية</label>
                    <input id="filter-start-date" type="date" class="form-control" data-datatable-filter="true"
                        data-filter-key="start_date" data-datatable-target="#auctions-table" />
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label" for="filter-end-date">تاريخ النهاية</label>
                    <input id="filter-end-date" type="date" class="form-control" data-datatable-filter="true"
                        data-filter-key="end_date" data-datatable-target="#auctions-table" />
                </div>
                <?php if (isset($component)) { $__componentOriginal3721828986c0d93618ebcde1401641f8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3721828986c0d93618ebcde1401641f8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::filter.select','data' => ['id' => 'filter-city','filterKey' => 'city','table' => '#auctions-table','label' => 'المدينة','placeholder' => 'جميع المدن','options' => $cityOptions]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filter.select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'filter-city','filterKey' => 'city','table' => '#auctions-table','label' => 'المدينة','placeholder' => 'جميع المدن','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($cityOptions)]); ?>
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
            <?php echo $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'auctions-table'], false); ?>

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

<?php echo $__env->make('tenant.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/tenant/auctions/index.blade.php ENDPATH**/ ?>