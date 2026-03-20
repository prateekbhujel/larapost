<?php

namespace SocialSync\Drivers;

use GuzzleHttp\Client;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Models\SocialAccount;

class LinkedInDriver extends AbstractDriver
{
    public function __construct(array $config, ?Client $client = null)
    {
        parent::__construct($config, $client ?? new Client([
            'base_uri' => 'https://api.linkedin.com/v2/',
            'timeout' => 30,
        ]));
    }

    public function publish(SocialAccount $account, array $payload): array
    {
        $credentials = $this->credentials($account);
        $personUrn = $this->credentialValue($credentials, 'person_urn');
        $accessToken = $this->credentialValue($credentials, 'access_token');

        $postData = [
            'author' => $personUrn,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => $payload['content'] ?? '',
                    ],
                    'shareMediaCategory' => 'NONE',
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        if (!empty($payload['media'][0])) {
            $media = $payload['media'][0];
            $imageUrn = $this->registerImageUpload((string) ($media['path'] ?? ''), $personUrn, $accessToken);

            $postData['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'IMAGE';
            $postData['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [[
                'status' => 'READY',
                'media' => $imageUrn,
            ]];
        }

        return $this->requestJson('POST', 'ugcPosts', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0',
            ],
            'json' => $postData,
        ]);
    }

    public function getAuthorizationUrl(string $redirectUri): string
    {
        $state = bin2hex(random_bytes(16));

        $this->rememberOauthContext('linkedin', $state, [
            'state' => $state,
        ]);

        $this->storeSessionValues([
            'linkedin_oauth_state' => $state,
        ]);

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->configValue('client_id'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => 'w_member_social r_liteprofile',
        ]);

        return 'https://www.linkedin.com/oauth/v2/authorization?' . $params;
    }

    public function handleCallback(string $code, string $redirectUri): array
    {
        $returnedState = $this->requestInput('state');
        $oauthContext = $this->pullOauthContext('linkedin', $returnedState);
        $expectedState = (string) ($oauthContext['state'] ?? $this->sessionValue('linkedin_oauth_state', ''));

        if ($returnedState !== '') {
            if ($expectedState === '') {
                throw new SocialSyncException('Missing LinkedIn OAuth state context. Start OAuth again.');
            }

            if (!hash_equals($expectedState, $returnedState)) {
                throw new SocialSyncException('LinkedIn returned an invalid OAuth state. Start OAuth again.');
            }
        }

        $this->forgetSessionValues(['linkedin_oauth_state']);

        $tokenData = $this->requestJson('POST', 'https://www.linkedin.com/oauth/v2/accessToken', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
                'client_id' => $this->configValue('client_id'),
                'client_secret' => $this->configValue('client_secret'),
            ],
        ]);

        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            throw new SocialSyncException('LinkedIn did not return an access token.');
        }

        $profile = $this->requestJson('GET', 'me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        return [
            'access_token' => $accessToken,
            'person_urn' => 'urn:li:person:' . ($profile['id'] ?? ''),
            'profile' => $profile,
        ];
    }

    public function refreshToken(array $credentials): array
    {
        throw new SocialSyncException('LinkedIn does not provide token refresh for this OAuth flow. Reconnect the account.');
    }

    public function verifyCredentials(array $credentials): bool
    {
        try {
            $this->requestJson('GET', 'me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentialValue($credentials, 'access_token'),
                ],
            ]);

            return true;
        } catch (SocialSyncException) {
            return false;
        }
    }

    protected function registerImageUpload(string $imagePath, string $personUrn, string $accessToken): string
    {
        if ($imagePath === '' || !is_readable($imagePath)) {
            throw new SocialSyncException('LinkedIn image upload expects a readable local file path.');
        }

        $registerData = $this->requestJson('POST', 'assets?action=registerUpload', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'registerUploadRequest' => [
                    'recipes' => ['urn:li:digitalmediaRecipe:feedshare-image'],
                    'owner' => $personUrn,
                    'serviceRelationships' => [[
                        'relationshipType' => 'OWNER',
                        'identifier' => 'urn:li:userGeneratedContent',
                    ]],
                ],
            ],
        ]);

        $uploadUrl = $registerData['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl']
            ?? null;
        $asset = $registerData['value']['asset'] ?? null;

        if (!$uploadUrl || !$asset) {
            throw new SocialSyncException('LinkedIn upload registration failed.');
        }

        $uploadClient = new Client(['timeout' => 30]);
        $uploadClient->put($uploadUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'body' => file_get_contents($imagePath),
        ]);

        return $asset;
    }
}
