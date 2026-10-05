@props([
    'logoPath' => null,
    'height' => 30,
    'withbg' => '',
])

@php
    use App\Helpers\Tenants\Settings\SettingsHelper;

    if (! $logoPath) {
        $logoImage = SettingsHelper::get('image');
        $logoPath = $logoImage ? tasset($logoImage) : asset('static/brand/logos/logo.svg');
    }
@endphp

<span class="app-brand-logo demo">
    <img src="{{ $logoPath }}" alt="Logo" height="{{ $height }}">
</span>
