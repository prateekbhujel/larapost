<?php

use Composer\InstalledVersions;

if (!function_exists('social_sync_version')) {
    function social_sync_version(): string
    {
        try {
            return InstalledVersions::getPrettyVersion('prateekbhujel/larapost') ?: 'dev-main';
        } catch (\Throwable) {
            return 'dev-main';
        }
    }
}

if (!function_exists('social_sync_platform_icon')) {
    function social_sync_platform_icon(string $platform): string
    {
        return [
            'facebook' => 'fab fa-facebook',
            'twitter' => 'fab fa-x-twitter',
            'linkedin' => 'fab fa-linkedin',
            'tiktok' => 'fab fa-tiktok',
        ][$platform] ?? 'fas fa-share-nodes';
    }
}

if (!function_exists('social_sync_platform_color')) {
    function social_sync_platform_color(string $platform): string
    {
        return [
            'facebook' => 'bg-blue-600',
            'twitter' => 'bg-slate-950',
            'linkedin' => 'bg-blue-700',
            'tiktok' => 'bg-slate-950',
        ][$platform] ?? 'bg-gray-600';
    }
}

if (!function_exists('social_sync_status_badge')) {
    function social_sync_status_badge(string $status): string
    {
        return [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'processing' => 'bg-sky-100 text-sky-800',
            'published' => 'bg-green-100 text-green-800',
            'failed' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
        ][$status] ?? 'bg-gray-100 text-gray-800';
    }
}

if (!function_exists('social_sync_format_error')) {
    function social_sync_format_error(string $error): string
    {
        $error = preg_replace('/\s*\(.*?\)/', '', $error) ?: $error;

        foreach ([
            'ECONNREFUSED' => 'Unable to connect to the social platform',
            'Invalid OAuth' => 'Authentication failed. Please reconnect your account',
            'Rate limit' => 'Posting limit reached. Please try again later',
            'Token expired' => 'Your access token has expired. Please reconnect',
        ] as $pattern => $message) {
            if (stripos($error, $pattern) !== false) {
                return $message;
            }
        }

        return $error;
    }
}

if (!function_exists('social_sync_can_retry')) {
    function social_sync_can_retry($post): bool
    {
        $maxRetries = (int) config('larapost.retry.max_attempts', 3);

        return $post->status === 'failed' && $post->retry_count < $maxRetries;
    }
}

if (!function_exists('social_sync_humanize_platform')) {
    function social_sync_humanize_platform(string $platform): string
    {
        return [
            'facebook' => 'Facebook Pages',
            'twitter' => 'Twitter / X',
            'linkedin' => 'LinkedIn',
            'tiktok' => 'TikTok',
        ][$platform] ?? ucfirst($platform);
    }
}
