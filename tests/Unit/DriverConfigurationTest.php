<?php

namespace SocialSync\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SocialSync\Drivers\FacebookDriver;
use SocialSync\Drivers\LinkedInDriver;
use SocialSync\Drivers\TikTokDriver;
use SocialSync\Drivers\TwitterDriver;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Models\SocialAccount;

class DriverConfigurationTest extends TestCase
{
    public function test_facebook_driver_normalizes_numeric_api_versions(): void
    {
        $driver = new FacebookDriver([
            'app_id' => 'app-id',
            'app_secret' => 'app-secret',
            'api_version' => '25',
        ]);

        $url = $driver->getAuthorizationUrl('https://example.com/callback');

        $this->assertStringContainsString('https://www.facebook.com/v25.0/dialog/oauth?', $url);
        $this->assertStringContainsString('state=', $url);
    }

    public function test_facebook_driver_prefers_page_access_token_for_page_posts(): void
    {
        $driver = new InspectableFacebookDriver([
            'app_id' => 'app-id',
            'app_secret' => 'app-secret',
        ]);

        $token = $driver->exposedPageAccessToken([
            'access_token' => 'user-token',
            'page_id' => 'page-2',
            'pages' => [
                ['id' => 'page-1', 'access_token' => 'page-token-1'],
                ['id' => 'page-2', 'access_token' => 'page-token-2'],
            ],
        ]);

        $this->assertSame('page-token-2', $token);
    }

    public function test_twitter_driver_uses_basic_auth_for_confidential_clients(): void
    {
        $driver = new InspectableTwitterDriver([
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'api_version' => '2',
        ]);

        $options = $driver->exposedTokenRequestOptions([
            'grant_type' => 'authorization_code',
            'client_id' => 'client-id',
            'code' => 'abc123',
        ]);

        $this->assertSame(['client-id', 'client-secret'], $options['auth']);
        $this->assertArrayNotHasKey('client_id', $options['form_params']);
    }

    public function test_twitter_driver_keeps_client_id_for_public_clients(): void
    {
        $driver = new InspectableTwitterDriver([
            'client_id' => 'client-id',
            'api_version' => '2',
        ]);

        $options = $driver->exposedTokenRequestOptions([
            'grant_type' => 'authorization_code',
            'client_id' => 'client-id',
            'code' => 'abc123',
        ]);

        $this->assertArrayNotHasKey('auth', $options);
        $this->assertSame('client-id', $options['form_params']['client_id']);
    }

    public function test_twitter_driver_can_generate_authorization_url_without_laravel_context(): void
    {
        $driver = new TwitterDriver([
            'client_id' => 'client-id',
            'api_version' => '2',
        ]);

        $url = $driver->getAuthorizationUrl('https://example.com/callback');

        $this->assertStringContainsString('https://twitter.com/i/oauth2/authorize?', $url);
        $this->assertStringContainsString('client_id=client-id', $url);
        $this->assertStringContainsString('code_challenge=', $url);
        $this->assertStringContainsString('state=', $url);
    }

    public function test_twitter_driver_can_publish_text_with_xquik_backend(): void
    {
        $mock = new MockHandler([
            new Response(202, ['Content-Type' => 'application/json'], json_encode(['writeActionId' => 'write-123'])),
        ]);
        $driver = new TwitterDriver([
            'backend' => 'xquik',
            'xquik_api_key' => 'key-123',
            'xquik_account' => '@fallback',
            'xquik_api_base_url' => 'https://xquik.com/api/v1',
        ], new Client(['handler' => HandlerStack::create($mock)]));
        $account = new SocialAccount();
        $account->setRawAttributes([
            'credentials' => json_encode(['account' => '@account']),
        ], true);

        $response = $driver->publish($account, [
            'content' => 'Launch update',
            'media' => [],
        ]);
        $request = $mock->getLastRequest();

        $this->assertSame('xquik-write-action:write-123', $response['id']);
        $this->assertSame('accepted', $response['status']);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://xquik.com/api/v1/x/tweets', (string) $request->getUri());
        $this->assertSame('key-123', $request->getHeaderLine('x-api-key'));
        $this->assertSame([
            'account' => '@account',
            'text' => 'Launch update',
        ], json_decode((string) $request->getBody(), true));
    }

