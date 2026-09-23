<?php

namespace SocialSync\Drivers;

use GuzzleHttp\Client;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Models\SocialAccount;

class TikTokDriver extends AbstractDriver
{
    public function __construct(array $config, ?Client $client = null)
    {
        parent::__construct($config, $client ?? new Client([
            'base_uri' => 'https://open.tiktokapis.com/',
            'timeout' => 30,
        ]));
    }

    public function publish(SocialAccount $account, array $payload): array
    {
        $credentials = $this->credentials($account);
        $accessToken = (string) $this->credentialValue($credentials, 'access_token');
        $media = array_values((array) ($payload['media'] ?? []));

        if ($media === []) {
            throw new SocialSyncException('TikTok publishing requires at least one image or video.');
        }

        $creator = $this->requestJson('POST', 'v2/post/publish/creator_info/query/', [
            'headers' => $this->headers($accessToken),
            'json' => (object) [],
        ]);

        $creatorInfo = (array) ($creator['data'] ?? []);
        $privacy = $this->privacyLevel($payload, $creatorInfo);

        $types = array_values(array_unique(array_map(
            static fn (array $item): string => strtolower((string) ($item['type'] ?? 'image')),
            $media
        )));

        if ($types === ['video']) {
            if (count($media) !== 1) {
                throw new SocialSyncException('TikTok video publishing accepts one video per post.');
            }

            return $this->publishVideo($accessToken, $payload, $media[0], $privacy);
        }

        if ($types === ['image']) {
            return $this->publishPhotos($accessToken, $payload, $media, $privacy);
        }

        throw new SocialSyncException('TikTok posts cannot mix image and video media in one publish request.');
    }

    public function getAuthorizationUrl(string $redirectUri): string
    {
        $state = bin2hex(random_bytes(16));

        $this->rememberOauthContext('tiktok', $state, [
            'state' => $state,
        ]);

        $params = http_build_query([
            'client_key' => $this->configValue('client_key'),
            'scope' => 'user.info.basic,video.publish',
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);

        return 'https://www.tiktok.com/v2/auth/authorize/?' . $params;
    }

    public function handleCallback(string $code, string $redirectUri): array
    {
        $this->assertOauthState();

        $token = $this->requestJson('POST', 'v2/oauth/token/', [
            'form_params' => [
                'client_key' => $this->configValue('client_key'),
                'client_secret' => $this->configValue('client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ],
        ]);

        $accessToken = $token['access_token'] ?? null;

        if (!is_string($accessToken) || $accessToken === '') {
            throw new SocialSyncException('TikTok did not return an access token.');
        }

        $userResponse = $this->requestJson('GET', 'v2/user/info/', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'query' => [
                'fields' => 'open_id,display_name,avatar_url',
            ],
        ]);

        return array_replace($token, [
            'user' => (array) ($userResponse['data']['user'] ?? []),
        ]);
    }

    public function refreshToken(array $credentials): array
    {
        return $this->requestJson('POST', 'v2/oauth/token/', [
            'form_params' => [
                'client_key' => $this->configValue('client_key'),
                'client_secret' => $this->configValue('client_secret'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->credentialValue($credentials, 'refresh_token'),
            ],
        ]);
    }

    public function verifyCredentials(array $credentials): bool
    {
        try {
            $this->requestJson('GET', 'v2/user/info/', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->credentialValue($credentials, 'access_token'),
                ],
                'query' => [
                    'fields' => 'open_id',
                ],
            ]);

            return true;
        } catch (SocialSyncException) {
            return false;
        }
    }

    protected function publishVideo(string $accessToken, array $payload, array $media, string $privacy): array
    {
        $url = $this->httpsMediaUrl((string) ($media['path'] ?? ''), 'video');
        $metadata = (array) ($payload['metadata']['tiktok'] ?? []);

        $response = $this->requestJson('POST', 'v2/post/publish/video/init/', [
            'headers' => $this->headers($accessToken),
            'json' => [
                'post_info' => [
                    'title' => (string) ($payload['content'] ?? ''),
                    'privacy_level' => $privacy,
                    'disable_duet' => (bool) ($metadata['disable_duet'] ?? false),
                    'disable_comment' => (bool) ($metadata['disable_comment'] ?? false),
                    'disable_stitch' => (bool) ($metadata['disable_stitch'] ?? false),
                    'brand_content_toggle' => (bool) ($metadata['brand_content'] ?? false),
                    'brand_organic_toggle' => (bool) ($metadata['brand_organic'] ?? false),
                    'is_aigc' => (bool) ($metadata['is_aigc'] ?? false),
                ],
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'video_url' => $url,
                ],
            ],
        ]);

        return $this->publishResponse($response);
    }

