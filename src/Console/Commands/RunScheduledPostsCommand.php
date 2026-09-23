<?php

namespace SocialSync\Console\Commands;

use Illuminate\Console\Command;
use SocialSync\Jobs\PublishScheduledPost;
use SocialSync\Models\ScheduledPost;
use SocialSync\Services\ScheduledPostPublisher;

class RunScheduledPostsCommand extends Command
{
    protected $signature = 'larapost:run-scheduled {--limit=50 : Maximum posts to process in one run}';

    protected $description = 'Publish or queue pending LaraPost posts that are due.';

    public function handle(ScheduledPostPublisher $publisher): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $duePostIds = ScheduledPost::query()
            ->pending()
            ->scheduledBefore(now())
            ->orderBy('scheduled_for')
            ->limit($limit)
            ->pluck('id');

        if ($duePostIds->isEmpty()) {
            $this->line('No scheduled posts are due right now.');

            return self::SUCCESS;
        }

        foreach ($duePostIds as $postId) {
            $postId = (int) $postId;

            if (config('larapost.queue.enabled', false)) {
                $this->dispatchPost($postId);
                $this->info(sprintf('Queued scheduled post #%d.', $postId));

                continue;
            }

            $result = $publisher->publish($postId);

            match ($result['status']) {
                'published' => $this->info(sprintf('Published scheduled post #%d.', $postId)),
                'retrying' => $this->warn(sprintf(
                    'Post #%d failed and was rescheduled in %d minute(s): %s',
                    $postId,
                    (int) ($result['retry_in_minutes'] ?? 1),
                    (string) ($result['error'] ?? 'Publishing failed.')
                )),
                'failed' => $this->error(sprintf(
                    'Failed scheduled post #%d: %s',
                    $postId,
                    (string) ($result['error'] ?? 'Publishing failed.')
                )),
                default => null,
            };
        }

        return self::SUCCESS;
    }

    protected function dispatchPost(int $postId): void
    {
        $dispatch = PublishScheduledPost::dispatch($postId);
        $connection = config('larapost.queue.connection');
        $queue = config('larapost.queue.queue_name', 'larapost');

        if (is_string($connection) && $connection !== '') {
            $dispatch->onConnection($connection);
        }

        if (is_string($queue) && $queue !== '') {
            $dispatch->onQueue($queue);
        }
    }
}
