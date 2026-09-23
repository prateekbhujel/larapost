<?php

namespace SocialSync;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use SocialSync\Console\Commands\AddAccountCommand;
use SocialSync\Console\Commands\DoctorCommand;
use SocialSync\Console\Commands\InstallCommand;
use SocialSync\Console\Commands\RunScheduledPostsCommand;
use SocialSync\Console\Commands\TestPostCommand;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Http\Middleware\AuthenticateMcp;
use SocialSync\Mcp\LaraPostServer;

class SocialSyncServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/larapost.php', 'larapost');

        $this->app->singleton(SocialMediaManager::class, function ($app) {
            return new SocialMediaManager($app['config']->get('larapost', []), $app);
        });
        $this->app->alias(SocialMediaManager::class, 'social-media');
    }

    public function boot(): void
    {
        if (config('larapost.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        $this->registerMcp();
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'larapost');

        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/larapost.php' => config_path('larapost.php'),
        ], 'larapost-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'larapost-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/larapost'),
        ], 'larapost-views');

        $this->commands([
            InstallCommand::class,
            AddAccountCommand::class,
            TestPostCommand::class,
            RunScheduledPostsCommand::class,
            DoctorCommand::class,
        ]);

        if (config('larapost.scheduler.enabled', true)) {
            $this->registerSchedule();
        }
    }

    protected function registerMcp(): void
    {
        if (!config('larapost.mcp.enabled', false)) {
            return;
        }

        if (!class_exists(\Laravel\Mcp\Facades\Mcp::class)) {
            throw new SocialSyncException(
                'LaraPost MCP is enabled but laravel/mcp is not installed. Run: composer require laravel/mcp:^1.0'
            );
        }

        if (!filled(config('larapost.mcp.token'))) {
            throw new SocialSyncException(
                'LaraPost MCP is enabled but LARAPOST_MCP_TOKEN is empty.'
            );
        }

        $path = '/' . ltrim((string) config('larapost.mcp.path', '/larapost/mcp'), '/');

        \Laravel\Mcp\Facades\Mcp::web($path, LaraPostServer::class)
            ->middleware(AuthenticateMcp::class);
    }

    protected function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $limit = max(1, (int) config('larapost.scheduler.limit', 50));
            $event = $schedule
                ->command('larapost:run-scheduled --limit=' . $limit)
                ->everyMinute();

            if (config('larapost.scheduler.without_overlapping', true)) {
                $event->withoutOverlapping(max(
                    1,
                    (int) config('larapost.scheduler.overlap_expiration_minutes', 10)
                ));
            }
        });
    }
}
