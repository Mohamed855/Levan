<?php $__env->startSection('title', 'تعديل الدور'); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/js/shared/form-validation.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">

                <div class="card-header py-4">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-automatic-gearbox text-warning me-2"></i>
                        تعديل الدور
                    </h6>
                </div>

                <div class="border-1 border-light border-dashed mb-2"></div>

                <div class="card-body">

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <li><?php echo e($error); ?></li>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <form action="<?php echo e(route('admin.roles.update', $role->id)); ?>" method="POST" data-form-validate
                        data-validation-url="<?php echo e(route('admin.wizard-drafts.validate-step')); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>

                        <div class="row g-4">
                            <?php if (isset($component)) { $__componentOriginal3dadacf08e1fd61e8049ea7dffda1276 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3dadacf08e1fd61e8049ea7dffda1276 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.controls.input','data' => ['name' => 'name','label' => 'اسم الدور','value' => old('name', $role->name),'required' => true,'col' => 'col-md-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.controls.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'name','label' => 'اسم الدور','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('name', $role->name)),'required' => true,'col' => 'col-md-6']); ?>
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

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center my-3 gap-3">
                                <label for="permissions" class="form-label">الصلاحيات</label>
                                <!-- تحديد الكل العام -->
                                <div class="form-check d-flex align-items-center gap-2">
                                    <input type="checkbox" name="select_all" value="1" class="form-check-input"
                                        id="select-all" data-validation-skip>
                                    <label class="form-check-label" for="select-all">تحديد الكل</label>
                                </div>
                            </div>
                            <div id="permissions-treeview">

                                <!-- الأقسام -->
                                <div style="display: flex; flex-wrap: wrap; gap: 1.25rem;">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section => $perms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <div style="flex: 1; min-width: 300px;">
                                            <div class="accordion" id="accordion-<?php echo e($loop->index); ?>">
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header" id="heading-<?php echo e($loop->index); ?>">
                                                        <button type="button" class="accordion-button collapsed"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#collapse-<?php echo e($loop->index); ?>"
                                                            aria-expanded="false"
                                                            aria-controls="collapse-<?php echo e($loop->index); ?>">
                                                            <?php echo e(trans()->has('permissions.sections.' . $section) ? __('permissions.sections.' . $section) : (trans()->has('permissions.' . $section) ? __('permissions.' . $section) : $section)); ?>

                                                        </button>
                                                    </h2>
                                                    <div id="collapse-<?php echo e($loop->index); ?>"
                                                        class="accordion-collapse collapse"
                                                        aria-labelledby="heading-<?php echo e($loop->index); ?>"
                                                        data-bs-parent="#accordion-<?php echo e($loop->index); ?>">
                                                        <div class="accordion-body">

                                                            <!-- تحديد الكل داخل القسم -->
                                                            <div class="form-check mb-2">
                                                                <input type="checkbox"
                                                                    class="form-check-input section-select-all"
                                                                    id="section-select-all-<?php echo e($loop->index); ?>"
                                                                    data-section-index="<?php echo e($loop->index); ?>">
                                                                <label class="form-check-label"
                                                                    for="section-select-all-<?php echo e($loop->index); ?>">
                                                                    تحديد الكل داخل القسم
                                                                </label>
                                                            </div>

                                                            <hr>

                                                            <!-- الصلاحيات -->
                                                            <div style="max-height: 300px; overflow-y: auto;">
                                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $perms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($permission): ?>
                                                                        <div class="form-check mb-2">
                                                                            <input type="checkbox" name="permissions[]"
                                                                                value="<?php echo e($permission->name); ?>"
                                                                                class="form-check-input permission-checkbox"
                                                                                id="permission-<?php echo e($permission->id); ?>"
                                                                                data-section="<?php echo e($loop->parent->index); ?>"
                                                                                <?php echo e($role->hasPermissionTo($permission->name) ? 'checked' : ''); ?>>
                                                                            <label class="form-check-label"
                                                                                for="permission-<?php echo e($permission->id); ?>">
                                                                                <?php echo e(trans()->has('permissions.' . $permission->name) ? __('permissions.' . $permission->name) : $permission->name); ?>

                                                                            </label>
                                                                        </div>
                                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <?php if (isset($component)) { $__componentOriginal279ed4aee8936495973b201156bb1c1c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal279ed4aee8936495973b201156bb1c1c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'ba6ca0197cdb4927d0fc392270fc125b::form.wizard.previous-button','data' => ['href' => route('admin.roles.index'),'text' => 'العودة']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.wizard.previous-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.roles.index')),'text' => 'العودة']); ?>
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
                            <button type="submit" class="btn btn-primary">تحديث</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllGlobal = document.getElementById('select-all');
            const sectionSelectAllCheckboxes = document.querySelectorAll('.section-select-all');
            const individualCheckboxes = document.querySelectorAll('.permission-checkbox');

            // تحديد الكل العام
            selectAllGlobal.addEventListener('change', function() {
                const isChecked = this.checked;

                // تحديد جميع الصلاحيات
                individualCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });

                // تحديد جميع أقسام "تحديد الكل داخل القسم"
                sectionSelectAllCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                    checkbox.indeterminate = false;
                });
            });

            // تحديد الكل داخل القسم
            sectionSelectAllCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const sectionIndex = this.getAttribute('data-section-index');
                    const childCheckboxes = document.querySelectorAll(
                        `[data-section="${sectionIndex}"]`);
                    const isChecked = this.checked;

                    childCheckboxes.forEach(child => {
                        child.checked = isChecked;
                    });

                    this.indeterminate = false;
                    updateGlobalSelectAll();
                });
            });

            // تحديد الصلاحيات الفردية
            individualCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const sectionIndex = this.getAttribute('data-section');
                    updateSectionSelectAll(sectionIndex);
                    updateGlobalSelectAll();
                });
            });

            // تحديث حالة تحديد الكل داخل القسم
            function updateSectionSelectAll(sectionIndex) {
                const sectionCheckboxes = document.querySelectorAll(`[data-section="${sectionIndex}"]`);
                const sectionSelectAll = document.querySelector(`[data-section-index="${sectionIndex}"]`);

                if (!sectionSelectAll) return;

                const checkedCount = Array.from(sectionCheckboxes).filter(cb => cb.checked).length;
                const totalCount = sectionCheckboxes.length;

                if (checkedCount === 0) {
                    sectionSelectAll.checked = false;
                    sectionSelectAll.indeterminate = false;
                } else if (checkedCount === totalCount) {
                    sectionSelectAll.checked = true;
                    sectionSelectAll.indeterminate = false;
                } else {
                    sectionSelectAll.checked = false;
                    sectionSelectAll.indeterminate = true;
                }
            }

            // تحديث حالة تحديد الكل العام
            function updateGlobalSelectAll() {
                const checkedCount = Array.from(individualCheckboxes).filter(cb => cb.checked).length;
                const totalCount = individualCheckboxes.length;

                if (checkedCount === 0) {
                    selectAllGlobal.checked = false;
                    selectAllGlobal.indeterminate = false;
                } else if (checkedCount === totalCount) {
                    selectAllGlobal.checked = true;
                    selectAllGlobal.indeterminate = false;
                } else {
                    selectAllGlobal.checked = false;
                    selectAllGlobal.indeterminate = true;
                }
            }

            // تحديث حالة جميع checkboxes عند تحميل الصفحة
            function initializeCheckboxStates() {
                // تحديث حالة تحديد الكل لكل قسم
                sectionSelectAllCheckboxes.forEach(checkbox => {
                    const sectionIndex = checkbox.getAttribute('data-section-index');
                    updateSectionSelectAll(sectionIndex);
                });

                // تحديث حالة تحديد الكل العام
                updateGlobalSelectAll();
            }

            // تشغيل التحديث عند تحميل الصفحة
            initializeCheckboxStates();
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/admin/roles/edit.blade.php ENDPATH**/ ?>