<?php

namespace SocialSync\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SocialSync\SocialSyncServiceProvider;
use SocialSync\Tests\Fixtures\FakeDriver;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SocialSyncServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('larapost.default_platform', 'facebook');
        $app['config']->set('larapost.drivers', [
            'facebook' => FakeDriver::class,
            'twitter' => FakeDriver::class,
            'linkedin' => FakeDriver::class,
        ]);
        $app['config']->set('larapost.routes.enabled', false);
        $app['config']->set('larapost.queue.enabled', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__) . '/database/migrations');
    }
}
