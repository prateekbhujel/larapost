<?php

namespace SocialSync;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;
use SocialSync\Contracts\SocialDriverInterface;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Models\PlatformCredential;
use SocialSync\Models\SocialAccount;

class SocialMediaManager
{
    protected array $config;

    protected ?Container $container;

    /**
     * @var array<string, \SocialSync\Contracts\SocialDriverInterface>
     */
    protected array $resolvedDrivers = [];

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

        $driverClass = $this->driverMap()[$platform] ?? null;

        if (!$driverClass) {
            throw new SocialSyncException(sprintf(
                'Unsupported platform "%s". Supported platforms: %s',
                $platform,
                implode(', ', $this->supportedPlatforms())
            ));
        }

        $platformConfig = $this->platformConfig($platform);

        $driver = $this->container
            ? $this->container->make($driverClass, ['config' => $platformConfig])
            : new $driverClass($platformConfig);

        if (!$driver instanceof SocialDriverInterface) {
            throw new SocialSyncException(sprintf(
                'Driver "%s" must implement %s.',
                $driverClass,
                SocialDriverInterface::class
            ));
        }

        return $this->resolvedDrivers[$platform] = $driver;
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

            $response = $driver->publish($account, $payload);

            $account->forceFill(['last_used_at' => now()])->save();

            return [
                'success' => true,
                'platform' => $account->platform,
                'account_id' => $account->id,
                'post_id' => $response['id'] ?? $response['data']['id'] ?? null,
                'response' => $response,
            ];
        } catch (ModelNotFoundException) {
            return [
                'success' => false,
                'account_id' => $accountId,
                'error' => 'Active social account not found.',
            ];
        } catch (\Throwable $exception) {
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
        return (string) ($this->config['default_platform'] ?? 'facebook');
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
        return (array) ($this->config['drivers'] ?? []);
    }

    protected function databasePlatformConfig(string $platform): array
    {
        if (!$this->isPlatformCredentialStoreAvailable()) {
            return [];
        }

        try {
            $record = PlatformCredential::query()->platform($platform)->first();

            return is_array($record?->credentials) ? $record->credentials : [];
        } catch (\Throwable) {
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
        } catch (\Throwable) {
            $this->hasPlatformCredentialTable = false;
        }

        return $this->hasPlatformCredentialTable;
    }
}
