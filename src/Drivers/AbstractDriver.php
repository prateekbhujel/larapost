<?php

namespace SocialSync\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use SocialSync\Contracts\SocialDriverInterface;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Models\SocialAccount;
use Throwable;

abstract class AbstractDriver implements SocialDriverInterface
{
    protected array $config;

    protected Client $client;

    public function __construct(array $config, ?Client $client = null)
    {
        $this->config = $config;
        $this->client = $client ?? new Client([
            'timeout' => 30,
        ]);
    }

    protected function requestJson(string $method, string $uri, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $uri, $options);
            $contents = (string) $response->getBody();

            if ($contents === '') {
                return [];
            }

            $decoded = json_decode($contents, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new SocialSyncException('Received an invalid JSON response from the platform API.');
            }

            if (is_array($decoded) && isset($decoded['error'])) {
                $message = is_array($decoded['error'])
                    ? ($decoded['error']['message'] ?? json_encode($decoded['error']))
                    : (string) $decoded['error'];

                throw new SocialSyncException('Platform API error: ' . $message);
            }

            return $decoded;
        } catch (GuzzleException $exception) {
            throw new SocialSyncException('HTTP request failed: ' . $exception->getMessage(), 0, $exception);
        } catch (Throwable $exception) {
            if ($exception instanceof SocialSyncException) {
                throw $exception;
            }

            throw new SocialSyncException($exception->getMessage(), 0, $exception);
        }
    }

    protected function configValue(string $key): mixed
    {
        $value = $this->config[$key] ?? null;

        if ($value === null || $value === '') {
            throw new SocialSyncException(sprintf('Missing required configuration value "%s".', $key));
        }

        return $value;
    }

    protected function credentialValue(array $credentials, string $key): mixed
    {
        $value = $credentials[$key] ?? null;

        if ($value === null || $value === '') {
            throw new SocialSyncException(sprintf('Missing required account credential "%s".', $key));
        }

        return $value;
    }

    protected function credentials(SocialAccount $account): array
    {
        return is_array($account->credentials) ? $account->credentials : [];
    }

    protected function normalizeMetaApiVersion(mixed $value, string $default = 'v20.0'): string
    {
        $version = trim((string) ($value ?? ''));

        if ($version === '') {
            return $default;
        }

        if (preg_match('/^v\d+(?:\.\d+)?$/i', $version) === 1) {
            return strtolower($version);
        }

        if (preg_match('/^\d+(?:\.\d+)?$/', $version) === 1) {
            return 'v' . (str_contains($version, '.') ? $version : $version . '.0');
        }

        return $default;
    }

    protected function rememberOauthContext(string $platform, string $state, array $payload, int $ttlSeconds = 600): void
    {
        if ($state === '') {
            return;
        }

        if (function_exists('cache')) {
            try {
                cache()->put(
                    $this->oauthContextCacheKey($platform, $state),
                    $payload,
                    new \DateTimeImmutable(sprintf('+%d seconds', $ttlSeconds))
                );
            } catch (Throwable) {
                // Cache is a resilience layer; session fallback still supports the flow.
            }
        }

        $this->storeSessionValues([
            $this->oauthContextSessionKey($platform, $state) => $payload,
        ]);
    }

    protected function pullOauthContext(string $platform, string $state): array
    {
        if ($state === '') {
            return [];
        }

        $cacheKey = $this->oauthContextCacheKey($platform, $state);

        if (function_exists('cache')) {
            try {
                $cached = cache()->get($cacheKey, []);
                cache()->forget($cacheKey);

                if (is_array($cached) && $cached !== []) {
                    return $cached;
                }
            } catch (Throwable) {
                // Ignore cache failures and fall back to session storage.
            }
        }

        $sessionKey = $this->oauthContextSessionKey($platform, $state);
        $stored = $this->sessionValue($sessionKey, []);
        $this->forgetSessionValues([$sessionKey]);

        return is_array($stored) ? $stored : [];
    }

    protected function requestInput(string $key, string $default = ''): string
    {
        if (!function_exists('request')) {
            return $default;
        }

        try {
            return (string) request()->input($key, $default);
        } catch (Throwable) {
            return $default;
        }
    }

    protected function storeSessionValues(array $values): void
    {
        if (!function_exists('session')) {
            return;
        }

        try {
            session($values);
        } catch (Throwable) {
            // Session storage is optional in tests and CLI contexts.
        }
    }

    protected function sessionValue(string $key, mixed $default = null): mixed
    {
        if (!function_exists('session')) {
            return $default;
        }

        try {
            return session($key, $default);
        } catch (Throwable) {
            return $default;
        }
    }

    protected function forgetSessionValues(array $keys): void
    {
        if (!function_exists('session')) {
            return;
        }

        try {
            $session = session();

            if (is_object($session) && method_exists($session, 'forget')) {
                $session->forget($keys);
            }
        } catch (Throwable) {
            // Ignore session teardown failures outside HTTP runtime.
        }
    }

    protected function oauthContextCacheKey(string $platform, string $state): string
    {
        return sprintf('larapost_oauth_%s_%s', strtolower($platform), $state);
    }

    protected function oauthContextSessionKey(string $platform, string $state): string
    {
        return sprintf('larapost_oauth_%s_%s', strtolower($platform), $state);
    }
}
