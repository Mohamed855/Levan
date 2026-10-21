<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'text' => 'السابق',
    'icon' => 'ti ti-arrow-right me-1',
    'disabled' => false,
    'href' => null,
    'type' => 'button',
    'back' => null,
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
    'text' => 'السابق',
    'icon' => 'ti ti-arrow-right me-1',
    'disabled' => false,
    'href' => null,
    'type' => 'button',
    'back' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $isDisabled = filter_var($disabled, FILTER_VALIDATE_BOOLEAN) || $disabled === '' || $disabled === 'disabled' || $disabled === true;
    $backBool = is_bool($back) ? $back : ($back !== null ? filter_var($back, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null);

    $defaultOnClick = null;
    if (!$attributes->has('onclick') && !$isDisabled && !$href) {
        if ($backBool === true) {
            $defaultOnClick = "window.location.href = window.location.pathname.replace(/\\/create\\/?$/, '').replace(/\\/[^\\/]+\\/edit\\/?$/, '') || '/';";
        } elseif ($backBool !== false) {
            $defaultOnClick = "const c=this.closest('.content'),s=this.closest('.bs-stepper');const all=s?s.querySelectorAll('.content'):[];if(!c||!s||all[0]===c){window.location.href=window.location.pathname.replace(/\\/create\\/?$/, '').replace(/\\/[^\\/]+\\/edit\\/?$/, '') || '/';}";
        }
    }
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($href): ?>
    <a href="<?php echo e($href); ?>" <?php echo e($attributes->class(['btn', 'btn-secondary', 'btn-prev', 'waves-effect', 'waves-light'])); ?>>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($icon): ?><i class="<?php echo e($icon); ?>" aria-hidden="true"></i><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php echo e($slot->isNotEmpty() ? $slot : $text); ?>

    </a>
<?php else: ?>
    <button type="<?php echo e($type); ?>" data-wizard-prev data-wizard-back="<?php echo e($back === true ? 'true' : 'false'); ?>"
        <?php echo e($attributes->class(['btn', 'btn-secondary', 'btn-prev', 'waves-effect', 'waves-light'])); ?>

        <?php if($defaultOnClick): ?> onclick="<?php echo e($defaultOnClick); ?>" <?php endif; ?>
        <?php if($isDisabled): echo 'disabled'; endif; ?>>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($icon): ?><i class="<?php echo e($icon); ?>" aria-hidden="true"></i><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php echo e($slot->isNotEmpty() ? $slot : $text); ?>

    </button>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH D:\Levan\old\resources\views/components/shared/form/wizard/previous-button.blade.php ENDPATH**/ ?>