<?php

return [
    'default_platform' => env('LARAPOST_DEFAULT_PLATFORM', 'facebook'),

    'drivers' => [
        'facebook' => \SocialSync\Drivers\FacebookDriver::class,
        'twitter' => \SocialSync\Drivers\TwitterDriver::class,
        'linkedin' => \SocialSync\Drivers\LinkedInDriver::class,
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
    ],

    'queue' => [
        // Scheduled publishing remains synchronous unless explicitly enabled.
        // SocialMedia::post()->queue() always dispatches through Laravel's queue.
        'enabled' => env('LARAPOST_QUEUE_ENABLED', false),
        'connection' => env('LARAPOST_QUEUE_CONNECTION', env('QUEUE_CONNECTION')),
        'queue_name' => env('LARAPOST_QUEUE_NAME', 'larapost'),
    ],

    'scheduler' => [
        // Registers larapost:run-scheduled with Laravel's scheduler. Your app still
        // needs schedule:run / schedule:work to be running in production.
        'enabled' => env('LARAPOST_SCHEDULER_ENABLED', true),
        'limit' => (int) env('LARAPOST_SCHEDULER_LIMIT', 50),
        'without_overlapping' => true,
        'overlap_expiration_minutes' => 10,
    ],

    'retry' => [
        'max_attempts' => (int) env('LARAPOST_MAX_RETRY_ATTEMPTS', 3),
        'backoff_minutes' => [1, 5, 15],
    ],

    'rate_limits' => [
        'facebook' => [
            'posts_per_hour' => 30,
            'posts_per_day' => 200,
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
        'temp_upload_path' => storage_path('app/larapost/temp'),
        'max_image_size' => 5 * 1024 * 1024,
        'max_video_size' => 100 * 1024 * 1024,
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_video_types' => ['mp4', 'mov', 'avi', 'webm'],
    ],

    'routes' => [
        'enabled' => env('LARAPOST_ROUTES_ENABLED', true),
        'prefix' => env('LARAPOST_ROUTE_PREFIX', 'larapost'),

        // Kept for compatibility and used as the base middleware for callbacks.
        'middleware' => ['web'],

        // Operator actions are protected by authentication by default. Applications
        // with a different authorization model can override this published config.
        'operator_middleware' => ['web', 'auth'],
        'callback_middleware' => ['web'],
    ],

    'ui' => [
        'enabled' => env('LARAPOST_UI_ENABLED', true),
        'title' => env('LARAPOST_UI_TITLE', 'LaraPost Dashboard'),
    ],
];
