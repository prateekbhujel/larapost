<?php

namespace SocialSync\Contracts;

use SocialSync\Models\SocialAccount;

interface SocialDriverInterface
{
    public function publish(SocialAccount $account, array $payload): array;

    public function getAuthorizationUrl(string $redirectUri): string;

    public function handleCallback(string $code, string $redirectUri): array;

    public function refreshToken(array $credentials): array;

    public function verifyCredentials(array $credentials): bool;
}
