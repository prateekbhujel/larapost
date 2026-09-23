<?php

namespace SocialSync\Tests\Feature;

use SocialSync\Contracts\SocialDriverInterface;
use SocialSync\Facades\SocialMedia;
use SocialSync\Models\SocialAccount;
use SocialSync\Tests\TestCase;

class TokenRefreshTest extends TestCase
{
    public function test_expiring_credentials_are_refreshed_before_publish(): void
    {
        SocialMedia::extend('facebook', RefreshingDriver::class);

        $account = SocialAccount::query()->create([
            'platform' => 'facebook',
            'account_name' => 'Refresh Test',
            'account_id_on_platform' => 'refresh-test',
            'credentials' => [
                'access_token' => 'old-token',
                'refresh_token' => 'refresh-token',
                'expires_at' => now()->subMinute()->toIso8601String(),
            ],
            'is_active' => true,
        ]);

        $result = SocialMedia::publish($account->id, [
            'content' => 'Hello',
            'media' => [],
            'metadata' => [],
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('new-token', $result['response']['token']);

        $account->refresh();
        $this->assertSame('new-token', $account->credentials['access_token']);
        $this->assertNotSame('old-token', $account->credentials['access_token']);
    }
}

class RefreshingDriver implements SocialDriverInterface
{
    public function __construct(array $config = [])
    {
    }

    public function publish(SocialAccount $account, array $payload): array
    {
        return [
            'id' => 'refreshed-post',
            'token' => $account->credentials['access_token'] ?? null,
        ];
    }

    public function getAuthorizationUrl(string $redirectUri): string
    {
        return $redirectUri;
    }

    public function handleCallback(string $code, string $redirectUri): array
    {
        return [];
    }

    public function refreshToken(array $credentials): array
    {
        return [
            'access_token' => 'new-token',
            'refresh_token' => $credentials['refresh_token'] ?? null,
            'expires_in' => 3600,
        ];
    }

    public function verifyCredentials(array $credentials): bool
    {
        return true;
    }
}
