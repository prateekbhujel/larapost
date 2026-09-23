<?php

namespace SocialSync\Support;

use Carbon\CarbonImmutable;
use Throwable;

final class TokenCredentials
{
    public static function normalize(array $current, array $incoming): array
    {
        $merged = array_replace($current, array_filter(
            $incoming,
            static fn ($value): bool => $value !== null && $value !== ''
        ));

        if (array_key_exists('access_token', $incoming) && !array_key_exists('expires_in', $incoming)) {
            unset($merged['expires_at']);
        }

        if (isset($incoming['expires_in']) && is_numeric($incoming['expires_in'])) {
            $merged['expires_at'] = CarbonImmutable::now()
                ->addSeconds(max(0, (int) $incoming['expires_in']))
                ->toIso8601String();
        }

        if (array_key_exists('refresh_token', $incoming) && !array_key_exists('refresh_expires_in', $incoming)) {
            unset($merged['refresh_expires_at']);
        }

        if (isset($incoming['refresh_expires_in']) && is_numeric($incoming['refresh_expires_in'])) {
            $merged['refresh_expires_at'] = CarbonImmutable::now()
                ->addSeconds(max(0, (int) $incoming['refresh_expires_in']))
                ->toIso8601String();
        }

        return $merged;
    }

    public static function expiresSoon(array $credentials, int $leewaySeconds = 600): bool
    {
        $expiresAt = self::expiry($credentials);

        if ($expiresAt === null) {
            return false;
        }

        return $expiresAt->lessThanOrEqualTo(CarbonImmutable::now()->addSeconds(max(0, $leewaySeconds)));
    }

    public static function isExpired(array $credentials): bool
    {
        $expiresAt = self::expiry($credentials);

        return $expiresAt !== null && $expiresAt->lessThanOrEqualTo(CarbonImmutable::now());
    }

    private static function expiry(array $credentials): ?CarbonImmutable
    {
        $value = $credentials['expires_at'] ?? null;

        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
