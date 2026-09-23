<?php

namespace SocialSync;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;
use SocialSync\Contracts\SocialDriverInterface;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Models\PlatformCredential;
use SocialSync\Models\SocialAccount;
use SocialSync\Support\TokenCredentials;
use Throwable;

class SocialMediaManager
{
    protected array $config;

    protected ?Container $container;

    /**
     * @var array<string, SocialDriverInterface>
     */
    protected array $resolvedDrivers = [];

    /**
     * @var array<string, class-string<SocialDriverInterface>|callable>
     */
    protected array $customDrivers = [];

    protected ?bool $hasPlatformCredentialTable = null;

    public function __construct(array $config = [], ?Container $container = null)
    {
        $this->config = $config;
        $this->container = $container;
    }

    public function post(): PostBuilder
    {
        return new PostBuilder($this);
    }

    public function driver(?string $name = null): SocialDriverInterface
    {
        $platform = strtolower((string) ($name ?: $this->defaultPlatform()));

        if (isset($this->resolvedDrivers[$platform])) {
            return $this->resolvedDrivers[$platform];
        }

        $driverDefinition = $this->driverMap()[$platform] ?? null;

        if (!$driverDefinition) {
            throw new SocialSyncException(sprintf(
                'Unsupported or disabled platform "%s". Enabled platforms: %s',
                $platform,
                implode(', ', $this->supportedPlatforms())
            ));
        }

        $platformConfig = $this->platformConfig($platform);
        $driver = $this->resolveDriverDefinition($driverDefinition, $platform, $platformConfig);

        if (!$driver instanceof SocialDriverInterface) {
            throw new SocialSyncException(sprintf(
                'Driver for "%s" must implement %s.',
                $platform,
                SocialDriverInterface::class
            ));
        }

        return $this->resolvedDrivers[$platform] = $driver;
    }

    /**
     * Register or replace a driver without editing the package config file.
     *
     * @param  class-string<SocialDriverInterface>|callable  $driver
     */
    public function extend(string $platform, string|callable $driver): void
    {
        $platform = strtolower(trim($platform));

        if ($platform === '') {
            throw new SocialSyncException('Driver platform name cannot be empty.');
        }

        $this->customDrivers[$platform] = $driver;
        unset($this->resolvedDrivers[$platform]);
    }

    public function forgetDriver(?string $name = null): void
    {
        if ($name === null) {
            $this->resolvedDrivers = [];

            return;
        }

        unset($this->resolvedDrivers[strtolower($name)]);
    }

    public function publish(int $accountId, array $payload): array
    {
        try {
            $account = SocialAccount::query()->active()->findOrFail($accountId);
            $driver = $this->driver($account->platform);
            $account = $this->refreshExpiringCredentials($account, $driver);

            $response = $driver->publish($account, $payload);

            $account->forceFill(['last_used_at' => now()])->save();

            return [
                'success' => true,
                'platform' => $account->platform,
                'account_id' => $account->id,
                'post_id' => $response['id'] ?? $response['data']['id'] ?? $response['publish_id'] ?? null,
                'response' => $response,
            ];
        } catch (ModelNotFoundException) {
            return [
                'success' => false,
                'account_id' => $accountId,
                'error' => 'Active social account not found.',
            ];
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'account_id' => $accountId,
                'error' => $exception->getMessage(),
            ];
        }
    }

    public function supportedPlatforms(): array
    {
        return array_keys($this->driverMap());
    }

    public function defaultPlatform(): string
    {
        $default = strtolower((string) ($this->config['default_platform'] ?? 'facebook'));

        if (!in_array($default, $this->supportedPlatforms(), true)) {
            return $this->supportedPlatforms()[0] ?? $default;
        }

        return $default;
    }

    public function platformConfig(string $platform): array
    {
        $platform = strtolower($platform);

        $configured = (array) ($this->config['platforms'][$platform] ?? []);
        $stored = $this->databasePlatformConfig($platform);
        $merged = array_replace($configured, $stored);

        return array_filter($merged, static fn ($value) => $value !== null && $value !== '');
    }

    protected function driverMap(): array
    {
        $drivers = array_replace((array) ($this->config['drivers'] ?? []), $this->customDrivers);
        $enabled = array_values(array_unique(array_filter(array_map(
            static fn ($platform): string => strtolower(trim((string) $platform)),
            (array) ($this->config['enabled_platforms'] ?? array_keys($drivers))
        ))));

        if ($enabled === []) {
            return [];
        }

        return array_intersect_key($drivers, array_flip($enabled));
    }

    protected function resolveDriverDefinition(mixed $definition, string $platform, array $platformConfig): mixed
    {
        if (is_callable($definition) && !is_string($definition)) {
            return $definition($platformConfig, $this->container, $platform);
        }

        if (!is_string($definition) || $definition === '') {
            throw new SocialSyncException(sprintf('Invalid driver definition for "%s".', $platform));
        }

        return $this->container
            ? $this->container->make($definition, ['config' => $platformConfig])
            : new $definition($platformConfig);
    }

    protected function refreshExpiringCredentials(SocialAccount $account, SocialDriverInterface $driver): SocialAccount
    {
        $credentials = is_array($account->credentials) ? $account->credentials : [];

        if (!TokenCredentials::expiresSoon($credentials)) {
            return $account;
        }

        try {
            $refreshed = $driver->refreshToken($credentials);

            if ($refreshed === []) {
                return $account;
            }

            $account->credentials = TokenCredentials::normalize($credentials, $refreshed);
            $account->save();

            return $account->refresh();
        } catch (Throwable $exception) {
            if (TokenCredentials::isExpired($credentials)) {
                throw new SocialSyncException(
                    sprintf('The %s access token expired and could not be refreshed. Reconnect the account.', $account->platform),
                    0,
                    $exception
                );
            }

            return $account;
        }
    }

    protected function databasePlatformConfig(string $platform): array
    {
        if (!$this->isPlatformCredentialStoreAvailable()) {
            return [];
        }

        try {
            $record = PlatformCredential::query()->platform($platform)->first();

            return is_array($record?->credentials) ? $record->credentials : [];
        } catch (Throwable) {
            return [];
        }
    }

    protected function isPlatformCredentialStoreAvailable(): bool
    {
        if ($this->hasPlatformCredentialTable !== null) {
            return $this->hasPlatformCredentialTable;
        }

        try {
            $this->hasPlatformCredentialTable = Schema::hasTable('larapost_platform_credentials');
        } catch (Throwable) {
            $this->hasPlatformCredentialTable = false;
        }

        return $this->hasPlatformCredentialTable;
    }
}
