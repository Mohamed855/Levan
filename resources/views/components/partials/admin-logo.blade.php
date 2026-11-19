@props([
    'logoPath' => null,
    'height' => 30,
    'withbg' => '',
])

@php
    use App\Helpers\Admin\Settings\AdminSettingsHelper;

    if (! $logoPath) {
        $logoPath = AdminSettingsHelper::getLogoUrl();
    }
@endphp

<span class="app-brand-logo demo">
    <img src="{{ $logoPath }}" alt="Logo" height="{{ $height }}">
</span>
