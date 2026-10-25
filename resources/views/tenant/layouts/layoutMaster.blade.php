@isset($pageConfigs)
    {!! App\Helpers\Helpers::updatePageConfig($pageConfigs) !!}
@endisset
@php
    $configData = App\Helpers\Helpers::appClasses();
@endphp


@isset($configData['layout'])
    @include(
        $configData['layout'] === 'horizontal'
            ? 'tenant.layouts.horizontalLayout'
            : ($configData['layout'] === 'blank'
                ? 'tenant.layouts.blankLayout'
                : ($configData['layout'] === 'front'
                    ? 'tenant.layouts.layoutFront'
                    : 'tenant.layouts.contentNavbarLayout')))
@endisset

{{--
    All global JS (CSRF setup, toastr flash, confirm-delete, delete-btn listener,
    datatable search autocomplete fix) is now loaded via @vite in
    tenant/layouts/sections/scripts.blade.php from resources/assets/js/shared/*.js
--}}
