<?php

namespace SocialSync\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use SocialSync\Models\ScheduledPost;

class RateLimitPosts
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $platforms = $request->input('platforms', []);

        foreach ($platforms as $platform) {
            if (!$this->checkRateLimit($platform)) {
                return back()->with('error',
                    "Rate limit exceeded for {$platform}. Please try again later."
                );
            }
        }

        return $next($request);
    }

    /**
     * Check if rate limit is exceeded for platform
     *
     * @param string $platform
     * @return bool
     */
    protected function checkRateLimit($platform)
    {
        $limits = config("social-sync.rate_limits.{$platform}", [
            'posts_per_hour' => 30,
            'posts_per_day' => 200,
        ]);

        // Check hourly limit
        $hourKey = "social_sync_rate_limit:{$platform}:hour:" . now()->format('YmdH');
        $hourlyCount = Cache::get($hourKey, 0);

        if ($hourlyCount >= $limits['posts_per_hour']) {
            return false;
        }

        // Check daily limit
        $dayKey = "social_sync_rate_limit:{$platform}:day:" . now()->format('Ymd');
        $dailyCount = Cache::get($dayKey, 0);

        if ($dailyCount >= $limits['posts_per_day']) {
            return false;
        }

        // Increment counters
        Cache::increment($hourKey, 1);
        Cache::put($hourKey, Cache::get($hourKey), now()->addHour());

        Cache::increment($dayKey, 1);
        Cache::put($dayKey, Cache::get($dayKey), now()->addDay());

        return true;
    }

    /**
     * Get current rate limit status
     *
     * @param string $platform
     * @return array
     */
    public static function getRateLimitStatus($platform)
    {
        $limits = config("social-sync.rate_limits.{$platform}", [
            'posts_per_hour' => 30,
            'posts_per_day' => 200,
        ]);

        $hourKey = "social_sync_rate_limit:{$platform}:hour:" . now()->format('YmdH');
        $dayKey = "social_sync_rate_limit:{$platform}:day:" . now()->format('Ymd');

        return [
            'hourly' => [
                'used' => Cache::get($hourKey, 0),
                'limit' => $limits['posts_per_hour'],
                'remaining' => max(0, $limits['posts_per_hour'] - Cache::get($hourKey, 0)),
            ],
            'daily' => [
                'used' => Cache::get($dayKey, 0),
                'limit' => $limits['posts_per_day'],
                'remaining' => max(0, $limits['posts_per_day'] - Cache::get($dayKey, 0)),
            ],
        ];
    }
}
