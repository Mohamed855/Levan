@props(['confirmation' => true])

@once
    @if ($confirmation)
        <x-bootstrap.confirm-modal />
    @endif
    @vite([
        'resources/assets/css/shared/toastr.css',
        'resources/assets/js/shared/toastr.js',
        'resources/assets/js/shared/toastr-flash.js',
    ])
    @if ($confirmation)
        @vite(['resources/assets/js/shared/confirm-delete.js'])
    @endif
@endonce
