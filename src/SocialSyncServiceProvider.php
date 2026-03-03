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
        $this->mergeConfigFrom(__DIR__ . '/../config/larapost.php', 'larapost');

        $this->app->singleton('social-media', function ($app) {
            return new SocialMediaManager($app['config']->get('larapost', []), $app);
        });
    }

    public function boot(): void
    {
        if (config('larapost.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'larapost');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/larapost.php' => config_path('larapost.php'),
            ], 'larapost-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'larapost-migrations');

            $this->commands([
                InstallCommand::class,
                AddAccountCommand::class,
                TestPostCommand::class,
                RunScheduledPostsCommand::class,
            ]);
        }
    }
}
