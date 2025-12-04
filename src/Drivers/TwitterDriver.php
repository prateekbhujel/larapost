<?php

namespace SocialSync\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'social-sync:install';
    protected $description = 'Install Social Sync package';

    public function handle()
    {
        $this->info('Installing Social Sync...');

        // Publish configuration
        $this->call('vendor:publish', [
            '--tag' => 'social-sync-config',
            '--force' => true,
        ]);

        // Publish migrations
        $this->call('vendor:publish', [
            '--tag' => 'social-sync-migrations',
            '--force' => true,
        ]);

        // Run migrations
        if ($this->confirm('Do you want to run migrations now?', true)) {
            $this->call('migrate');
        }

        // Create storage directories
        $this->createStorageDirectories();

        // Display environment variables needed
        $this->displayEnvVariables();

        $this->newLine();
        $this->info('✓ Social Sync installed successfully!');
        $this->newLine();
        $this->line('Next steps:');
        $this->line('1. Add the required environment variables to your .env file');
        $this->line('2. Run: php artisan social-sync:add-account {platform}');
        $this->line('3. Start posting: SocialMedia::post()->content("Hello World!")->platforms(["facebook"])->publish()');
        $this->newLine();
    }

    protected function createStorageDirectories()
    {
        $directories = [
            storage_path('app/social-sync'),
            storage_path('app/social-sync/temp'),
        ];

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->info("✓ Created directory: {$directory}");
            }
        }
    }

    protected function displayEnvVariables()
    {
        $this->newLine();
        $this->warn('Add these to your .env file:');
        $this->newLine();

        $envVars = [
            '# Facebook',
            'FACEBOOK_APP_ID=your_app_id',
            'FACEBOOK_APP_SECRET=your_app_secret',
            '',
            '# Instagram (uses same Facebook app)',
            'INSTAGRAM_APP_ID=your_app_id',
            'INSTAGRAM_APP_SECRET=your_app_secret',
            '',
            '# Twitter/X',
            'TWITTER_API_KEY=your_api_key',
            'TWITTER_API_SECRET=your_api_secret',
            'TWITTER_BEARER_TOKEN=your_bearer_token',
            '',
            '# LinkedIn',
            'LINKEDIN_CLIENT_ID=your_client_id',
            'LINKEDIN_CLIENT_SECRET=your_client_secret',
            '',
            '# Queue Configuration (optional)',
            'SOCIAL_SYNC_QUEUE_ENABLED=true',
            'SOCIAL_SYNC_QUEUE_CONNECTION=database',
        ];

        foreach ($envVars as $var) {
            $this->line($var);
        }
    }
}
