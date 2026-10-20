@isset($pageConfigs)
    {!! App\Helpers\Helpers::updatePageConfig($pageConfigs) !!}
@endisset
@php
    $configData = App\Helpers\Helpers::appClasses();
@endphp

@if (!isset($excludeJquery) || !$excludeJquery)
    <script src="{{ asset('static/shared/js/cdn/jquery-init.js') }}"></script>
    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha384-vtXRMe3mGCbOeY7l30aIg8H9p3GdeSe4IFlP6G8JMa7o7lXvnz3GFKzPxzJdPfGK" crossorigin="anonymous"></script> --}}
@endif

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
