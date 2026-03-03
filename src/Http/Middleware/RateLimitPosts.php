<?php

namespace SocialSync\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RateLimitPosts
{
    public function handle(Request $request, Closure $next)
    {
        $platforms = (array) $request->input('platforms', []);

        foreach ($platforms as $platform) {
            if (!$this->checkRateLimit((string) $platform)) {
                return back()->with('error', sprintf(
                    'Rate limit exceeded for %s. Please try again later.',
                    $platform
                ));
            }
        }

        return $next($request);
    }

    protected function checkRateLimit(string $platform): bool
    {
        $limits = config('larapost.rate_limits.' . $platform, [
            'posts_per_hour' => 30,
            'posts_per_day' => 200,
        ]);

        $hourKey = 'social_sync_rate_limit:' . $platform . ':hour:' . now()->format('YmdH');
        $dayKey = 'social_sync_rate_limit:' . $platform . ':day:' . now()->format('Ymd');

        $hourlyCount = (int) Cache::get($hourKey, 0);
        $dailyCount = (int) Cache::get($dayKey, 0);

        if ($hourlyCount >= (int) $limits['posts_per_hour'] || $dailyCount >= (int) $limits['posts_per_day']) {
            return false;
        }

        $this->incrementWithTtl($hourKey, now()->addHour());
        $this->incrementWithTtl($dayKey, now()->addDay());

        return true;
    }

    public static function getRateLimitStatus(string $platform): array
    {
        $limits = config('larapost.rate_limits.' . $platform, [
            'posts_per_hour' => 30,
            'posts_per_day' => 200,
        ]);

        $hourKey = 'social_sync_rate_limit:' . $platform . ':hour:' . now()->format('YmdH');
        $dayKey = 'social_sync_rate_limit:' . $platform . ':day:' . now()->format('Ymd');

        $hourlyUsed = (int) Cache::get($hourKey, 0);
        $dailyUsed = (int) Cache::get($dayKey, 0);

        return [
            'hourly' => [
                'used' => $hourlyUsed,
                'limit' => (int) $limits['posts_per_hour'],
                'remaining' => max(0, (int) $limits['posts_per_hour'] - $hourlyUsed),
            ],
            'daily' => [
                'used' => $dailyUsed,
                'limit' => (int) $limits['posts_per_day'],
                'remaining' => max(0, (int) $limits['posts_per_day'] - $dailyUsed),
            ],
        ];
    }

    protected function incrementWithTtl(string $key, \DateTimeInterface $ttl): void
    {
        Cache::add($key, 0, $ttl);
        Cache::increment($key);
    }
}
