<?php

namespace SocialSync\Tests\Feature;

use Laravel\Mcp\Request;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Mcp\Tools\CreatePost;
use SocialSync\Models\SocialAccount;
use SocialSync\SocialMediaManager;
use SocialSync\Tests\TestCase;

class McpTikTokSafetyTest extends TestCase
{
    public function test_mcp_refuses_tiktok_direct_post_mode(): void
    {
        config()->set('larapost.enabled_platforms', ['tiktok']);
        config()->set('larapost.platforms.tiktok.publish_mode', 'direct');

        $account = SocialAccount::query()->create([
            'platform' => 'tiktok',
            'account_name' => 'Creator',
            'account_id_on_platform' => 'creator-1',
            'credentials' => ['access_token' => 'token'],
            'is_active' => true,
        ]);

        $tool = new CreatePost(app(SocialMediaManager::class));

        $this->expectException(SocialSyncException::class);
        $this->expectExceptionMessage('TikTok Direct Post is intentionally unavailable through LaraPost MCP');

        $tool->handle(new Request([
            'content' => 'Draft',
            'account_ids' => [$account->id],
            'media_urls' => ['https://media.example.com/video.mp4'],
            'media_type' => 'video',
        ]));
    }
}
