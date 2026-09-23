@props(['logoPath' => null, 'height' => '30'])

@php
use App\Helpers\Tenants\Settings\SettingsHelper;

$logoImage = SettingsHelper::get('image');
$logoPath = $logoPath ?? tasset($logoImage);
@endphp

<span class="app-brand-logo demo">
    <img src="{{ $logoPath }}" alt="Logo" height="{{ $height }}">
</span>