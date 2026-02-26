<?php

namespace SocialSync\Console\Commands;

use Illuminate\Console\Command;
use SocialSync\Events\PostFailed;
use SocialSync\Events\PostPublished;
use SocialSync\Facades\SocialMedia;
use SocialSync\Models\ScheduledPost;

class RunScheduledPostsCommand extends Command
{
    protected $signature = 'social-sync:run-scheduled {--limit=50 : Maximum posts to process in one run}';

    protected $description = 'Publish pending scheduled posts that are due.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $posts = ScheduledPost::query()
            ->with('account')
            ->pending()
            ->scheduledBefore(now())
            ->orderBy('scheduled_for')
            ->limit($limit)
            ->get();

        if ($posts->isEmpty()) {
            $this->line('No scheduled posts are due right now.');

            return self::SUCCESS;
        }

        foreach ($posts as $post) {
            $account = $post->account;

            if (!$account || !$account->is_active) {
                $post->forceFill([
                    'status' => ScheduledPost::STATUS_FAILED,
                    'error_message' => 'Account missing or inactive.',
                    'retry_count' => $post->retry_count + 1,
                ])->save();

                event(new PostFailed($post, 'Account missing or inactive.'));

                $this->error(sprintf('Failed scheduled post #%d: account missing or inactive.', $post->id));

                continue;
            }

            $result = SocialMedia::publish($account->id, [
                'content' => $post->content,
                'media' => $post->media ?? [],
                'metadata' => $post->metadata ?? [],
            ]);

            if (($result['success'] ?? false) === true) {
                $post->forceFill([
                    'status' => ScheduledPost::STATUS_PUBLISHED,
                    'published_at' => now(),
                    'published_response' => $result['response'] ?? null,
                    'error_message' => null,
                ])->save();

                event(new PostPublished($post, $result));

                $this->info(sprintf('Published scheduled post #%d.', $post->id));

                continue;
            }

            $this->applyFailureStrategy($post, (string) ($result['error'] ?? 'Publishing failed.'));
        }

        return self::SUCCESS;
    }

    protected function applyFailureStrategy(ScheduledPost $post, string $error): void
    {
        $newRetryCount = $post->retry_count + 1;
        $maxAttempts = max(1, (int) $post->max_attempts);

        if ($newRetryCount < $maxAttempts) {
            $backoff = (array) config('social-sync.retry.backoff_minutes', [1, 5, 15]);
            $index = min($newRetryCount - 1, count($backoff) - 1);
            $minutes = (int) ($backoff[$index] ?? 1);

            $post->forceFill([
                'status' => ScheduledPost::STATUS_PENDING,
                'retry_count' => $newRetryCount,
                'scheduled_for' => now()->addMinutes($minutes),
                'error_message' => $error,
            ])->save();

            $this->warn(sprintf(
                'Post #%d failed and was rescheduled in %d minute(s): %s',
                $post->id,
                $minutes,
                $error
            ));

            return;
        }

        $post->forceFill([
            'status' => ScheduledPost::STATUS_FAILED,
            'retry_count' => $newRetryCount,
            'error_message' => $error,
        ])->save();

        event(new PostFailed($post, $error));

        $this->error(sprintf('Post #%d permanently failed: %s', $post->id, $error));
    }
}
