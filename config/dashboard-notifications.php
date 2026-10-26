<?php

return [
    'enabled' => env('DASHBOARD_NOTIFICATIONS_ENABLED', env('APP_ENV') !== 'testing'),
    'broadcast' => env('DASHBOARD_NOTIFICATIONS_BROADCAST', env('APP_ENV') !== 'testing'),
];
