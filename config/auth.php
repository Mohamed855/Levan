<?php

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Tenants\UserMangment\User\User;

return [

    'defaults' => [
        'guard'             => 'tenant',
        'passwords'         => 'users',
    ],

    'guards' => [
        'admin' => [
            'driver'    => 'session',
            'provider'  => 'admin_users',
        ],

        'tenant' => [
            'driver'    => 'session',
            'provider'  => 'users',
        ],
    ],

    'providers' => [
        'admin_users' => [
            'driver'    => 'eloquent',
            'model'     => AdminUser::class,
        ],
        'users' => [
            'driver'    => 'eloquent',
            'model'     => User::class,
        ],
    ],

    'passwords' => [
        'admin_users' => [
            'provider'  => 'admin_users',
            'table'     => 'admin_password_reset_tokens',
            'expire'    => 60,
            'throttle'  => 60,
        ],

        'users' => [
            'provider'  => 'users',
            'table'     => 'password_reset_tokens',
            'expire'    => 60,
            'throttle'  => 60,
        ],
    ],

    'password_timeout' => 10800,

];
