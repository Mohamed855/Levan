@php
    use App\Helpers\Admin\Settings\AdminSettingsHelper;

    $logoImage = AdminSettingsHelper::get('image');
    $logoPath = $logoImage ? asset('storage/' . $logoImage) : asset('static/brand/logos/logo.svg');
@endphp
<span class="app-brand-logo demo">
    <img src="{{ $logoPath }}" alt="Logo" height="30">
</span>
