@props([
    'logoPath' => null,
    'height' => 30,
    'withbg' => '',
])

@php
    use App\Helpers\Tenants\Settings\SettingsHelper;

    if (! $logoPath) {
        $logoImage = SettingsHelper::get('image');
        $logoPath = ($logoImage && ! in_array($logoImage, ['default-office.png', 'image/demo-office.jpg'], true))
            ? tasset($logoImage, null)
            : null;
    }
@endphp

@if ($logoPath)
    <span class="app-brand-logo demo">
        <img src="{{ $logoPath }}" alt="Logo" height="{{ $height }}" style="max-height: {{ $height }}px; max-width: 100%; object-fit: contain;">
    </span>
@endif
