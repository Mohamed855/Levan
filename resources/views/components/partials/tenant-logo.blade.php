@props([
    'logoPath' => null,
    'height' => 30,
    'withbg' => '',
])

@php
    use App\Helpers\Tenants\Settings\SettingsHelper;

    $platformIcon = asset('static/brand/logos/lot/trans-icon.png');
    if (! $logoPath) {
        $logoImage = SettingsHelper::get('image');
        $logoPath = ($logoImage && ! in_array($logoImage, ['default-office.png', 'image/demo-office.jpg'], true))
            ? tasset($logoImage, null)
            : null;
    }
    $logoPath = $logoPath ?: $platformIcon;
@endphp

@if ($logoPath)
    <span class="app-brand-logo demo">
        <img src="{{ $logoPath }}" alt="Logo" height="{{ $height }}" style="max-height: {{ $height }}px; max-width: 100%; object-fit: contain;"
            onerror="this.onerror=null;this.src='{{ $platformIcon }}';">
    </span>
@endif
