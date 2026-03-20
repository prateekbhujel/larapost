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
}
