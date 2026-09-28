@isset($pageConfigs)
    {!! App\Helpers\Helpers::updatePageConfig($pageConfigs) !!}
@endisset
@php
    $configData = App\Helpers\Helpers::appClasses();
@endphp


@if (!isset($excludeJquery) || !$excludeJquery)
    <script src="{{ asset('assets/shared/js/cdn/jquery-init.js') }}"></script>
@endif

@isset($configData['layout'])
    @include(
        $configData['layout'] === 'horizontal'
            ? 'admin.layouts.horizontalLayout'
            : ($configData['layout'] === 'blank'
                ? 'admin.layouts.blankLayout'
                : ($configData['layout'] === 'front'
                    ? 'admin.layouts.layoutFront'
                    : 'admin.layouts.contentNavbarLayout')))
@endisset

{{--
    All global JS (CSRF setup, toastr flash, confirm-delete, delete-btn listener)
    is now loaded via @vite in admin/layouts/sections/scripts.blade.php
    from resources/js/shared/*.js
--}}
