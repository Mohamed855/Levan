@props(['front' => false])

@if ($front)
    @vite(['resources/assets/vendor/js/helpers.js', 'resources/assets/js/shared/basic-tamplate/front-config.js'])
@else
    @vite(['resources/assets/vendor/js/helpers.js', 'resources/assets/js/shared/basic-tamplate/config.js'])
@endif
