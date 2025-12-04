<?php

namespace SocialSync\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \SocialSync\PostBuilder post()
 * @method static \SocialSync\Contracts\SocialDriverInterface driver(string $name = null)
 * @method static array publish(int $accountId, array $data)
 * @method static string getAuthorizationUrl(string $redirectUri)
 * @method static array handleCallback(string $code, string $redirectUri)
 * @method static array refreshToken(array $credentials)
 * @method static bool verifyCredentials(array $credentials)
 *
 * @see \SocialSync\SocialMediaManager
 */
class SocialMedia extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'social-media';
    }
}
