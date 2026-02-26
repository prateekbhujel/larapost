<?php

namespace SocialSync\Drivers;

use GuzzleHttp\Client;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Models\SocialAccount;

class TwitterDriver extends AbstractDriver
{
    protected string $apiVersion;

    public function __construct(array $config, ?Client $client = null)
    {
        $this->apiVersion = (string) ($config['api_version'] ?? '2');

        parent::__construct($config, $client ?? new Client([
            'base_uri' => sprintf('https://api.twitter.com/%s/', $this->apiVersion),
            'timeout' => 30,
        ]));
    }

    public function publish(SocialAccount $account, array $payload): array
    {
        $credentials = $this->credentials($account);
        $accessToken = $this->credentialValue($credentials, 'access_token');

        $body = [
            'text' => $payload['content'] ?? '',
        ];

        $firstMedia = $payload['media'][0] ?? null;

        if ($firstMedia && !empty($firstMedia['path']) && ctype_digit((string) $firstMedia['path'])) {
            $body['media'] = [
                'media_ids' => [(string) $firstMedia['path']],
            ];
        }

        return $this->requestJson('POST', 'tweets', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
        ]);
    }

    public function getAuthorizationUrl(string $redirectUri): string
    {
        $clientId = $this->configValue('client_id');
        $state = bin2hex(random_bytes(16));
        $codeVerifier = $this->codeVerifier();

        if (function_exists('session')) {
            session([
                'twitter_oauth_state' => $state,
                'twitter_code_verifier' => $codeVerifier,
            ]);
        }

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'tweet.read tweet.write users.read offline.access',
            'state' => $state,
            'code_challenge' => $this->codeChallenge($codeVerifier),
            'code_challenge_method' => 'S256',
        ]);

        return 'https://twitter.com/i/oauth2/authorize?' . $params;
    }

    public function handleCallback(string $code, string $redirectUri): array
    {
        $codeVerifier = function_exists('session') ? session('twitter_code_verifier') : null;

        if (!$codeVerifier) {
            throw new SocialSyncException('Missing Twitter PKCE code verifier in session. Start OAuth again.');
        }

        $client = new Client(['timeout' => 30]);

        $tokenData = $this->requestToken($client, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'code_verifier' => $codeVerifier,
            'client_id' => $this->configValue('client_id'),
        ]);

        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            throw new SocialSyncException('Twitter did not return an access token.');
        }

        $user = $this->requestJson('GET', 'users/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'query' => [
                'user.fields' => 'id,name,username',
            ],
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'expires_in' => $tokenData['expires_in'] ?? null,
            'user_id' => $user['data']['id'] ?? null,
            'user' => $user['data'] ?? [],
        ];
    }

    public function refreshToken(array $credentials): array
    {
        $refreshToken = $this->credentialValue($credentials, 'refresh_token');
        $client = new Client(['timeout' => 30]);

        return $this->requestToken($client, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->configValue('client_id'),
        ]);
    }

    public function verifyCredentials(array $credentials): bool
    {
        try {
            $this->requestJson('GET', 'users/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentialValue($credentials, 'access_token'),
                ],
            ]);

            return true;
        } catch (SocialSyncException) {
            return false;
        }
    }

    protected function requestToken(Client $client, array $params): array
    {
        $response = $client->post('https://api.twitter.com/2/oauth2/token', [
            'form_params' => $params,
        ]);

        $contents = (string) $response->getBody();
        $decoded = json_decode($contents, true);

        if (!is_array($decoded)) {
            throw new SocialSyncException('Twitter returned an invalid token response.');
        }

        if (isset($decoded['error'])) {
            throw new SocialSyncException('Twitter OAuth error: ' . json_encode($decoded));
        }

        return $decoded;
    }

    protected function codeVerifier(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(64)), '+/', '-_'), '=');
    }

    protected function codeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }
}