    public function test_twitter_driver_rejects_media_for_xquik_backend(): void
    {
        $driver = new TwitterDriver([
            'backend' => 'xquik',
            'xquik_api_key' => 'key-123',
            'xquik_account' => '@account',
        ], new Client(['handler' => HandlerStack::create(new MockHandler())]));
        $account = new SocialAccount();
        $account->setRawAttributes(['credentials' => json_encode([])], true);

        $this->expectException(SocialSyncException::class);
        $this->expectExceptionMessage('text posts only');

        $driver->publish($account, [
            'content' => 'Launch update',
            'media' => [['path' => 'media-1']],
        ]);
    }

    public function test_linkedin_driver_can_generate_authorization_url_without_laravel_context(): void
    {
        $driver = new LinkedInDriver([
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
        ]);

        $url = $driver->getAuthorizationUrl('https://example.com/callback');

        $this->assertStringContainsString('https://www.linkedin.com/oauth/v2/authorization?', $url);
        $this->assertStringContainsString('client_id=client-id', $url);
        $this->assertStringContainsString('state=', $url);
    }

    public function test_tiktok_upload_mode_requests_upload_scope(): void
    {
        $driver = new TikTokDriver([
            'client_key' => 'client-key',
            'client_secret' => 'client-secret',
            'publish_mode' => 'upload',
        ]);

        $url = $driver->getAuthorizationUrl('https://example.com/callback');

        $this->assertStringContainsString('video.upload', urldecode($url));
        $this->assertStringNotContainsString('video.publish', urldecode($url));
        $this->assertStringContainsString('state=', $url);
    }

