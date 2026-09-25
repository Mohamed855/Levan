<?php

return [
    'user'       => env('CPANEL_USER'),
    'shared_ip'  => env('CPANEL_SHARED_IP'),
    'api_token'  => env('CPANEL_API_TOKEN'),
    'ssl_verify' => env('CPANEL_SSL_VERIFY', false),
];
