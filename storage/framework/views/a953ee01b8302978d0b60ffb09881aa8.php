<div class="auction-design-surface" data-designer-surface>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($mode ?? 'template') !== 'sample'): ?>
    <div class="mb-3" data-sample-shelf data-library-url="<?php echo e(route('admin.auction-template-samples.library')); ?>" data-type="<?php echo e($type ?? 'brochure'); ?>">
        <div class="small fw-semibold mb-2">عينات جاهزة</div><div class="d-flex gap-2 overflow-auto" data-sample-cards><span class="text-muted small">جار تحميل العينات...</span></div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <div class="d-flex flex-wrap gap-2 my-3 align-items-center">
        <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="text"><i class="ti ti-typography me-1"></i>إضافة نص</button>
        <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="variable"><i class="ti ti-variable me-1"></i>إضافة متغير</button>
        <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="image"><i class="ti ti-photo me-1"></i>إضافة صورة</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" data-add-page><i class="ti ti-plus me-1"></i>إضافة صفحة</button>
        <label class="d-flex align-items-center gap-2 ms-auto small mb-0">لون الخلفية
            <input class="form-control form-control-color" data-background-color type="color" value="#ffffff">
        </label>
        <label class="d-flex align-items-center gap-2 small mb-0">ملاءمة الصورة
            <select class="form-select form-select-sm" data-background-fit>
                <option value="cover">تغطية</option><option value="contain">احتواء</option><option value="fill">تمديد</option>
            </select>
        </label>
        <label class="btn btn-outline-secondary btn-sm mb-0">خلفية الصفحة
            <input type="file" class="d-none" accept="image/png,image/jpeg,image/webp" data-background-image>
        </label>
    </div>
    <div class="template-designer__canvas template-canvas" data-canvas></div>
</div>
<?php /**PATH D:\Levan\resources\views/admin/auction-designs/partials/designer.blade.php ENDPATH**/ ?>