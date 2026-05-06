<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin\Tests\Feature;

use Orchestra\Testbench\TestCase as Orchestra;
use Padosoft\PiiRedactor\PiiRedactorServiceProvider;
use Padosoft\PiiRedactorAdmin\PiiRedactorAdminServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            PiiRedactorServiceProvider::class,
            PiiRedactorAdminServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $app['config']->set('pii-redactor-admin.middleware', ['web']);
        $app['config']->set('pii-redactor-admin.abilities.view', '');
        $app['config']->set('pii-redactor-admin.abilities.detokenise', '');
        $app['config']->set('pii-redactor-admin.abilities.raw_samples', '');
        $app['config']->set('pii-redactor.salt', 'testing-admin-salt');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
