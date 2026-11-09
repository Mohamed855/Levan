<?php

declare(strict_types=1);

return [
    // Temporary explicit opt-in, including on servers running APP_ENV=production.
    'test_seeders_enabled' => (bool) env('TEST_SEEDERS_ENABLED', false),
    'test_tenant_database' => env('TEST_TENANT_DATABASE', 'etmam_lot_test'),
];
