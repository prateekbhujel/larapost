<?php

namespace SocialSync\Console\Commands;

use Illuminate\Console\Command;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Facades\SocialMedia;
use SocialSync\Models\SocialAccount;
use SocialSync\Support\AccountDataResolver;

class AddAccountCommand extends Command
{
    protected $signature = 'larapost:add-account {platform : facebook|instagram|twitter|linkedin}';

    protected $description = 'Connect a social account and store credentials securely.';

    public function handle(): int
    {
        $platform = strtolower((string) $this->argument('platform'));

        if (!in_array($platform, SocialMedia::supportedPlatforms(), true)) {
            $this->error('Invalid platform. Supported: ' . implode(', ', SocialMedia::supportedPlatforms()));

            return self::FAILURE;
        }

        try {
            $driver = SocialMedia::driver($platform);
            $callbackUrl = route('larapost.callback', ['platform' => $platform], false);
            $callbackUrl = url($callbackUrl);
            $authUrl = $driver->getAuthorizationUrl($callbackUrl);

            $this->newLine();
            $this->line('Open this URL in your browser and complete the OAuth flow:');
            $this->warn($authUrl);
            $this->newLine();

            $code = (string) $this->ask('Paste the authorization code');

            if ($code === '') {
                throw new SocialSyncException('Authorization code is required.');
            }

            $credentials = $driver->handleCallback($code, $callbackUrl);
            $accountData = AccountDataResolver::fromCredentials($platform, $credentials);

            $account = SocialAccount::query()->updateOrCreate(
                [
                    'platform' => $platform,
                    'account_id_on_platform' => $accountData['id'],
                ],
                [
                    'account_name' => $accountData['name'],
                    'account_username' => $accountData['username'],
                    'credentials' => $credentials,
                    'metadata' => $accountData['metadata'] ?? [],
                    'is_active' => true,
                ]
            );

            $this->info(sprintf('%s account connected successfully (id: %d).', ucfirst($platform), $account->id));

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Failed to add account: ' . $exception->getMessage());

            return self::FAILURE;
        }
    }
}
