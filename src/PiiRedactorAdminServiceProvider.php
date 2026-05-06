<?php

declare(strict_types=1);

namespace Padosoft\PiiRedactorAdmin;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class PiiRedactorAdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pii-redactor-admin.php', 'pii-redactor-admin');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pii-redactor-admin');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/pii-redactor-admin.php' => $this->app->configPath('pii-redactor-admin.php'),
            ], 'pii-redactor-admin-config');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'pii-redactor-admin-migrations');
        }

        if (! (bool) $this->app['config']->get('pii-redactor-admin.enabled', false)) {
            return;
        }

        $middleware = (array) $this->app['config']->get('pii-redactor-admin.middleware', ['web', 'auth']);

        Route::middleware($middleware)
            ->prefix((string) $this->app['config']->get('pii-redactor-admin.api_prefix', 'pii-redactor-admin/api'))
            ->name('pii-redactor-admin.api.')
            ->group(__DIR__.'/../routes/api.php');

        Route::middleware($middleware)
            ->prefix((string) $this->app['config']->get('pii-redactor-admin.route_prefix', 'pii-redactor-admin'))
            ->name('pii-redactor-admin.')
            ->group(__DIR__.'/../routes/web.php');
    }
}
