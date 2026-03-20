<?php

namespace SocialSync\Drivers;

use GuzzleHttp\Client;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Models\SocialAccount;

class InstagramDriver extends AbstractDriver
{
    protected string $apiVersion;

    public function __construct(array $config, ?Client $client = null)
    {
        $this->apiVersion = $this->normalizeMetaApiVersion($config['api_version'] ?? null);

        parent::__construct($config, $client ?? new Client([
            'base_uri' => sprintf('https://graph.facebook.com/%s/', $this->apiVersion),
            'timeout' => 30,
        ]));
    }

    public function publish(SocialAccount $account, array $payload): array
    {
        $credentials = $this->credentials($account);
        $instagramAccountId = $this->credentialValue($credentials, 'instagram_business_account_id');
        $accessToken = $this->credentialValue($credentials, 'access_token');

        $containerData = [
            'caption' => $payload['content'] ?? '',
            'access_token' => $accessToken,
        ];

        if (!empty($payload['media'][0])) {
            $media = $payload['media'][0];
            $mediaUrl = $this->prepareMediaUrl((string) ($media['path'] ?? ''));

            if (($media['type'] ?? 'image') === 'video') {
                $containerData['media_type'] = 'VIDEO';
                $containerData['video_url'] = $mediaUrl;
            } else {
                $containerData['image_url'] = $mediaUrl;
            }
        }

        $container = $this->requestJson('POST', sprintf('%s/media', $instagramAccountId), [
            'form_params' => $containerData,
        ]);

        $creationId = $container['id'] ?? null;

        if (!$creationId) {
            throw new SocialSyncException('Instagram media container creation failed.');
        }

        return $this->requestJson('POST', sprintf('%s/media_publish', $instagramAccountId), [
            'form_params' => [
                'creation_id' => $creationId,
                'access_token' => $accessToken,
            ],
        ]);
    }

    public function getAuthorizationUrl(string $redirectUri): string
    {
        $params = http_build_query([
            'client_id' => $this->configValue('app_id'),
            'redirect_uri' => $redirectUri,
            'scope' => 'instagram_basic,instagram_content_publish,pages_show_list',
            'response_type' => 'code',
        ]);

        return sprintf('https://www.facebook.com/%s/dialog/oauth?%s', $this->apiVersion, $params);
    }

    public function handleCallback(string $code, string $redirectUri): array
    {
        $tokenData = $this->requestJson('GET', 'oauth/access_token', [
            'query' => [
                'client_id' => $this->configValue('app_id'),
                'client_secret' => $this->configValue('app_secret'),
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ],
        ]);

        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            throw new SocialSyncException('Instagram did not return an access token.');
        }

        $pagesData = $this->requestJson('GET', 'me/accounts', [
            'query' => [
                'access_token' => $accessToken,
                'fields' => 'id,name,instagram_business_account',
            ],
        ]);

        $pages = $pagesData['data'] ?? [];
        $instagramBusinessAccountId = $pages[0]['instagram_business_account']['id'] ?? null;

        return [
            'access_token' => $accessToken,
            'instagram_business_account_id' => $instagramBusinessAccountId,
            'pages' => $pages,
        ];
    }

    public function refreshToken(array $credentials): array
    {
        return $this->requestJson('GET', 'refresh_access_token', [
            'query' => [
                'grant_type' => 'ig_refresh_token',
                'access_token' => $this->credentialValue($credentials, 'access_token'),
            ],
        ]);
    }

    public function verifyCredentials(array $credentials): bool
    {
        try {
            $this->requestJson('GET', (string) $this->credentialValue($credentials, 'instagram_business_account_id'), [
                'query' => [
                    'fields' => 'id,username',
                    'access_token' => $this->credentialValue($credentials, 'access_token'),
                ],
            ]);

            return true;
        } catch (SocialSyncException) {
            return false;
        }
    }

    protected function prepareMediaUrl(string $path): string
    {
        if ($path === '') {
            throw new SocialSyncException('Media path cannot be empty for Instagram posts.');
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        throw new SocialSyncException(
            'Instagram requires public media URLs. Upload your file to public storage (S3/CDN) and pass the URL.'
        );
    }
}
