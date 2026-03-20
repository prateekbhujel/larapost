<?php

namespace SocialSync\Tests\Fixtures;

use SocialSync\Contracts\SocialDriverInterface;
use SocialSync\Models\SocialAccount;

class FakeDriver implements SocialDriverInterface
{
    public function __construct(protected array $config = [])
    {
    }

    public function publish(SocialAccount $account, array $payload): array
    {
        if (str_contains(strtolower((string) ($payload['content'] ?? '')), 'fail')) {
            throw new \RuntimeException('Forced failure for testing.');
        }

        return [
            'id' => 'fake-post-' . $account->id,
            'platform' => $account->platform,
            'echo' => $payload,
        ];
    }

    public function getAuthorizationUrl(string $redirectUri): string
    {
        $separator = str_contains($redirectUri, '?') ? '&' : '?';

        return $redirectUri . $separator . 'oauth=fake';
    }

    public function handleCallback(string $code, string $redirectUri): array
    {
        if ($code === 'multi-page') {
            return [
                'access_token' => 'fake-token',
                'page_id' => 'fake-page-1',
                'pages' => [
                    ['id' => 'fake-page-1', 'name' => 'Fake Page One', 'access_token' => 'page-token-1'],
                    ['id' => 'fake-page-2', 'name' => 'Fake Page Two', 'access_token' => 'page-token-2'],
                ],
                'user' => [
                    'id' => '123',
                    'name' => 'Fake User',
                    'username' => 'fake-user',
                ],
                'profile' => [
                    'id' => 'profile-1',
                    'localizedFirstName' => 'Fake',
                    'localizedLastName' => 'User',
                ],
                'person_urn' => 'urn:li:person:profile-1',
                'instagram_business_account_id' => 'ig-1',
            ];
        }

        return [
            'access_token' => 'fake-token',
            'page_id' => 'fake-page-id',
            'pages' => [
                ['id' => 'fake-page-id', 'name' => 'Fake Page', 'access_token' => 'fake-page-token'],
            ],
            'user' => [
                'id' => '123',
                'name' => 'Fake User',
                'username' => 'fake-user',
            ],
            'profile' => [
                'id' => 'profile-1',
                'localizedFirstName' => 'Fake',
                'localizedLastName' => 'User',
            ],
            'person_urn' => 'urn:li:person:profile-1',
            'instagram_business_account_id' => 'ig-1',
        ];
    }

    public function refreshToken(array $credentials): array
    {
        return ['access_token' => 'fake-refresh-token'];
    }

    public function verifyCredentials(array $credentials): bool
    {
        return true;
    }
}
