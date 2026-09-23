@php
    use App\Helpers\Admin\Settings\AdminSettingsHelper;

    $logoImage = AdminSettingsHelper::get('image');
    $logoPath = asset($logoImage);
@endphp
<span class="app-brand-logo demo">
    <img src="{{ $logoPath }}" alt="Logo" height="30">
</span>
