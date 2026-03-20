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

    public static function accountsFromCredentials(string $platform, array $credentials): array
    {
        return match ($platform) {
            'facebook' => self::facebookAccounts($credentials),
            default => [self::withCredentials(self::fromCredentials($platform, $credentials), $credentials)],
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

    protected static function facebookAccounts(array $credentials): array
    {
        $pages = array_values(array_filter($credentials['pages'] ?? [], static fn ($page): bool => is_array($page)));

        if ($pages === []) {
            return [self::withCredentials(self::facebook($credentials), $credentials)];
        }

        return array_map(function (array $page) use ($credentials): array {
            $pageCredentials = $credentials;
            $pageCredentials['page_id'] = (string) ($page['id'] ?? ($credentials['page_id'] ?? 'unknown'));

            if (!empty($page['access_token'])) {
                $pageCredentials['page_access_token'] = $page['access_token'];
            }

            return [
                'id' => (string) ($page['id'] ?? 'unknown'),
                'name' => $page['name'] ?? 'Facebook Page',
                'username' => null,
                'credentials' => $pageCredentials,
                'metadata' => [
                    'pages' => $credentials['pages'] ?? [],
                    'selected_page' => $page,
                ],
            ];
        }, $pages);
    }

    protected static function withCredentials(array $accountData, array $credentials): array
    {
        $accountData['credentials'] = $credentials;

        return $accountData;
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
