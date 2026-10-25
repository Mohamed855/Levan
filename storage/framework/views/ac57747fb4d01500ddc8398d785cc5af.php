<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'logoPath' => null,
    'height' => 30,
    'withbg' => '',
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
    'logoPath' => null,
    'height' => 30,
    'withbg' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    use App\Helpers\Tenants\Settings\SettingsHelper;

    if (! $logoPath) {
        $logoImage = SettingsHelper::get('image');
        $logoPath = $logoImage ? tasset($logoImage) : asset('static/brand/logos/lot/trans-icon.png');
    }
?>

<span class="app-brand-logo demo">
    <img src="<?php echo e($logoPath); ?>" alt="Logo" height="<?php echo e($height); ?>">
</span>
<?php /**PATH D:\Levan\resources\views/components/partials/tenant-logo.blade.php ENDPATH**/ ?>