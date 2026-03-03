<?php

namespace SocialSync\Console\Commands;

use Illuminate\Console\Command;
use SocialSync\Facades\SocialMedia;
use SocialSync\Models\SocialAccount;

class TestPostCommand extends Command
{
    protected $signature = 'larapost:test
                            {--platform= : Limit to one platform}
                            {--content= : Post content}
                            {--schedule= : Schedule time (e.g. "+30 minutes")}';

    protected $description = 'Send a test post using connected accounts.';

    public function handle(): int
    {
        $query = SocialAccount::query()->active();

        if ($platform = $this->option('platform')) {
            $query->where('platform', $platform);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->error('No active accounts found. Use `php artisan larapost:add-account {platform}` first.');

            return self::FAILURE;
        }

        $platforms = $accounts->pluck('platform')->unique()->values()->all();
        $content = (string) ($this->option('content') ?: 'Test post from Laravel Social Sync.');

        $builder = SocialMedia::post()->content($content)->platforms($platforms);

        if ($schedule = $this->option('schedule')) {
            $builder->scheduleFor($schedule);
        }

        $results = $builder->publish();

        foreach ($results as $result) {
            if (($result['success'] ?? false) === true) {
                $this->info(sprintf('[OK] %s account #%d', $result['platform'] ?? 'unknown', $result['account_id'] ?? 0));
            } else {
                $this->error(sprintf('[FAILED] account #%d: %s', $result['account_id'] ?? 0, $result['error'] ?? 'unknown error'));
            }
        }

        return self::SUCCESS;
    }
}
