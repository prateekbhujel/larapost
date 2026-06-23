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
        if ($this->backend() === 'xquik') {
            return $this->publishWithXquik($account, $payload);
        }

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

    protected function publishWithXquik(SocialAccount $account, array $payload): array
    {
        $credentials = $this->credentials($account);

        if (($payload['media'] ?? []) !== []) {
            throw new SocialSyncException('Xquik backend currently supports Twitter / X text posts only.');
        }

        $content = trim((string) ($payload['content'] ?? ''));

        if ($content === '') {
            throw new SocialSyncException('Post content is required for Xquik publishing.');
        }

        $response = $this->requestJson('POST', $this->xquikEndpoint('/x/tweets'), [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->xquikConfigValue($credentials, 'api_key', 'xquik_api_key'),
            ],
            'json' => [
                'account' => $this->xquikConfigValue($credentials, 'account', 'xquik_account'),
                'text' => $content,
            ],
        ]);

        $tweetId = $response['tweetId'] ?? $response['id'] ?? ($response['data']['id'] ?? null);

        if ($tweetId !== null && $tweetId !== '') {
            return array_merge($response, [
                'id' => (string) $tweetId,
                'status' => 'published',
            ]);
        }

        $writeActionId = $response['writeActionId'] ?? null;

        if ($writeActionId !== null && $writeActionId !== '') {
            return array_merge($response, [
                'id' => 'xquik-write-action:' . (string) $writeActionId,
                'status' => 'accepted',
                'write_action_id' => (string) $writeActionId,
            ]);
        }

        return $response;
    }

    public function getAuthorizationUrl(string $redirectUri): string
    {
        if ($this->backend() === 'xquik') {
            throw new SocialSyncException('Xquik backend uses API key configuration and does not start Twitter OAuth.');
        }

        $clientId = $this->configValue('client_id');
        $state = bin2hex(random_bytes(16));
        $codeVerifier = $this->codeVerifier();

        $this->rememberOauthContext('twitter', $state, [
            'state' => $state,
            'code_verifier' => $codeVerifier,
        ]);

        $this->storeSessionValues([
            'twitter_oauth_state' => $state,
            'twitter_code_verifier' => $codeVerifier,
        ]);

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
        if ($this->backend() === 'xquik') {
            throw new SocialSyncException('Xquik backend uses API key configuration and does not handle Twitter OAuth callbacks.');
        }

        $returnedState = $this->requestInput('state');
        $oauthContext = $this->pullOauthContext('twitter', $returnedState);
        $expectedState = (string) ($oauthContext['state'] ?? $this->sessionValue('twitter_oauth_state', ''));

        if ($returnedState !== '') {
            if ($expectedState === '') {
                throw new SocialSyncException('Missing Twitter OAuth state context. Start OAuth again.');
            }

            if (!hash_equals($expectedState, $returnedState)) {
                throw new SocialSyncException('Twitter returned an invalid OAuth state. Start OAuth again.');
            }
        }

        $codeVerifier = $oauthContext['code_verifier'] ?? $this->sessionValue('twitter_code_verifier');

        if (!$codeVerifier) {
            throw new SocialSyncException('Missing Twitter PKCE code verifier in session. Start OAuth again.');
        }

        $client = new Client(['timeout' => 30]);
        $tokenParams = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'code_verifier' => $codeVerifier,
            'client_id' => $this->configValue('client_id'),
        ];

        $tokenData = $this->requestToken($client, $tokenParams);

        $this->forgetSessionValues(['twitter_code_verifier', 'twitter_oauth_state']);

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
        if ($this->backend() === 'xquik') {
            return $credentials;
        }

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
        if ($this->backend() === 'xquik') {
            return $this->hasXquikValue($credentials, 'api_key', 'xquik_api_key')
                && $this->hasXquikValue($credentials, 'account', 'xquik_account');
        }

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

    protected function backend(): string
    {
        $backend = strtolower(trim((string) ($this->config['backend'] ?? 'twitter')));

        if (!in_array($backend, ['twitter', 'xquik'], true)) {
            throw new SocialSyncException('Unsupported Twitter backend "' . $backend . '".');
        }

        return $backend;
    }

    protected function xquikEndpoint(string $path): string
    {
        return rtrim((string) ($this->config['xquik_api_base_url'] ?? 'https://xquik.com/api/v1'), '/')
            . '/'
            . ltrim($path, '/');
    }

    protected function xquikConfigValue(array $credentials, string $credentialKey, string $configKey): string
    {
        $value = $credentials[$credentialKey]
            ?? $credentials['xquik_' . $credentialKey]
            ?? $this->config[$configKey]
            ?? null;

        if ($value === null || $value === '') {
            throw new SocialSyncException(sprintf('Missing required Xquik value "%s".', $credentialKey));
        }

        return (string) $value;
    }

    protected function hasXquikValue(array $credentials, string $credentialKey, string $configKey): bool
    {
        $value = $credentials[$credentialKey]
            ?? $credentials['xquik_' . $credentialKey]
            ?? $this->config[$configKey]
            ?? null;

        return $value !== null && $value !== '';
    }

    protected function requestToken(Client $client, array $params): array
    {
        $response = $client->post('https://api.twitter.com/2/oauth2/token', $this->tokenRequestOptions($params));

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

    protected function tokenRequestOptions(array $params): array
    {
        $clientSecret = $this->config['client_secret'] ?? null;
        $options = [
            'form_params' => $params,
        ];

        if (is_string($clientSecret) && $clientSecret !== '') {
            unset($options['form_params']['client_id']);
            $options['auth'] = [
                $this->configValue('client_id'),
                $clientSecret,
            ];
        }

        return $options;
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
