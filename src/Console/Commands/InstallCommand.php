<?php

namespace SocialSync\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'social-sync:install {--force : Overwrite published files}';

    protected $description = 'Install Laravel Social Sync configuration, migrations, and storage directories.';

    public function handle(): int
    {
        $this->info('Installing Laravel Social Sync...');

        $this->call('vendor:publish', [
            '--tag' => 'social-sync-config',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'social-sync-migrations',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->createStorageDirectories();

        if ($this->confirm('Run migrations now?', true)) {
            $this->call('migrate');
        }

        $this->displayRequiredEnvVariables();

        $this->newLine();
        $this->info('Social Sync installed successfully.');
        $this->line('Next: connect an account with `php artisan social-sync:add-account facebook`.');

        return self::SUCCESS;
    }

    protected function createStorageDirectories(): void
    {
        $directories = [
            storage_path('app/social-sync'),
            storage_path('app/social-sync/temp'),
        ];

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->line(sprintf('Created %s', $directory));
            }
        }
    }

    protected function displayRequiredEnvVariables(): void
    {
        $this->newLine();
        $this->line('Add the variables you need to `.env`:');
        $this->newLine();

        $variables = [
            '# Facebook + Instagram',
            'FACEBOOK_APP_ID=',
            'FACEBOOK_APP_SECRET=',
            'FACEBOOK_API_VERSION=v20.0',
            '',
            '# Twitter/X OAuth2',
            'TWITTER_CLIENT_ID=',
            'TWITTER_CLIENT_SECRET=',
            '',
            '# LinkedIn',
            'LINKEDIN_CLIENT_ID=',
            'LINKEDIN_CLIENT_SECRET=',
            '',
            '# Optional',
            'SOCIAL_SYNC_DEFAULT_PLATFORM=facebook',
            'SOCIAL_SYNC_QUEUE_ENABLED=true',
            'SOCIAL_SYNC_MAX_RETRY_ATTEMPTS=3',
        ];

        foreach ($variables as $variable) {
            $this->line($variable);
        }
    }
}
