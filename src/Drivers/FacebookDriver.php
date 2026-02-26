<?php

namespace SocialSync\Drivers;

use GuzzleHttp\Client;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Models\SocialAccount;

class FacebookDriver extends AbstractDriver
{
    protected string $apiVersion;

    public function __construct(array $config, ?Client $client = null)
    {
        $this->apiVersion = $config['api_version'] ?? 'v20.0';

        parent::__construct($config, $client ?? new Client([
            'base_uri' => sprintf('https://graph.facebook.com/%s/', $this->apiVersion),
            'timeout' => 30,
        ]));
    }

    public function publish(SocialAccount $account, array $payload): array
    {
        $credentials = $this->credentials($account);
        $pageId = $this->credentialValue($credentials, 'page_id');
        $accessToken = $this->credentialValue($credentials, 'access_token');

        $form = [
            'access_token' => $accessToken,
            'message' => $payload['content'] ?? '',
        ];

        $endpoint = sprintf('%s/feed', $pageId);

        if (!empty($payload['media'][0]['path'])) {
            $media = $payload['media'][0];
            $mediaPath = (string) $media['path'];

            if (($media['type'] ?? 'image') === 'video') {
                $endpoint = sprintf('%s/videos', $pageId);
                $form = [
                    'access_token' => $accessToken,
                    'description' => $payload['content'] ?? '',
                    'file_url' => $mediaPath,
                ];
            } else {
                $endpoint = sprintf('%s/photos', $pageId);
                $form = [
                    'access_token' => $accessToken,
                    'caption' => $payload['content'] ?? '',
                    'url' => $mediaPath,
                    'published' => true,
                ];
            }
        }

        return $this->requestJson('POST', $endpoint, [
            'form_params' => $form,
        ]);
    }

    public function getAuthorizationUrl(string $redirectUri): string
    {
        $params = http_build_query([
            'client_id' => $this->configValue('app_id'),
            'redirect_uri' => $redirectUri,
            'scope' => 'pages_show_list,pages_manage_posts,pages_read_engagement',
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
            throw new SocialSyncException('Facebook did not return an access token.');
        }

        $pagesData = $this->requestJson('GET', 'me/accounts', [
            'query' => [
                'access_token' => $accessToken,
                'fields' => 'id,name,access_token,instagram_business_account',
            ],
        ]);

        $pages = $pagesData['data'] ?? [];

        return [
            'access_token' => $accessToken,
            'page_id' => $pages[0]['id'] ?? null,
            'pages' => $pages,
        ];
    }

    public function refreshToken(array $credentials): array
    {
        return $this->requestJson('GET', 'oauth/access_token', [
            'query' => [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->configValue('app_id'),
                'client_secret' => $this->configValue('app_secret'),
                'fb_exchange_token' => $this->credentialValue($credentials, 'access_token'),
            ],
        ]);
    }

    public function verifyCredentials(array $credentials): bool
    {
        try {
            $this->requestJson('GET', 'me', [
                'query' => [
                    'fields' => 'id',
                    'access_token' => $this->credentialValue($credentials, 'access_token'),
                ],
            ]);

            return true;
        } catch (SocialSyncException) {
            return false;
        }
    }
}
