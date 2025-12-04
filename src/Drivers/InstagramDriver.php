<?php

namespace SocialSync\Drivers;

use GuzzleHttp\Client;
use SocialSync\Contracts\SocialDriverInterface;
use SocialSync\Models\SocialAccount;

class InstagramDriver implements SocialDriverInterface
{
    protected $config;
    protected $client;
    protected $apiVersion = 'v18.0';

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'base_uri' => "https://graph.facebook.com/{$this->apiVersion}/",
            'timeout' => 30,
        ]);
    }

    public function publish(int $accountId, array $data): array
    {
        $account = SocialAccount::findOrFail($accountId);
        $credentials = json_decode($account->credentials, true);

        $igAccountId = $credentials['instagram_business_account_id'];
        $accessToken = $credentials['access_token'];

        // Instagram requires a two-step process: create container, then publish

        // Step 1: Create media container
        $containerData = [
            'caption' => $data['content'],
            'access_token' => $accessToken,
        ];

        if (!empty($data['media'])) {
            $media = $data['media'][0];

            if ($media['type'] === 'image') {
                $containerData['image_url'] = $this->getMediaUrl($media['path']);
            } elseif ($media['type'] === 'video') {
                $containerData['media_type'] = 'VIDEO';
                $containerData['video_url'] = $this->getMediaUrl($media['path']);
            }
        }

        $containerResponse = $this->client->post("{$igAccountId}/media", [
            'form_params' => $containerData,
        ]);

        $container = json_decode($containerResponse->getBody(), true);
        $creationId = $container['id'];

        // Wait for container to be ready (for videos especially)
        sleep(2);

        // Step 2: Publish the container
        $publishResponse = $this->client->post("{$igAccountId}/media_publish", [
            'form_params' => [
                'creation_id' => $creationId,
                'access_token' => $accessToken,
            ],
        ]);

        $result = json_decode($publishResponse->getBody(), true);

        // Update account last used
        $account->update(['last_used_at' => now()]);

        return $result;
    }

    protected function getMediaUrl(string $path): string
    {
        // Instagram requires publicly accessible URLs
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // For local files, you'd need to upload to a CDN or temporary public URL
        // This is a simplified version - in production, use S3, Cloudinary, etc.
        throw new \Exception('Instagram requires publicly accessible image URLs. Please provide a URL or upload to a CDN first.');
    }

    public function getAuthorizationUrl(string $redirectUri): string
    {
        $params = http_build_query([
            'client_id' => $this->config['app_id'],
            'redirect_uri' => $redirectUri,
            'scope' => 'instagram_basic,instagram_content_publish,pages_show_list',
            'response_type' => 'code',
        ]);

        return "https://www.facebook.com/{$this->apiVersion}/dialog/oauth?{$params}";
    }

    public function handleCallback(string $code, string $redirectUri): array
    {
        // Same as Facebook for getting access token
        $response = $this->client->get('oauth/access_token', [
            'query' => [
                'client_id' => $this->config['app_id'],
                'client_secret' => $this->config['app_secret'],
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        $accessToken = $data['access_token'];

        // Get Instagram Business Account
        $pagesResponse = $this->client->get('me/accounts', [
            'query' => [
                'access_token' => $accessToken,
                'fields' => 'instagram_business_account,name',
            ],
        ]);

        $pages = json_decode($pagesResponse->getBody(), true)['data'] ?? [];

        return [
            'access_token' => $accessToken,
            'pages' => $pages,
        ];
    }

    public function refreshToken(array $credentials): array
    {
        $response = $this->client->get('oauth/access_token', [
            'query' => [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->config['app_id'],
                'client_secret' => $this->config['app_secret'],
                'fb_exchange_token' => $credentials['access_token'],
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    public function verifyCredentials(array $credentials): bool
    {
        try {
            $igAccountId = $credentials['instagram_business_account_id'];
            $response = $this->client->get($igAccountId, [
                'query' => [
                    'fields' => 'id,username',
                    'access_token' => $credentials['access_token'],
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
}
