<?php

return [
    'contact' => [
        'address' => env('SITE_CONTACT_ADDRESS'),
        'phone' => env('SITE_CONTACT_PHONE'),
        'email' => env('SITE_CONTACT_EMAIL'),
    ],

    'social' => [
        'facebook' => env('SITE_FACEBOOK_URL'),
        'x' => env('SITE_X_URL'),
        'linkedin' => env('SITE_LINKEDIN_URL'),
    ],

    'maps' => [
        'embed_url' => env('SITE_MAPS_EMBED_URL'),
    ],
];
