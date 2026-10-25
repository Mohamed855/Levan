<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['resourceKey']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['resourceKey']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<div class="modal fade" tabindex="-1" aria-hidden="true" data-wizard-modal="<?php echo e($resourceKey); ?>">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">مسودة محفوظة</h5>
            </div>
            <div class="modal-body">
                لديك مسودة سابقة. هل تريد استرجاع البيانات والمتابعة من حيث توقفت أم بدء نموذج جديد؟
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-discard-draft>بدء جديد</button>
                <button type="button" class="btn btn-primary" data-restore-draft>استرجاع المسودة</button>
            </div>
        </div>
    </div>
</div>
<?php /**PATH D:\Levan\resources\views/components/shared/wizard/draft-modal.blade.php ENDPATH**/ ?>