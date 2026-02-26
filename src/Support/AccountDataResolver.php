<?php

namespace SocialSync\Support;

class AccountDataResolver
{
    public static function fromCredentials(string $platform, array $credentials): array
    {
        return match ($platform) {
            'facebook' => self::facebook($credentials),
            'instagram' => self::instagram($credentials),
            'twitter' => self::twitter($credentials),
            'linkedin' => self::linkedin($credentials),
            default => self::fallback($platform),
        };
    }

    protected static function facebook(array $credentials): array
    {
        $page = $credentials['pages'][0] ?? [];

        return [
            'id' => (string) ($page['id'] ?? 'unknown'),
            'name' => $page['name'] ?? 'Facebook Page',
            'username' => null,
            'metadata' => ['pages' => $credentials['pages'] ?? []],
        ];
    }

    protected static function instagram(array $credentials): array
    {
        $page = $credentials['pages'][0] ?? [];
        $instagramBusinessAccount = $page['instagram_business_account'] ?? [];

        return [
            'id' => (string) ($credentials['instagram_business_account_id'] ?? $instagramBusinessAccount['id'] ?? 'unknown'),
            'name' => $page['name'] ?? 'Instagram Business Account',
            'username' => $credentials['username'] ?? null,
            'metadata' => [
                'pages' => $credentials['pages'] ?? [],
                'profile' => $credentials['profile'] ?? null,
            ],
        ];
    }

    protected static function twitter(array $credentials): array
    {
        $user = $credentials['user'] ?? [];

        return [
            'id' => (string) ($credentials['user_id'] ?? $user['id'] ?? 'unknown'),
            'name' => $user['name'] ?? 'Twitter/X Account',
            'username' => $user['username'] ?? null,
            'metadata' => ['user' => $user],
        ];
    }

    protected static function linkedin(array $credentials): array
    {
        $profile = $credentials['profile'] ?? [];

        $fullName = trim(implode(' ', array_filter([
            $profile['localizedFirstName'] ?? null,
            $profile['localizedLastName'] ?? null,
        ])));

        return [
            'id' => (string) ($profile['id'] ?? 'unknown'),
            'name' => $fullName !== '' ? $fullName : 'LinkedIn Profile',
            'username' => null,
            'metadata' => ['profile' => $profile],
        ];
    }

    protected static function fallback(string $platform): array
    {
        return [
            'id' => 'unknown',
            'name' => ucfirst($platform) . ' Account',
            'username' => null,
            'metadata' => [],
        ];
    }
}
