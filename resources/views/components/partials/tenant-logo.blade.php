@props([
    'logoPath' => null,
    'height' => 30,
    'withbg' => '',
])

@php
    use App\Helpers\Tenants\Settings\SettingsHelper;

    if (! $logoPath) {
        $logoImage = SettingsHelper::get('image');
        $logoPath = ($logoImage && $logoImage !== 'default-office.png')
            ? tasset($logoImage, '/static/brand/logos/logo-light.svg')
            : asset('static/brand/logos/logo-light.svg');
    }
@endphp

<span class="app-brand-logo demo">
    <img src="{{ $logoPath }}" alt="Logo" height="{{ $height }}" style="max-height: {{ $height }}px; max-width: 100%; object-fit: contain;">
</span>
