@isset($pageConfigs)
    {!! App\Helpers\Helpers::updatePageConfig($pageConfigs) !!}
@endisset
@php
    $configData = App\Helpers\Helpers::appClasses();
@endphp

@if (!isset($excludeJquery) || !$excludeJquery)
    <script src="{{ asset('static/shared/js/cdn/jquery-init.js') }}"></script>
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
