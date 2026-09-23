<?php

$enabledPlatforms = array_values(array_filter(array_map(
    static fn ($platform): string => strtolower(trim($platform)),
    explode(',', (string) env('LARAPOST_PLATFORMS', 'facebook,twitter,linkedin,tiktok'))
)));

return [
    'default_platform' => env('LARAPOST_DEFAULT_PLATFORM', 'facebook'),
    'enabled_platforms' => $enabledPlatforms,

    'drivers' => [
        'facebook' => \SocialSync\Drivers\FacebookDriver::class,
        'twitter' => \SocialSync\Drivers\TwitterDriver::class,
        'linkedin' => \SocialSync\Drivers\LinkedInDriver::class,
        'tiktok' => \SocialSync\Drivers\TikTokDriver::class,
    ],

    'platforms' => [
        'facebook' => [
            'app_id' => env('FACEBOOK_APP_ID'),
            'app_secret' => env('FACEBOOK_APP_SECRET'),
            'api_version' => env('FACEBOOK_API_VERSION', 'v20.0'),
        ],
        'twitter' => [
            'client_id' => env('TWITTER_CLIENT_ID'),
            'client_secret' => env('TWITTER_CLIENT_SECRET'),
            'api_version' => env('TWITTER_API_VERSION', '2'),
            'backend' => env('TWITTER_BACKEND', 'twitter'),
            'xquik_api_key' => env('XQUIK_API_KEY'),
            'xquik_account' => env('XQUIK_ACCOUNT'),
            'xquik_api_base_url' => env('XQUIK_API_BASE_URL', 'https://xquik.com/api/v1'),
        ],
        'linkedin' => [
            'client_id' => env('LINKEDIN_CLIENT_ID'),
            'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        ],
        'tiktok' => [
            'client_key' => env('TIKTOK_CLIENT_KEY'),
            'client_secret' => env('TIKTOK_CLIENT_SECRET'),

            // "upload" sends media to the creator's TikTok inbox so they review
            // and finish the post in TikTok. "direct" requires a host application
            // to implement TikTok's creator-info, privacy, interaction, and consent UX.
            'publish_mode' => env('TIKTOK_PUBLISH_MODE', 'upload'),
        ],
    ],

    'queue' => [
        'enabled' => env('LARAPOST_QUEUE_ENABLED', false),
        'connection' => env('LARAPOST_QUEUE_CONNECTION', env('QUEUE_CONNECTION')),
        'queue_name' => env('LARAPOST_QUEUE_NAME', 'larapost'),
    ],

    'scheduler' => [
        'enabled' => env('LARAPOST_SCHEDULER_ENABLED', true),
        'limit' => (int) env('LARAPOST_SCHEDULER_LIMIT', 50),
        'without_overlapping' => true,
        'overlap_expiration_minutes' => 10,
    ],

    'retry' => [
        'max_attempts' => (int) env('LARAPOST_MAX_RETRY_ATTEMPTS', 3),
        'backoff_minutes' => [1, 5, 15],
    ],

    'media' => [
        'temp_upload_path' => storage_path('app/larapost/temp'),
        'max_image_size' => 5 * 1024 * 1024,
        'max_video_size' => 100 * 1024 * 1024,
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_video_types' => ['mp4', 'mov', 'avi', 'webm'],
    ],

    'routes' => [
        'enabled' => env('LARAPOST_ROUTES_ENABLED', true),
        'prefix' => env('LARAPOST_ROUTE_PREFIX', 'larapost'),
        'middleware' => ['web'],
        'operator_middleware' => ['web', 'auth'],
        'callback_middleware' => ['web'],
    ],

    'ui' => [
        'enabled' => env('LARAPOST_UI_ENABLED', true),
        'title' => env('LARAPOST_UI_TITLE', 'LaraPost Dashboard'),
    ],

    'mcp' => [
        'enabled' => env('LARAPOST_MCP_ENABLED', false),
        'path' => env('LARAPOST_MCP_PATH', '/larapost/mcp'),
        'token' => env('LARAPOST_MCP_TOKEN'),
        'business' => [
            'name' => env('LARAPOST_BUSINESS_NAME'),
            'website' => env('LARAPOST_BUSINESS_WEBSITE'),
            'description' => env('LARAPOST_BUSINESS_DESCRIPTION'),
            'audience' => env('LARAPOST_TARGET_AUDIENCE'),
            'voice' => env('LARAPOST_BRAND_VOICE'),
            'guidelines' => env('LARAPOST_CONTENT_GUIDELINES'),
        ],
    ],
];
