<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'table' => null,
    'target' => null,
    'name' => null,
    'resetId' => 'btn-reset-filters',
    'resetLabel' => 'إعادة تعيين',
    'showReset' => true,
    'cardClass' => 'mb-4',
]));

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

foreach (array_filter(([
    'table' => null,
    'target' => null,
    'name' => null,
    'resetId' => 'btn-reset-filters',
    'resetLabel' => 'إعادة تعيين',
    'showReset' => true,
    'cardClass' => 'mb-4',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $targetTable = $table ?? $target;
    if ($targetTable && !str_starts_with($targetTable, '#') && !str_starts_with($targetTable, '.')) {
        $targetTable = '#' . $targetTable;
    }
?>

<div <?php echo e($attributes->class(['card', 'datatable-filters-card', $cardClass])); ?>

    <?php if($targetTable): ?> data-datatable-target="<?php echo e($targetTable); ?>" <?php endif; ?>
    <?php if($name): ?> data-datatable-filter-group="<?php echo e($name); ?>" <?php endif; ?>
>
    <div class="card-body">
        <div class="row g-4 datatable-filters-grid">
            <?php echo e($slot); ?>

        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showReset): ?>
            <div class="d-flex justify-content-end mt-3 gap-2">
                <button type="button" id="<?php echo e($resetId); ?>" class="btn btn-secondary btn-reset-datatable-filters"
                    data-datatable-filter-reset="true"
                    <?php if($targetTable): ?> data-datatable-target="<?php echo e($targetTable); ?>" <?php endif; ?>>
                    <i class="ti ti-refresh me-1" aria-hidden="true"></i><?php echo e($resetLabel); ?>

                </button>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php /**PATH D:\Levan\resources\views/components/shared/data-table/filters.blade.php ENDPATH**/ ?>