<?php

namespace SocialSync\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \SocialSync\PostBuilder post()
 * @method static \SocialSync\Contracts\SocialDriverInterface driver(string|null $name = null)
 * @method static array publish(int $accountId, array $payload)
 * @method static array supportedPlatforms()
 * @method static string defaultPlatform()
 *
 * @see \SocialSync\SocialMediaManager
 */
class SocialMedia extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'social-media';
    }
}
