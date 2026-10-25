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
    use App\Helpers\Admin\Settings\AdminSettingsHelper;

    if (! $logoPath) {
        $logoImage = AdminSettingsHelper::get('image');
        $logoPath = $logoImage ? asset('storage/' . $logoImage) : asset('static/brand/logos/lot/trans-icon.png');
    }
?>

<span class="app-brand-logo demo">
    <img src="<?php echo e($logoPath); ?>" alt="Logo" height="<?php echo e($height); ?>">
</span>
<?php /**PATH D:\Levan\old\resources\views/components/partials/admin-logo.blade.php ENDPATH**/ ?>