<?php

namespace Cliomusetours\LaravelHealth;

use Cliomusetours\LaravelHealth\Console\HealthCheckCommand;
use Cliomusetours\LaravelHealth\Console\HealthListCommand;
use Cliomusetours\LaravelHealth\Console\HealthRunCommand;
use Cliomusetours\LaravelHealth\Runner\HealthRunner;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class HealthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/health.php',
            'health'
        );

        $this->app->singleton(HealthRunner::class, function ($app) {
            return new HealthRunner(
                $app['cache']->store(),
                $app['events']
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish config
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/health.php' => config_path('health.php'),
            ], 'health-config');
        }

        // Register routes
        $this->registerRoutes();

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                HealthRunCommand::class,
                HealthListCommand::class,
                HealthCheckCommand::class,
            ]);
        }
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        $config = config('health.routes', []);

        if (!($config['enabled'] ?? true)) {
            return;
        }

        Route::prefix($config['prefix'] ?? 'health')
            ->middleware($config['middleware'] ?? ['api'])
            ->group(function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/health.php');
            });
    }
}
