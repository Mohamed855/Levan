<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title',
    'value',
    'subtitle' => null,
    'icon' => null,
    'color' => 'primary',
    'col' => 'col-sm-6 col-xl',
    'valueClass' => null,
    'avatarClass' => 'rounded',
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
    'title',
    'value',
    'subtitle' => null,
    'icon' => null,
    'color' => 'primary',
    'col' => 'col-sm-6 col-xl',
    'valueClass' => null,
    'avatarClass' => 'rounded',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $bgClass = str_starts_with($color, 'bg-') ? $color : 'bg-label-' . $color;
    $iconClass = str_contains($icon ?? '', 'ti ') ? $icon : 'ti ' . ($icon ?? '') . ' ti-26px';
    $isCircle = in_array($avatarClass, ['circle', 'rounded-circle'], true);
?>

<div class="<?php echo e($col); ?>">
    <div <?php echo e($attributes->merge(['class' => 'card h-100'])); ?>>
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between h-100">
                <div class="content-left d-flex flex-column justify-content-between h-100">
                    <span class="text-heading"><?php echo e($title); ?></span>
                    <div class="d-flex align-items-center my-1">
                        <h4 class="mb-0 me-2 <?php echo e($valueClass); ?>"><?php echo e($value); ?></h4>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($subtitle): ?>
                        <small class="mb-0"><?php echo e($subtitle); ?></small>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($icon): ?>
                    <div class="avatar <?php echo e($isCircle ? 'circle' : ''); ?>">
                        <span class="avatar-initial <?php echo e($isCircle ? 'rounded-circle' : 'rounded'); ?> <?php echo e($bgClass); ?>">
                            <i class="<?php echo e($iconClass); ?>" aria-hidden="true"></i>
                        </span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(trim($slot)): ?>
                <?php echo e($slot); ?>

            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH D:\Levan\resources\views/components/shared/ui/stat-card.blade.php ENDPATH**/ ?>