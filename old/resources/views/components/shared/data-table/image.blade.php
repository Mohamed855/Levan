@props([
    'src' => null,
    'alt' => '',
    'tenantLogo' => null,
    'size' => 44,
])

@php
    use App\Helpers\Tenants\Settings\SettingsHelper;

    $platformLogo = asset('static/brand/logos/logo.svg');
    if (! $tenantLogo && function_exists('tenant') && tenant()) {
        $tenantLogoPath = SettingsHelper::get('image');
        $tenantLogo = $tenantLogoPath ? tasset($tenantLogoPath) : null;
    }
    $fallback = $tenantLogo ?: $platformLogo;
    $image = filled($src) ? $src : $fallback;
@endphp

<img
    src="{{ $image }}"
    alt="{{ $alt }}"
    width="{{ $size }}"
    height="{{ $size }}"
    loading="lazy"
    class="rounded-circle border bg-white object-fit-cover"
    onerror="this.onerror=null;this.src='{{ $fallback }}';"
>
