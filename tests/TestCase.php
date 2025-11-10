<?php

namespace Cliomusetours\LaravelHealth\Tests;

use Cliomusetours\LaravelHealth\HealthServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            HealthServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup cache to use array driver
        $app['config']->set('cache.default', 'array');

        // Setup queue to use sync driver
        $app['config']->set('queue.default', 'sync');
    }
}
