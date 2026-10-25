<?php $__env->startSection('title', 'تفاصيل قالب المزاد'); ?>

<?php $__env->startSection('content'); ?>
<div class="d-flex flex-wrap gap-2 mb-4">
    <a class="btn btn-label-secondary" href="<?php echo e(route('admin.auction-templates.index')); ?>">
        <i class="ti ti-arrow-right me-1"></i> العودة للقائمة
    </a>
    <a class="btn btn-primary" href="<?php echo e(route('admin.auction-templates.edit', $template)); ?>">
        <i class="ti ti-edit me-1"></i> تعديل القالب
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title fw-bold text-primary"><?php echo e($template->name); ?></h5>
                <p class="text-muted"><?php echo e($template->description ?: 'لا يوجد وصف مضاف.'); ?></p>
                <dl class="row mb-0">
                    <dt class="col-5">النوع</dt>
                    <dd class="col-7">
                        <span class="badge bg-label-primary"><?php echo e($template->type instanceof \BackedEnum ? $template->type->value : (string) $template->type); ?></span>
                    </dd>
                    <dt class="col-5">الحالة العامة</dt>
                    <dd class="col-7">
                        <span class="badge <?php echo e($template->status === 'published' ? 'bg-label-success' : 'bg-label-warning'); ?>"><?php echo e($template->status ?? 'draft'); ?></span>
                    </dd>
                    <dt class="col-5">الإصدار الحالي</dt>
                    <dd class="col-7 font-monospace fw-bold"><?php echo e($template->current_version ?: '—'); ?></dd>
                    <dt class="col-5">مفعل في النظام</dt>
                    <dd class="col-7"><?php echo e($template->is_active ? 'نعم' : 'لا'); ?></dd>
                    <dt class="col-5">أضيف بواسطة</dt>
                    <dd class="col-7"><?php echo e($template->creator?->name ?? 'النظام'); ?></dd>
                    <dt class="col-5">تاريخ الإضافة</dt>
                    <dd class="col-7"><?php echo e($template->created_at?->format('Y-m-d H:i')); ?></dd>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($template->field_names)): ?>
                        <dt class="col-5">الحقول المعرفة</dt>
                        <dd class="col-7">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $template->field_names; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fieldName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <span class="badge bg-label-info"><?php echo e($fieldName); ?></span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </dd>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ti ti-versions me-2 text-primary"></i>إصدارات القالب</h5>
                <span class="badge bg-label-info"><?php echo e($template->versions->count()); ?> إصدار</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>الإصدار</th>
                            <th>الحالة</th>
                            <th>تاريخ النشر</th>
                            <th>بواسطة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $template->versions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $version): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr>
                                <td class="fw-bold font-monospace">إصدار <?php echo e($version->version); ?></td>
                                <td>
                                    <span class="badge <?php echo e($version->status === 'published' ? 'bg-label-success' : 'bg-label-warning'); ?>">
                                        <?php echo e($version->status === 'published' ? 'منشور ومتاح' : 'مسودة'); ?>

                                    </span>
                                </td>
                                <td><?php echo e($version->published_at?->format('Y-m-d H:i') ?: '—'); ?></td>
                                <td><?php echo e($version->creator?->name ?? '—'); ?></td>
                                <td>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($version->status !== 'published'): ?>
                                        <form method="POST" action="<?php echo e(route('admin.auction-templates.versions.publish', [$template, $version])); ?>" class="d-inline">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="ti ti-check me-1"></i> نشر الإصدار
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge bg-label-secondary">معتمد كإصدار نشط</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">لا توجد إصدارات مسجلة لهذا القالب.</td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Levan\old\resources\views/admin/auction-templates/show.blade.php ENDPATH**/ ?>