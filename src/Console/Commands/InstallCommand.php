<?php

namespace SocialSync\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'larapost:install {--force : Overwrite published files}';

    protected $description = 'Install Laravel Social Sync configuration, migrations, and storage directories.';

    public function handle(): int
    {
        $this->info('Installing LaraPost for Laravel...');

        $this->call('vendor:publish', [
            '--tag' => 'larapost-config',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'larapost-migrations',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->createStorageDirectories();

        if ($this->confirm('Run migrations now?', true)) {
            $this->call('migrate');
        }

        $this->displayRequiredEnvVariables();

        $this->newLine();
        $this->info('LaraPost installed successfully.');
        $this->line('Next: connect an account with `php artisan larapost:add-account facebook`.');

        return self::SUCCESS;
    }

    protected function createStorageDirectories(): void
    {
        $tempUploadPath = (string) config('larapost.media.temp_upload_path', storage_path('app/larapost/temp'));

        $directories = [
            dirname($tempUploadPath),
            $tempUploadPath,
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
            'LARAPOST_DEFAULT_PLATFORM=facebook',
            'LARAPOST_QUEUE_ENABLED=true',
            'LARAPOST_MAX_RETRY_ATTEMPTS=3',
        ];

        foreach ($variables as $variable) {
            $this->line($variable);
        }
    }
}
