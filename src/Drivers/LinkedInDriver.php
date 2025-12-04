<?php

namespace SocialSync\Drivers;

use GuzzleHttp\Client;
use SocialSync\Contracts\SocialDriverInterface;
use SocialSync\Models\SocialAccount;

class LinkedInDriver implements SocialDriverInterface
{
    protected $config;
    protected $client;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'base_uri' => 'https://api.linkedin.com/v2/',
            'timeout' => 30,
        ]);
    }

    public function publish(int $accountId, array $data): array
    {
        $account = SocialAccount::findOrFail($accountId);
        $credentials = json_decode($account->credentials, true);

        $personUrn = $credentials['person_urn'];
        $accessToken = $credentials['access_token'];

        $postData = [
            'author' => $personUrn,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $data['content'],
                    ],
                    'shareMediaCategory' => 'NONE',
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        // Handle media
        if (!empty($data['media'])) {
            $media = $data['media'][0];

            if ($media['type'] === 'image') {
                $imageUrn = $this->uploadImage($media['path'], $personUrn, $accessToken);

                $postData['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'IMAGE';
                $postData['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [
                    [
                        'status' => 'READY',
                        'media' => $imageUrn,
                    ],
                ];
            }
        }

        $response = $this->client->post('ugcPosts', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0',
            ],
            'json' => $postData,
        ]);

        $result = json_decode($response->getBody(), true);

        // Update account last used
        $account->update(['last_used_at' => now()]);

        return $result;
    }

    protected function uploadImage(string $imagePath, string $personUrn, string $accessToken)
    {
        // Step 1: Register upload
        $registerResponse = $this->client->post('assets?action=registerUpload', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'registerUploadRequest' => [
                    'recipes' => ['urn:li:digitalmediaRecipe:feedshare-image'],
                    'owner' => $personUrn,
                    'serviceRelationships' => [
                        [
                            'relationshipType' => 'OWNER',
                            'identifier' => 'urn:li:userGeneratedContent',
                        ],
                    ],
                ],
            ],
        ]);

        $uploadData = json_decode($registerResponse->getBody(), true);
        $uploadUrl = $uploadData['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];
        $asset = $uploadData['value']['asset'];

        // Step 2: Upload image
        $imageData = file_get_contents($imagePath);

        $uploadClient = new Client();
        $uploadClient->put($uploadUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'body' => $imageData,
        ]);

        return $asset;
    }

    public function getAuthorizationUrl(string $redirectUri): string
    {
        $state = bin2hex(random_bytes(16));
        session(['linkedin_oauth_state' => $state]);

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => 'w_member_social r_liteprofile',
        ]);

        return "https://www.linkedin.com/oauth/v2/authorization?{$params}";
    }

    public function handleCallback(string $code, string $redirectUri): array
    {
        $response = $this->client->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
            ],
        ]);

        $tokens = json_decode($response->getBody(), true);
        $accessToken = $tokens['access_token'];

        // Get user profile
        $profileResponse = $this->client->get('me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        $profile = json_decode($profileResponse->getBody(), true);

        return [
            'access_token' => $accessToken,
            'person_urn' => 'urn:li:person:' . $profile['id'],
            'profile' => $profile,
        ];
    }

    public function refreshToken(array $credentials): array
    {
        // LinkedIn access tokens are long-lived (60 days) and don't support refresh
        // You would need to re-authenticate after expiry
        throw new \Exception('LinkedIn tokens must be manually refreshed through re-authentication');
    }

    public function verifyCredentials(array $credentials): bool
    {
        try {
            $response = $this->client->get('me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $credentials['access_token'],
                ],
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
}
