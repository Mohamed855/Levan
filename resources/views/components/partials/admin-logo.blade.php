@props([
    'logoPath' => null,
    'height' => 30,
    'withbg' => '',
])

@php
    use App\Helpers\Admin\Settings\AdminSettingsHelper;

    if (! $logoPath) {
        $logoImage = AdminSettingsHelper::get('image');
        $logoPath = $logoImage ? asset('storage/' . $logoImage) : asset('static/brand/logos/logo.svg');
    }
@endphp

<span class="app-brand-logo demo">
    <img src="{{ $logoPath }}" alt="Logo" height="{{ $height }}">
</span>
