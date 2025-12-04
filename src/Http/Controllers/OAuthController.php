<?php

namespace SocialSync\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SocialSync\Facades\SocialMedia;
use SocialSync\Models\SocialAccount;

class OAuthController extends Controller
{
    public function connect($platform)
    {
        try {
            $driver = SocialMedia::driver($platform);
            $callbackUrl = route('social-sync.callback', ['platform' => $platform]);
            $authUrl = $driver->getAuthorizationUrl($callbackUrl);

            return redirect($authUrl);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to initiate OAuth: ' . $e->getMessage()
            ], 500);
        }
    }

    public function callback(Request $request, $platform)
    {
        try {
            if ($request->has('error')) {
                throw new \Exception($request->get('error_description', 'OAuth authorization failed'));
            }

            $code = $request->get('code');
            if (!$code) {
                throw new \Exception('No authorization code received');
            }

            $driver = SocialMedia::driver($platform);
            $callbackUrl = route('social-sync.callback', ['platform' => $platform]);

            $credentials = $driver->handleCallback($code, $callbackUrl);

            // Prepare account data
            $accountData = $this->prepareAccountData($platform, $credentials);

            // Check if account already exists
            $existingAccount = SocialAccount::where('platform', $platform)
                ->where('account_id_on_platform', $accountData['id'])
                ->first();

            if ($existingAccount) {
                // Update existing account
                $existingAccount->update([
                    'credentials' => $credentials,
                    'is_active' => true,
                    'account_name' => $accountData['name'],
                    'account_username' => $accountData['username'] ?? null,
                ]);
                $account = $existingAccount;
                $message = 'Account reconnected successfully!';
            } else {
                // Create new account
                $account = SocialAccount::create([
                    'platform' => $platform,
                    'account_name' => $accountData['name'],
                    'account_username' => $accountData['username'] ?? null,
                    'account_id_on_platform' => $accountData['id'],
                    'credentials' => $credentials,
                    'is_active' => true,
                    'metadata' => $accountData['metadata'] ?? [],
                ]);
                $message = 'Account connected successfully!';
            }

            // Return success view or redirect
            return view('social-sync::oauth-success', [
                'platform' => $platform,
                'account' => $account,
                'message' => $message,
            ]);

        } catch (\Exception $e) {
            return view('social-sync::oauth-error', [
                'platform' => $platform,
                'error' => $e->getMessage(),
            ]);
        }
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
