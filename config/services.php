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
        'secret' => env('MOYASER_SECRET_KEY', ''),
        'publishable' => env('MOYASER_PUBLISHABLE_KEY', ''),
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

    'ticket_api' => [
        'base_url' => 'https://app.e-tec.sa/api/v1',
        'key' => 'pk_live_2JkzAr71BivRrEEemRIeMzA81Ty3deB52GeWpDHO',
        'secret' => 'sk_live_o5NVyOtDwQknUkaoyiHlxvw7QefUG4f2qvhNA0hg',
    ],

    'recaptcha' => [
        'version' => env('RECAPTCHA_VERSION', 'v3'),
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    ],

];
