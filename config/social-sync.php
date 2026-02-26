<?php

return [
    'default_platform' => env('SOCIAL_SYNC_DEFAULT_PLATFORM', 'facebook'),

    'drivers' => [
        'facebook' => \SocialSync\Drivers\FacebookDriver::class,
        'instagram' => \SocialSync\Drivers\InstagramDriver::class,
        'twitter' => \SocialSync\Drivers\TwitterDriver::class,
        'linkedin' => \SocialSync\Drivers\LinkedInDriver::class,
    ],

    'platforms' => [
        'facebook' => [
            'app_id' => env('FACEBOOK_APP_ID'),
            'app_secret' => env('FACEBOOK_APP_SECRET'),
            'api_version' => env('FACEBOOK_API_VERSION', 'v20.0'),
        ],

        'instagram' => [
            'app_id' => env('INSTAGRAM_APP_ID', env('FACEBOOK_APP_ID')),
            'app_secret' => env('INSTAGRAM_APP_SECRET', env('FACEBOOK_APP_SECRET')),
            'api_version' => env('INSTAGRAM_API_VERSION', env('FACEBOOK_API_VERSION', 'v20.0')),
        ],

        'twitter' => [
            'client_id' => env('TWITTER_CLIENT_ID'),
            'client_secret' => env('TWITTER_CLIENT_SECRET'),
            'api_version' => env('TWITTER_API_VERSION', '2'),
        ],

        'linkedin' => [
            'client_id' => env('LINKEDIN_CLIENT_ID'),
            'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        ],
    ],

    'queue' => [
        'enabled' => env('SOCIAL_SYNC_QUEUE_ENABLED', true),
        'connection' => env('SOCIAL_SYNC_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
        'queue_name' => env('SOCIAL_SYNC_QUEUE_NAME', 'social-sync'),
    ],

    'retry' => [
        'max_attempts' => (int) env('SOCIAL_SYNC_MAX_RETRY_ATTEMPTS', 3),
        'backoff_minutes' => [1, 5, 15],
    ],

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

    'media' => [
        'temp_upload_path' => storage_path('app/social-sync/temp'),
        'max_image_size' => 5 * 1024 * 1024,
        'max_video_size' => 100 * 1024 * 1024,
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_video_types' => ['mp4', 'mov', 'avi', 'webm'],
    ],

    'routes' => [
        'enabled' => env('SOCIAL_SYNC_ROUTES_ENABLED', true),
        'prefix' => env('SOCIAL_SYNC_ROUTE_PREFIX', 'social-sync'),
        'middleware' => ['web'],
    ],
];
