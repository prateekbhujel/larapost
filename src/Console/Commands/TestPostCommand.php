<?php

namespace SocialSync\Console\Commands;

use Illuminate\Console\Command;
use SocialSync\Facades\SocialMedia;
use SocialSync\Models\SocialAccount;

class TestPostCommand extends Command
{
    protected $signature = 'social-sync:test
                            {--platform= : Test specific platform}
                            {--content= : Custom post content}';

    protected $description = 'Test posting to social media platforms';

    public function handle()
    {
        $this->info('Social Sync - Test Post');
        $this->newLine();

        // Get active accounts
        $query = SocialAccount::active();

        if ($platform = $this->option('platform')) {
            $query->where('platform', $platform);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->error('No active accounts found.');
            $this->info('Run: php artisan social-sync:add-account {platform}');
            return 1;
        }

        // Show available accounts
        $this->info('Available accounts:');
        $accounts->each(function ($account, $index) {
            $this->line("  [{$index}] {$account->platform} - {$account->account_name}");
        });
        $this->newLine();

        // Get platforms to post to
        $platforms = $accounts->pluck('platform')->unique()->toArray();

        if (!$this->option('platform') && count($platforms) > 1) {
            $selected = $this->choice(
                'Select platforms to post to (comma-separated)',
                array_merge(['all'], $platforms),
                'all'
            );

            if ($selected !== 'all') {
                $platforms = explode(',', $selected);
            }
        }

        // Get content
        $content = $this->option('content') ?: $this->ask(
            'Enter post content',
            'Test post from Social Sync! 🚀 #SocialSync #Laravel'
        );

        // Confirm
        if (!$this->confirm('Post to ' . implode(', ', $platforms) . '?', true)) {
            $this->warn('Cancelled.');
            return 0;
        }

        // Post
        $this->info('Posting...');
        $this->newLine();

        try {
            $results = SocialMedia::post()
                ->content($content)
                ->platforms($platforms)
                ->publish();

            // Display results
            $this->info('Results:');
            $this->newLine();

            foreach ($results as $result) {
                if ($result['success']) {
                    $this->line("  ✓ {$result['platform']} (Account #{$result['account_id']}): Success");
                    if (isset($result['post_id'])) {
                        $this->line("    Post ID: {$result['post_id']}");
                    }
                } else {
                    $this->error("  ✗ {$result['platform']} (Account #{$result['account_id']}): Failed");
                    $this->line("    Error: {$result['error']}");
                }
            }

            $this->newLine();
            $successCount = collect($results)->where('success', true)->count();
            $this->info("Posted to {$successCount} of " . count($results) . " accounts.");

        } catch (\Exception $e) {
            $this->error('Failed to post: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
