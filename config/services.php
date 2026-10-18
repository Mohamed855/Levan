<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'moyasar' => [
        'mode' => env('MOYASAR_MODE', 'test'),
        'secret' => env('MOYASAR_MODE', 'test') === 'live'
            ? env('MOYASAR_LIVE_SECRET_KEY', '')
            : env('MOYASAR_TEST_SECRET_KEY', ''),
        'publishable' => env('MOYASAR_MODE', 'test') === 'live'
            ? env('MOYASAR_LIVE_PUBLISHABLE_KEY', '')
            : env('MOYASAR_TEST_PUBLISHABLE_KEY', ''),
        'base_url' => env('MOYASAR_API_BASE_URL', 'https://api.moyasar.com/v1'),
        'timeout' => (int) env('MOYASAR_API_TIMEOUT', 15),
        'verify_ssl' => filter_var(env('MOYASAR_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'namecheap' => [
        'user' => env('NAMECHEAP_API_USER'),
        'api_key' => env('NAMECHEAP_API_KEY'),
        'client_ip' => env('NAMECHEAP_CLIENT_IP'),
        'base_url' => env('NAMECHEAP_URL', 'https://api.namecheap.com/xml.response'),
        'whm_api_url' => env('WHM_API_URL'),
        'whm_host' => env('WHM_HOST', env('WHM_API_URL')),
        'whm_token' => env('WHM_TOKEN'),
        'server_ip' => env('SERVER_IP'),
    ],

    'domains' => [
        'server_ip' => env('SERVER_IP', '135.181.215.105'),
        'name_servers' => ['ns1.levan.com', 'ns2.levan.com'],
        'server_domain' => env('SERVER_DOMAIN', 'levan.com'),
    ],

    'ticket_api' => [
        'base_url' => env('TICKET_API_BASE_URL'),
        'key' => env('TICKET_API_KEY'),
        'secret' => env('TICKET_API_SECRET'),
    ],

    'recaptcha' => [
        'version' => env('RECAPTCHA_VERSION', 'v3'),
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    ],

];
