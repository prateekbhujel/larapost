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

        $types = array_values(array_unique(array_map(
            static fn (array $item): string => strtolower((string) ($item['type'] ?? 'image')),
            $media
        )));

        if ($types === ['video'] && count($media) !== 1) {
            throw new SocialSyncException('TikTok video publishing accepts one video per post.');
        }

        if (!in_array($types, [['video'], ['image']], true)) {
            throw new SocialSyncException('TikTok posts cannot mix image and video media in one publish request.');
        }

        if ($this->publishMode() === 'upload') {
            return $types === ['video']
                ? $this->uploadVideoDraft($accessToken, $media[0])
                : $this->uploadPhotoDraft($accessToken, $payload, $media);
        }

        $metadata = $this->directPostMetadata($payload, $types[0]);
        $creatorInfo = $this->creatorInfo($account);
        $privacy = $this->privacyLevel($metadata, $creatorInfo);

        return $types === ['video']
            ? $this->directPostVideo($accessToken, $payload, $media[0], $privacy, $metadata, $creatorInfo)
            : $this->directPostPhotos($accessToken, $payload, $media, $privacy, $metadata, $creatorInfo);
    }

    public function getAuthorizationUrl(string $redirectUri): string
    {
        $state = bin2hex(random_bytes(16));
        $scope = $this->publishMode() === 'direct'
            ? 'user.info.basic,video.publish'
            : 'user.info.basic,video.upload';

        $this->rememberOauthContext('tiktok', $state, [
            'state' => $state,
        ]);

        $params = http_build_query([
            'client_key' => $this->configValue('client_key'),
            'scope' => $scope,
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

    /**
     * Current creator capabilities for building a TikTok Direct Post UI.
     *
     * Direct Post integrations should render these values to the creator before
     * collecting privacy, interaction settings, and consent.
     */
    public function creatorInfo(SocialAccount $account): array
    {
        $credentials = $this->credentials($account);
        $accessToken = (string) $this->credentialValue($credentials, 'access_token');

        $response = $this->requestJson('POST', 'v2/post/publish/creator_info/query/', [
            'headers' => $this->headers($accessToken),
            'json' => (object) [],
        ]);

        return (array) ($response['data'] ?? []);
    }

    protected function uploadVideoDraft(string $accessToken, array $media): array
    {
        $url = $this->httpsMediaUrl((string) ($media['path'] ?? ''), 'video');

        $response = $this->requestJson('POST', 'v2/post/publish/inbox/video/init/', [
            'headers' => $this->headers($accessToken),
            'json' => [
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'video_url' => $url,
                ],
            ],
        ]);

        return $this->publishResponse($response, 'uploaded');
    }

    protected function uploadPhotoDraft(string $accessToken, array $payload, array $media): array
    {
        if (count($media) > 35) {
            throw new SocialSyncException('TikTok photo uploads support at most 35 images.');
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
                ],
                'source_info' => [
                    'source' => 'PULL_FROM_URL',
                    'photo_cover_index' => (int) ($metadata['photo_cover_index'] ?? 0),
                    'photo_images' => $urls,
                ],
                'post_mode' => 'MEDIA_UPLOAD',
                'media_type' => 'PHOTO',
            ],
        ]);

        return $this->publishResponse($response, 'uploaded');
    }

    protected function directPostVideo(
        string $accessToken,
        array $payload,
        array $media,
        string $privacy,
        array $metadata,
        array $creatorInfo
    ): array {
        $url = $this->httpsMediaUrl((string) ($media['path'] ?? ''), 'video');

        $response = $this->requestJson('POST', 'v2/post/publish/video/init/', [
            'headers' => $this->headers($accessToken),
            'json' => [
                'post_info' => [
                    'title' => (string) ($payload['content'] ?? ''),
                    'privacy_level' => $privacy,
                    'disable_duet' => (bool) ($creatorInfo['duet_disabled'] ?? false)
                        || (bool) $metadata['disable_duet'],
                    'disable_comment' => (bool) ($creatorInfo['comment_disabled'] ?? false)
                        || (bool) $metadata['disable_comment'],
                    'disable_stitch' => (bool) ($creatorInfo['stitch_disabled'] ?? false)
                        || (bool) $metadata['disable_stitch'],
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

        return $this->publishResponse($response, 'processing');
    }

    protected function directPostPhotos(
        string $accessToken,
        array $payload,
        array $media,
        string $privacy,
        array $metadata,
        array $creatorInfo
    ): array {
        if (count($media) > 35) {
            throw new SocialSyncException('TikTok photo posts support at most 35 images.');
        }

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
                    'disable_comment' => (bool) ($creatorInfo['comment_disabled'] ?? false)
                        || (bool) $metadata['disable_comment'],
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

        return $this->publishResponse($response, 'processing');
    }

    protected function directPostMetadata(array $payload, string $mediaType): array
    {
        $metadata = (array) ($payload['metadata']['tiktok'] ?? []);

        if (($metadata['consent'] ?? false) !== true) {
            throw new SocialSyncException(
                'TikTok Direct Post requires explicit creator consent. Set metadata.tiktok.consent=true only after the creator approves the post.'
            );
        }

        if (!isset($metadata['privacy_level']) || trim((string) $metadata['privacy_level']) === '') {
            throw new SocialSyncException(
                'TikTok Direct Post requires an explicit privacy_level selected by the creator.'
            );
        }

        $requiredInteractionFields = $mediaType === 'video'
            ? ['disable_comment', 'disable_duet', 'disable_stitch']
            : ['disable_comment'];

        foreach ($requiredInteractionFields as $field) {
            if (!array_key_exists($field, $metadata)) {
                throw new SocialSyncException(sprintf(
                    'TikTok Direct Post requires an explicit metadata.tiktok.%s value from the creator UI.',
                    $field
                ));
            }
        }

        return $metadata;
    }

    protected function privacyLevel(array $metadata, array $creatorInfo): string
    {
        $requested = strtoupper(trim((string) $metadata['privacy_level']));
        $options = array_map(
            static fn ($value): string => strtoupper((string) $value),
            (array) ($creatorInfo['privacy_level_options'] ?? [])
        );

        if ($options === []) {
            throw new SocialSyncException(
                'TikTok did not return privacy options for this creator. Direct Post cannot continue safely.'
            );
        }

        if (!in_array($requested, $options, true)) {
            throw new SocialSyncException(sprintf(
                'TikTok privacy level "%s" is not available for this account. Available values: %s.',
                $requested,
                implode(', ', $options)
            ));
        }

        return $requested;
    }

    protected function publishResponse(array $response, string $status): array
    {
        $publishId = $response['data']['publish_id'] ?? null;

        if (!is_string($publishId) || $publishId === '') {
            throw new SocialSyncException('TikTok accepted the request without returning a publish ID.');
        }

        return [
            'id' => $publishId,
            'publish_id' => $publishId,
            'status' => $status,
            'response' => $response,
        ];
    }

    protected function publishMode(): string
    {
        $mode = strtolower(trim((string) ($this->config['publish_mode'] ?? 'upload')));

        if (!in_array($mode, ['upload', 'direct'], true)) {
            throw new SocialSyncException(
                'Unsupported TikTok publish mode. Use "upload" or "direct".'
            );
        }

        return $mode;
    }

    protected function httpsMediaUrl(string $url, string $type): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false || !str_starts_with(strtolower($url), 'https://')) {
            throw new SocialSyncException(sprintf(
                'TikTok %s publishing requires a public HTTPS URL from a domain or URL prefix verified with TikTok.',
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
