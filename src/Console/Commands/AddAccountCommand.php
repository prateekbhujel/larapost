<?php

namespace SocialSync\Console\Commands;

use Illuminate\Console\Command;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Facades\SocialMedia;
use SocialSync\Models\SocialAccount;
use SocialSync\Support\AccountDataResolver;

class AddAccountCommand extends Command
{
    protected $signature = 'larapost:add-account {platform : facebook|twitter|linkedin}';

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
            $accounts = $this->persistConnectedAccounts($platform, $credentials);

            if ($accounts->count() === 1) {
                $account = $accounts->first();
                $this->info(sprintf('%s account connected successfully (id: %d).', ucfirst($platform), $account->id));

                return self::SUCCESS;
            }

            $this->info(sprintf('%s connected successfully. %d account(s) synced.', ucfirst($platform), $accounts->count()));
            foreach ($accounts as $account) {
                $this->line(sprintf('- #%d %s', $account->id, $account->account_name));
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Failed to add account: ' . $exception->getMessage());

            return self::FAILURE;
        }
    }

    protected function persistConnectedAccounts(string $platform, array $credentials)
    {
        return collect(AccountDataResolver::accountsFromCredentials($platform, $credentials))
            ->map(function (array $accountData) use ($platform, $credentials) {
                $accountCredentials = is_array($accountData['credentials'] ?? null)
                    ? $accountData['credentials']
                    : $credentials;

                return SocialAccount::query()->updateOrCreate(
                    [
                        'platform' => $platform,
                        'account_id_on_platform' => $accountData['id'],
                    ],
                    [
                        'account_name' => $accountData['name'],
                        'account_username' => $accountData['username'],
                        'credentials' => $accountCredentials,
                        'metadata' => $accountData['metadata'] ?? [],
                        'is_active' => true,
                    ]
                );
            })
            ->values();
    }
}