    public function test_tiktok_upload_mode_sends_video_to_creator_inbox(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => ['publish_id' => 'v_inbox_url~123'],
                'error' => ['code' => 'ok', 'message' => '', 'log_id' => 'upload-log'],
            ])),
        ]);

        $driver = new TikTokDriver([
            'client_key' => 'client-key',
            'client_secret' => 'client-secret',
            'publish_mode' => 'upload',
        ], new Client([
            'base_uri' => 'https://open.tiktokapis.com/',
            'handler' => HandlerStack::create($mock),
        ]));

        $account = new SocialAccount();
        $account->setRawAttributes([
            'credentials' => json_encode(['access_token' => 'access-token']),
        ], true);

        $response = $driver->publish($account, [
            'content' => 'TikTok video',
            'media' => [[
                'type' => 'video',
                'path' => 'https://cdn.example.com/video.mp4',
            ]],
            'metadata' => [],
        ]);

        $this->assertSame('v_inbox_url~123', $response['publish_id']);
        $this->assertSame('uploaded', $response['status']);

        $request = $mock->getLastRequest();
        $payload = json_decode((string) $request->getBody(), true);

        $this->assertSame('/v2/post/publish/inbox/video/init/', $request->getUri()->getPath());
        $this->assertSame('PULL_FROM_URL', $payload['source_info']['source']);
        $this->assertSame('https://cdn.example.com/video.mp4', $payload['source_info']['video_url']);
    }

    public function test_tiktok_upload_mode_sends_photos_for_in_app_completion(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => ['publish_id' => 'p_pub_url~123'],
                'error' => ['code' => 'ok', 'message' => '', 'log_id' => 'photo-log'],
            ])),
        ]);

        $driver = new TikTokDriver([
            'client_key' => 'client-key',
            'client_secret' => 'client-secret',
            'publish_mode' => 'upload',
        ], new Client([
            'base_uri' => 'https://open.tiktokapis.com/',
            'handler' => HandlerStack::create($mock),
        ]));

        $account = new SocialAccount();
        $account->setRawAttributes([
            'credentials' => json_encode(['access_token' => 'access-token']),
        ], true);

        $driver->publish($account, [
            'content' => 'Photo draft',
            'media' => [[
                'type' => 'image',
                'path' => 'https://cdn.example.com/photo.webp',
            ]],
            'metadata' => ['tiktok' => ['title' => 'Photo draft']],
        ]);

        $request = $mock->getLastRequest();
        $payload = json_decode((string) $request->getBody(), true);

        $this->assertSame('/v2/post/publish/content/init/', $request->getUri()->getPath());
        $this->assertSame('MEDIA_UPLOAD', $payload['post_mode']);
        $this->assertSame('PHOTO', $payload['media_type']);
        $this->assertArrayNotHasKey('privacy_level', $payload['post_info']);
    }

    public function test_tiktok_direct_mode_requires_explicit_creator_consent(): void
    {
        $driver = new TikTokDriver([
            'client_key' => 'client-key',
            'client_secret' => 'client-secret',
            'publish_mode' => 'direct',
        ], new Client(['handler' => HandlerStack::create(new MockHandler())]));

        $account = new SocialAccount();
        $account->setRawAttributes([
            'credentials' => json_encode(['access_token' => 'access-token']),
        ], true);

        $this->expectException(SocialSyncException::class);
        $this->expectExceptionMessage('explicit creator consent');

        $driver->publish($account, [
            'content' => 'Direct post',
            'media' => [[
                'type' => 'video',
                'path' => 'https://cdn.example.com/video.mp4',
            ]],
            'metadata' => [
                'tiktok' => [
                    'privacy_level' => 'SELF_ONLY',
                    'disable_comment' => true,
                    'disable_duet' => true,
                    'disable_stitch' => true,
                ],
            ],
        ]);
    }

    public function test_tiktok_direct_mode_honors_creator_disabled_interactions(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => [
                    'privacy_level_options' => ['SELF_ONLY'],
                    'comment_disabled' => true,
                    'duet_disabled' => true,
                    'stitch_disabled' => true,
                ],
                'error' => ['code' => 'ok', 'message' => '', 'log_id' => 'creator-log'],
            ])),
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'data' => ['publish_id' => 'v_pub_url~123'],
                'error' => ['code' => 'ok', 'message' => '', 'log_id' => 'publish-log'],
            ])),
        ]);

        $driver = new TikTokDriver([
            'client_key' => 'client-key',
            'client_secret' => 'client-secret',
            'publish_mode' => 'direct',
        ], new Client([
            'base_uri' => 'https://open.tiktokapis.com/',
            'handler' => HandlerStack::create($mock),
        ]));

        $account = new SocialAccount();
        $account->setRawAttributes([
            'credentials' => json_encode(['access_token' => 'access-token']),
        ], true);

        $driver->publish($account, [
            'content' => 'Direct post',
            'media' => [[
                'type' => 'video',
                'path' => 'https://cdn.example.com/video.mp4',
            ]],
            'metadata' => [
                'tiktok' => [
                    'consent' => true,
                    'privacy_level' => 'SELF_ONLY',
                    'disable_comment' => false,
                    'disable_duet' => false,
                    'disable_stitch' => false,
                ],
            ],
        ]);

        $request = $mock->getLastRequest();
        $payload = json_decode((string) $request->getBody(), true);

        $this->assertSame('/v2/post/publish/video/init/', $request->getUri()->getPath());
        $this->assertTrue($payload['post_info']['disable_comment']);
        $this->assertTrue($payload['post_info']['disable_duet']);
        $this->assertTrue($payload['post_info']['disable_stitch']);
    }

    public function test_tiktok_driver_rejects_non_https_media(): void
    {
        $driver = new TikTokDriver([
            'client_key' => 'client-key',
            'client_secret' => 'client-secret',
            'publish_mode' => 'upload',
        ], new Client(['handler' => HandlerStack::create(new MockHandler())]));

        $account = new SocialAccount();
        $account->setRawAttributes([
            'credentials' => json_encode(['access_token' => 'access-token']),
        ], true);

        $this->expectException(SocialSyncException::class);
        $this->expectExceptionMessage('public HTTPS URL');

        $driver->publish($account, [
            'content' => 'Nope',
            'media' => [[
                'type' => 'video',
                'path' => '/tmp/video.mp4',
            ]],
            'metadata' => [],
        ]);
    }

}

class InspectableTwitterDriver extends TwitterDriver
{
    public function exposedTokenRequestOptions(array $params): array
    {
        return $this->tokenRequestOptions($params);
    }
}

class InspectableFacebookDriver extends FacebookDriver
{
    public function exposedPageAccessToken(array $credentials): string
    {
        return $this->pageAccessToken($credentials);
    }
}
