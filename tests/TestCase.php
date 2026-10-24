<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;
use Tests\Support\TestDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        if ($app->configurationIsCached()) {
            throw new RuntimeException('Tests refuse cached application configuration. Clear the config cache before testing.');
        }

        TestDatabaseGuard::assertSafe(
            $app['config']->get('app.env'),
            $app['config']->get('database'),
            $app['config']->get('tenancy.database'),
        );

        return $app;
    }
}