    protected function publishPhotos(string $accessToken, array $payload, array $media, string $privacy): array
    {
        if (count($media) > 35) {
            throw new SocialSyncException('TikTok photo posts support at most 35 images.');
        }

        $metadata = (array) ($payload['metadata']['tiktok'] ?? []);
        $urls = array_map(
            fn (array $item): string => $this->httpsMediaUrl((string) ($item['path'] ?? ''), 'image'),
            $media
        );

        $response = $this->requestJson('POST', 'v2/post/publish/content/init/', [
            'headers' => $this->headers($accessToken),
            'json' => [
                'post_info' => [
                    'title' => (string) ($metadata['title'] ?? ''),
                    'description' => (string) ($payload['content'] ?? ''),
                    'privacy_level' => $privacy,
                    'disable_comment' => (bool) ($metadata['disable_comment'] ?? false),
                    'auto_add_music' => (bool) ($metadata['auto_add_music'] ?? true),
                    'brand_content_toggle' => (bool) ($metadata['brand_content'] ?? false),
                    'brand_organic_toggle' => (bool) ($metadata['brand_organic'] ?? false),
                ],
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'photo_cover_index' => (int) ($metadata['photo_cover_index'] ?? 0),
                    'photo_images' => $urls,
                ],
                'post_mode' => 'DIRECT_POST',
                'media_type' => 'PHOTO',
                'is_aigc' => (bool) ($metadata['is_aigc'] ?? false),
            ],
        ]);

        return $this->publishResponse($response);
    }

    protected function publishResponse(array $response): array
    {
        $publishId = $response['data']['publish_id'] ?? null;

        if (!is_string($publishId) || $publishId === '') {
            throw new SocialSyncException('TikTok accepted the request without returning a publish ID.');
        }

        return [
            'id' => $publishId,
            'publish_id' => $publishId,
            'status' => 'processing',
            'response' => $response,
        ];
    }

    protected function privacyLevel(array $payload, array $creatorInfo): string
    {
        $requested = strtoupper((string) (
            $payload['metadata']['tiktok']['privacy_level']
            ?? $this->config['default_privacy_level']
            ?? 'SELF_ONLY'
        ));

        $options = array_map(
            static fn ($value): string => strtoupper((string) $value),
            (array) ($creatorInfo['privacy_level_options'] ?? [])
        );

        if ($options !== [] && !in_array($requested, $options, true)) {
            throw new SocialSyncException(sprintf(
                'TikTok privacy level "%s" is not available for this account. Available values: %s.',
                $requested,
                implode(', ', $options)
            ));
        }

        return $requested;
    }

    protected function httpsMediaUrl(string $url, string $type): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false || !str_starts_with(strtolower($url), 'https://')) {
            throw new SocialSyncException(sprintf(
                'TikTok %s publishing currently requires a public HTTPS URL from a domain or URL prefix verified with TikTok.',
                $type
            ));
        }

        return $url;
    }

    protected function assertOauthState(): void
    {
        $returnedState = $this->requestInput('state');
        $context = $this->pullOauthContext('tiktok', $returnedState);
        $expectedState = (string) ($context['state'] ?? '');

        if ($returnedState === '' || $expectedState === '' || !hash_equals($expectedState, $returnedState)) {
            throw new SocialSyncException('TikTok returned an invalid OAuth state. Start OAuth again.');
        }
    }

    protected function headers(string $accessToken): array
    {
        return [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json; charset=UTF-8',
        ];
    }
}
