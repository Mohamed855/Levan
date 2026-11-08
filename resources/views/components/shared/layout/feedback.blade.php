@once
    <x-bootstrap.confirm-modal />
    @vite([
        'resources/assets/css/shared/toastr.css',
        'resources/assets/js/shared/toastr.js',
        'resources/assets/js/shared/toastr-flash.js',
        'resources/assets/js/shared/confirm-delete.js',
    ])
@endonce
