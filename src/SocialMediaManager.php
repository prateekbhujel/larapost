<?php

namespace SocialSync;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use SocialSync\Contracts\SocialDriverInterface;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Models\SocialAccount;

class SocialMediaManager
{
    protected array $config;

    protected ?Container $container;

    /**
     * @var array<string, \SocialSync\Contracts\SocialDriverInterface>
     */
    protected array $resolvedDrivers = [];

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
        return (array) ($this->config['platforms'][$platform] ?? []);
    }

    protected function driverMap(): array
    {
        return (array) ($this->config['drivers'] ?? []);
    }
}
