<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'src' => null,
    'alt' => '',
    'tenantLogo' => null,
    'size' => 44,
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
    'src' => null,
    'alt' => '',
    'tenantLogo' => null,
    'size' => 44,
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

    $platformLogo = asset('static/brand/logos/logo.svg');
    if (! $tenantLogo && function_exists('tenant') && tenant()) {
        $tenantLogoPath = SettingsHelper::get('image');
        $tenantLogo = $tenantLogoPath ? tasset($tenantLogoPath) : null;
    }
    $fallback = $tenantLogo ?: $platformLogo;
    $image = filled($src) ? $src : $fallback;
?>

<img
    src="<?php echo e($image); ?>"
    alt="<?php echo e($alt); ?>"
    width="<?php echo e($size); ?>"
    height="<?php echo e($size); ?>"
    loading="lazy"
    class="rounded-circle border bg-white object-fit-cover"
    onerror="this.onerror=null;this.src='<?php echo e($fallback); ?>';"
>
<?php /**PATH D:\Levan\resources\views/components/shared/data-table/image.blade.php ENDPATH**/ ?>