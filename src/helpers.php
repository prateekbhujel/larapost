<?php

if (!function_exists('social_sync_version')) {
    /**
     * Get the Social Sync package version
     *
     * @return string
     */
    function social_sync_version()
    {
        return '1.1.0';
    }
}

if (!function_exists('social_sync_platform_icon')) {
    /**
     * Get the icon class for a social platform
     *
     * @param string $platform
     * @return string
     */
    function social_sync_platform_icon($platform)
    {
        $icons = [
            'facebook' => 'fab fa-facebook',
            'instagram' => 'fab fa-instagram',
            'twitter' => 'fab fa-twitter',
            'linkedin' => 'fab fa-linkedin',
        ];

        return $icons[$platform] ?? 'fas fa-share-nodes';
    }
}

if (!function_exists('social_sync_platform_color')) {
    /**
     * Get the brand color for a social platform
     *
     * @param string $platform
     * @return string
     */
    function social_sync_platform_color($platform)
    {
        $colors = [
            'facebook' => 'bg-blue-600',
            'instagram' => 'bg-gradient-to-br from-purple-500 to-pink-500',
            'twitter' => 'bg-blue-400',
            'linkedin' => 'bg-blue-700',
        ];

        return $colors[$platform] ?? 'bg-gray-600';
    }
}

if (!function_exists('social_sync_status_badge')) {
    /**
     * Get the badge class for post status
     *
     * @param string $status
     * @return string
     */
    function social_sync_status_badge($status)
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'published' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
        ];

        return $badges[$status] ?? 'bg-gray-100 text-gray-800';
    }
}

if (!function_exists('social_sync_format_error')) {
    /**
     * Format error message for display
     *
     * @param string $error
     * @return string
     */
    function social_sync_format_error($error)
    {
        // Remove technical details
        $error = preg_replace('/\s*\(.*?\)/', '', $error);

        // Common error mappings
        $errorMap = [
            'ECONNREFUSED' => 'Unable to connect to the social platform',
            'Invalid OAuth' => 'Authentication failed. Please reconnect your account',
            'Rate limit' => 'Posting limit reached. Please try again later',
            'Token expired' => 'Your access token has expired. Please reconnect',
        ];

        foreach ($errorMap as $pattern => $message) {
            if (stripos($error, $pattern) !== false) {
                return $message;
            }
        }

        return $error;
    }
}

if (!function_exists('social_sync_can_retry')) {
    /**
     * Check if a failed post can be retried
     *
     * @param \SocialSync\Models\ScheduledPost $post
     * @return bool
     */
    function social_sync_can_retry($post)
    {
        $maxRetries = config('social-sync.retry.max_attempts', 3);
        return $post->status === 'failed' && $post->retry_count < $maxRetries;
    }
}

if (!function_exists('social_sync_humanize_platform')) {
    /**
     * Get human-readable platform name
     *
     * @param string $platform
     * @return string
     */
    function social_sync_humanize_platform($platform)
    {
        $names = [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'twitter' => 'Twitter / X',
            'linkedin' => 'LinkedIn',
        ];

        return $names[$platform] ?? ucfirst($platform);
    }
}
