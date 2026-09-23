<?php

namespace SocialSync\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use SocialSync\SocialMediaManager;

#[Description('Describe LaraPost publishing capabilities and current supported platforms so an AI client can plan compatible content.')]
#[IsReadOnly]
class GetCapabilities extends Tool
{
    public function __construct(private readonly SocialMediaManager $manager)
    {
    }

    public function handle(Request $request): Response
    {
        $catalog = [
            'facebook' => [
                'destination' => 'Facebook Pages',
                'text' => true,
                'images' => true,
                'video' => true,
                'notes' => 'Page publishing only. Personal profile publishing is not supported.',
            ],
            'twitter' => [
                'destination' => 'Twitter / X account',
                'text' => true,
                'images' => false,
                'video' => false,
                'notes' => 'Native backend currently publishes text. Xquik is an optional text backend.',
            ],
            'linkedin' => [
                'destination' => 'LinkedIn member profile',
                'text' => true,
                'images' => true,
                'video' => false,
                'notes' => 'Member profiles are supported. Image publishing expects a readable local file path.',
            ],
            'tiktok' => [
                'destination' => 'TikTok creator account',
                'text' => false,
                'images' => true,
                'video' => true,
                'publish_mode' => (string) config('larapost.platforms.tiktok.publish_mode', 'upload'),
                'notes' => config('larapost.platforms.tiktok.publish_mode', 'upload') === 'direct'
                    ? 'Direct Post is configured for application code with a compliant creator UI. LaraPost MCP will not perform TikTok Direct Post.'
                    : 'Upload mode sends media to TikTok for the creator to review and complete from the TikTok inbox.',
            ],
        ];

        $supported = $this->manager->supportedPlatforms();

        return Response::json([
            'platforms' => array_intersect_key($catalog, array_flip($supported)),
            'scheduling' => true,
            'queues' => true,
            'multi_account' => true,
            'custom_drivers' => true,
            'mcp' => true,
        ]);
    }
}
