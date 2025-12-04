<?php

namespace SocialSync\Console\Commands;

use Illuminate\Console\Command;
use SocialSync\Facades\SocialMedia;
use SocialSync\Models\SocialAccount;

class AddAccountCommand extends Command
{
    protected $signature = 'social-sync:add-account {platform : The social platform (facebook, instagram, twitter, linkedin)}';
    protected $description = 'Add a new social media account';

    public function handle()
    {
        $platform = strtolower($this->argument('platform'));

        $validPlatforms = ['facebook', 'instagram', 'twitter', 'linkedin'];

        if (!in_array($platform, $validPlatforms)) {
            $this->error("Invalid platform. Choose from: " . implode(', ', $validPlatforms));
            return 1;
        }

        $this->info("Adding {$platform} account...");
        $this->newLine();

        // Check if credentials are configured
        $config = config("social-sync.platforms.{$platform}");
        if (empty($config)) {
            $this->error("Platform '{$platform}' is not configured in config/social-sync.php");
            return 1;
        }

        try {
            $driver = SocialMedia::driver($platform);

            // Generate OAuth URL
            $callbackUrl = url("/social-sync/callback/{$platform}");
            $authUrl = $driver->getAuthorizationUrl($callbackUrl);

            $this->info("Step 1: Authorize the application");
            $this->line("Open this URL in your browser:");
            $this->newLine();
            $this->warn($authUrl);
            $this->newLine();

            // Simpler flow for CLI
            $this->info("After authorizing, you'll be redirected to a callback URL.");
            $this->info("Copy the 'code' parameter from the URL and paste it here:");
            $this->newLine();

            $code = $this->ask('Enter the authorization code');

            if (empty($code)) {
                $this->error('Authorization code is required.');
                return 1;
            }

            // Exchange code for tokens
            $this->info("Exchanging code for access token...");
            $credentials = $driver->handleCallback($code, $callbackUrl);

            // Save account
            $accountData = $this->prepareAccountData($platform, $credentials);

            $account = SocialAccount::create([
                'platform' => $platform,
                'account_name' => $accountData['name'],
                'account_username' => $accountData['username'] ?? null,
                'account_id_on_platform' => $accountData['id'],
                'credentials' => $credentials,
                'is_active' => true,
                'metadata' => $accountData['metadata'] ?? [],
            ]);

            $this->newLine();
            $this->info("✓ {$platform} account added successfully!");
            $this->table(
                ['ID', 'Platform', 'Name', 'Username'],
                [[$account->id, $account->platform, $account->account_name, $account->account_username ?? 'N/A']]
            );

        } catch (\Exception $e) {
            $this->error("Failed to add account: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function prepareAccountData(string $platform, array $credentials): array
    {
        switch ($platform) {
            case 'facebook':
                return [
                    'id' => $credentials['pages'][0]['id'] ?? 'unknown',
                    'name' => $credentials['pages'][0]['name'] ?? 'Facebook Page',
                    'username' => null,
                    'metadata' => ['pages' => $credentials['pages']],
                ];

            case 'instagram':
                $page = $credentials['pages'][0] ?? [];
                $igAccount = $page['instagram_business_account'] ?? [];

                return [
                    'id' => $igAccount['id'] ?? 'unknown',
                    'name' => $page['name'] ?? 'Instagram Account',
                    'username' => null,
                    'metadata' => ['facebook_page' => $page],
                ];

            case 'twitter':
                return [
                    'id' => $credentials['user']['id'] ?? 'unknown',
                    'name' => $credentials['user']['name'] ?? 'Twitter Account',
                    'username' => $credentials['user']['username'] ?? null,
                    'metadata' => ['user' => $credentials['user']],
                ];

            case 'linkedin':
                $profile = $credentials['profile'] ?? [];
                $firstName = $profile['localizedFirstName'] ?? '';
                $lastName = $profile['localizedLastName'] ?? '';

                return [
                    'id' => $profile['id'] ?? 'unknown',
                    'name' => trim("{$firstName} {$lastName}") ?: 'LinkedIn Profile',
                    'username' => null,
                    'metadata' => ['profile' => $profile],
                ];

            default:
                return [
                    'id' => 'unknown',
                    'name' => ucfirst($platform) . ' Account',
                    'username' => null,
                ];
        }
    }
}
