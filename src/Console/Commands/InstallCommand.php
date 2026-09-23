<?php

namespace SocialSync\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class InstallCommand extends Command
{
    protected $signature = 'larapost:install
        {--force : Overwrite published files}
        {--platforms= : Comma-separated platforms: facebook,twitter,linkedin,tiktok}
        {--mcp : Prepare optional MCP integration}
        {--no-dashboard : Disable the built-in operator dashboard}
        {--no-migrate : Do not run migrations}
        {--no-env : Do not offer to write LaraPost choices to .env}';

    protected $description = 'Install LaraPost and choose only the publishing surfaces your application needs.';

    public function handle(): int
    {
        $this->info('Installing LaraPost...');

        $platforms = $this->selectedPlatforms();
        $dashboard = !$this->option('no-dashboard');
        $mcp = (bool) $this->option('mcp');

        if ($this->input->isInteractive() && !$this->option('mcp')) {
            $mcp = $this->confirm('Enable the optional MCP server for AI clients such as ChatGPT or Claude?', false);
        }

        $this->publishPackageFiles();
        $this->createStorageDirectories();

        if (!$this->option('no-migrate') && (!$this->input->isInteractive() || $this->confirm('Run migrations now?', true))) {
            $this->call('migrate');
        }

        $values = [
            'LARAPOST_PLATFORMS' => implode(',', $platforms),
            'LARAPOST_UI_ENABLED' => $dashboard ? 'true' : 'false',
            'LARAPOST_MCP_ENABLED' => $mcp ? 'true' : 'false',
        ];

        if ($mcp) {
            if (!class_exists(\Laravel\Mcp\Server::class)) {
                $this->warn('MCP was selected, but laravel/mcp is not installed.');
                $this->line('Run: composer require laravel/mcp:^1.0');
            }

            $values['LARAPOST_MCP_TOKEN'] = $this->existingEnvValue('LARAPOST_MCP_TOKEN') ?: Str::random(64);
        }

        if (!$this->option('no-env') && $this->input->isInteractive() && $this->confirm('Write these LaraPost choices to .env?', true)) {
            $this->writeEnvValues($values);
            $this->info('Updated .env.');
        } else {
            $this->displayEnvValues($values);
        }

        $this->displayProviderVariables($platforms);

        $this->newLine();
        $this->info('LaraPost installation is ready.');
        $this->line('Run php artisan larapost:doctor before connecting real accounts.');
        $this->line('Documentation: https://prateekbhujel.github.io/larapost/');

        return self::SUCCESS;
    }

    protected function selectedPlatforms(): array
    {
        $supported = ['facebook', 'twitter', 'linkedin', 'tiktok'];
        $option = trim((string) $this->option('platforms'));

        if ($option === '' && $this->input->isInteractive()) {
            $selected = $this->choice(
                'Which platforms should LaraPost enable?',
                $supported,
                'facebook',
                null,
                true
            );

            $platforms = is_array($selected) ? $selected : [$selected];
        } else {
            $platforms = $option === '' ? $supported : explode(',', $option);
        }

        $platforms = array_values(array_unique(array_filter(array_map(
            static fn ($platform): string => strtolower(trim((string) $platform)),
            $platforms
        ))));

        $invalid = array_diff($platforms, $supported);

        if ($invalid !== []) {
            throw new RuntimeException('Unsupported platform(s): ' . implode(', ', $invalid));
        }

        if ($platforms === []) {
            throw new RuntimeException('Choose at least one publishing platform.');
        }

        return $platforms;
    }

    protected function publishPackageFiles(): void
    {
        foreach (['larapost-config', 'larapost-migrations', 'larapost-views'] as $tag) {
            $this->call('vendor:publish', [
                '--tag' => $tag,
                '--force' => (bool) $this->option('force'),
            ]);
        }
    }

    protected function createStorageDirectories(): void
    {
        $tempUploadPath = (string) config('larapost.media.temp_upload_path', storage_path('app/larapost/temp'));

        foreach ([dirname($tempUploadPath), $tempUploadPath] as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->line(sprintf('Created %s', $directory));
            }
        }
    }

    protected function writeEnvValues(array $values): void
    {
        $path = base_path('.env');

        if (!File::exists($path)) {
            $this->warn('No .env file was found. Printing the values instead.');
            $this->displayEnvValues($values);

            return;
        }

        $contents = File::get($path);

        foreach ($values as $key => $value) {
            $escaped = preg_quote($key, '/');
            $line = $key . '=' . $value;

            if (preg_match('/^' . $escaped . '=.*$/m', $contents)) {
                $contents = preg_replace('/^' . $escaped . '=.*$/m', $line, $contents, 1) ?? $contents;
            } else {
                $contents = rtrim($contents) . PHP_EOL . $line . PHP_EOL;
            }
        }

        File::put($path, $contents);
    }

    protected function existingEnvValue(string $key): ?string
    {
        $path = base_path('.env');

        if (!File::exists($path)) {
            return null;
        }

        if (!preg_match('/^' . preg_quote($key, '/') . '=(.*)$/m', File::get($path), $match)) {
            return null;
        }

        return trim($match[1], " \t\n\r\0\x0B\"'");
    }

    protected function displayEnvValues(array $values): void
    {
        $this->newLine();
        $this->line('Add these LaraPost choices to .env:');

        foreach ($values as $key => $value) {
            $this->line($key . '=' . $value);
        }
    }

    protected function displayProviderVariables(array $platforms): void
    {
        $variables = [
            'facebook' => [
                'FACEBOOK_APP_ID=',
                'FACEBOOK_APP_SECRET=',
                'FACEBOOK_API_VERSION=v20.0',
            ],
            'twitter' => [
                'TWITTER_CLIENT_ID=',
                'TWITTER_CLIENT_SECRET=',
                'TWITTER_BACKEND=twitter',
            ],
            'linkedin' => [
                'LINKEDIN_CLIENT_ID=',
                'LINKEDIN_CLIENT_SECRET=',
            ],
            'tiktok' => [
                'TIKTOK_CLIENT_KEY=',
                'TIKTOK_CLIENT_SECRET=',
                'TIKTOK_PUBLISH_MODE=upload',
            ],
        ];

        $this->newLine();
        $this->line('Provider credentials needed for your selection:');

        foreach ($platforms as $platform) {
            $this->line('');
            $this->line('# ' . ucfirst($platform));

            foreach ($variables[$platform] as $variable) {
                $this->line($variable);
            }
        }
    }
}
