<?php

namespace SocialSync;

use Illuminate\Support\ServiceProvider;
use SocialSync\Console\Commands\AddAccountCommand;
use SocialSync\Console\Commands\InstallCommand;
use SocialSync\Console\Commands\RunScheduledPostsCommand;
use SocialSync\Console\Commands\TestPostCommand;

class SocialSyncServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/social-sync.php', 'social-sync');

        $this->app->singleton('social-media', function ($app) {
            return new SocialMediaManager($app['config']->get('social-sync', []), $app);
        });
    }

    public function boot(): void
    {
        if (config('social-sync.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'social-sync');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/social-sync.php' => config_path('social-sync.php'),
            ], 'social-sync-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'social-sync-migrations');

            $this->commands([
                InstallCommand::class,
                AddAccountCommand::class,
                TestPostCommand::class,
                RunScheduledPostsCommand::class,
            ]);
        }
    }
}
