<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Platform
    |--------------------------------------------------------------------------
    |
    | This option defines the default social media platform that will be used
    | when no platform is explicitly specified.
    |
    */

    'default_platform' => env('SOCIAL_SYNC_DEFAULT_PLATFORM', 'facebook'),

    /*
    |--------------------------------------------------------------------------
    | Platform Configurations
    |--------------------------------------------------------------------------
    |
    | Here you can configure each social media platform's API credentials.
    | Make sure to add these to your .env file for security.
    |
    */

    'platforms' => [

        'facebook' => [
            'app_id' => env('FACEBOOK_APP_ID'),
            'app_secret' => env('FACEBOOK_APP_SECRET'),
            'api_version' => env('FACEBOOK_API_VERSION', 'v18.0'),
        ],

        'instagram' => [
            'app_id' => env('INSTAGRAM_APP_ID'),
            'app_secret' => env('INSTAGRAM_APP_SECRET'),
            'api_version' => env('INSTAGRAM_API_VERSION', 'v18.0'),
        ],

        'twitter' => [
            'api_key' => env('TWITTER_API_KEY'),
            'api_secret' => env('TWITTER_API_SECRET'),
            'bearer_token' => env('TWITTER_BEARER_TOKEN'),
            'api_version' => env('TWITTER_API_VERSION', '2'),
        ],

        'linkedin' => [
            'client_id' => env('LINKEDIN_CLIENT_ID'),
            'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
            'api_version' => env('LINKEDIN_API_VERSION', 'v2'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how posts should be queued and processed.
    |
    */

    'queue' => [
        'enabled' => env('SOCIAL_SYNC_QUEUE_ENABLED', true),
        'connection' => env('SOCIAL_SYNC_QUEUE_CONNECTION', 'database'),
        'queue_name' => env('SOCIAL_SYNC_QUEUE_NAME', 'social-posts'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how failed posts should be retried.
    |
    */

    'retry' => [
        'max_attempts' => env('SOCIAL_SYNC_MAX_RETRY_ATTEMPTS', 3),
        'backoff_minutes' => [1, 5, 15], // Wait time between retries
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limits for each platform to avoid hitting API limits.
    |
    */

    'rate_limits' => [
        'facebook' => [
            'posts_per_hour' => 30,
            'posts_per_day' => 200,
        ],
        'instagram' => [
            'posts_per_hour' => 25,
            'posts_per_day' => 100,
        ],
        'twitter' => [
            'posts_per_hour' => 50,
            'posts_per_day' => 300,
        ],
        'linkedin' => [
            'posts_per_hour' => 20,
            'posts_per_day' => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Configuration
    |--------------------------------------------------------------------------
    |
    | Configure media upload settings.
    |
    */

    'media' => [
        'temp_upload_path' => storage_path('app/social-sync/temp'),
        'max_image_size' => 5 * 1024 * 1024, // 5MB
        'max_video_size' => 100 * 1024 * 1024, // 100MB
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif'],
        'allowed_video_types' => ['mp4', 'mov', 'avi'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhooks for receiving platform updates.
    |
    */

    'webhooks' => [
        'enabled' => env('SOCIAL_SYNC_WEBHOOKS_ENABLED', true),
        'verify_signature' => env('SOCIAL_SYNC_VERIFY_WEBHOOK_SIGNATURE', true),
        'route_prefix' => 'social-sync/webhooks',
    ],

];
