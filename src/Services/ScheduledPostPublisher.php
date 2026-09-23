<?php

namespace SocialSync\Services;

use SocialSync\Events\PostFailed;
use SocialSync\Events\PostPublished;
use SocialSync\Models\ScheduledPost;
use SocialSync\SocialMediaManager;

class ScheduledPostPublisher
{
    public function __construct(private readonly SocialMediaManager $manager)
    {
    }

    /**
     * @return array{status: string, post_id: int, error?: string, retry_in_minutes?: int}
     */
    public function publish(int $postId): array
    {
        $post = $this->claim($postId);

        if ($post === null) {
            return [
                'status' => 'skipped',
                'post_id' => $postId,
            ];
        }

        $account = $post->account;

        if (!$account || !$account->is_active) {
            $error = 'Account missing or inactive.';

            $post->forceFill([
                'status' => ScheduledPost::STATUS_FAILED,
                'error_message' => $error,
                'retry_count' => $post->retry_count + 1,
            ])->save();

            event(new PostFailed($post, $error));

            return [
                'status' => 'failed',
                'post_id' => $post->id,
                'error' => $error,
            ];
        }

        $result = $this->manager->publish($account->id, [
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

            return [
                'status' => 'published',
                'post_id' => $post->id,
            ];
        }

        return $this->applyFailureStrategy(
            $post,
            (string) ($result['error'] ?? 'Publishing failed.')
        );
    }

    protected function claim(int $postId): ?ScheduledPost
    {
        $claimed = ScheduledPost::query()
            ->whereKey($postId)
            ->pending()
            ->scheduledBefore(now())
            ->update([
                'status' => ScheduledPost::STATUS_PROCESSING,
            ]);

        if ($claimed === 0) {
            return null;
        }

        return ScheduledPost::query()->with('account')->find($postId);
    }

    /**
     * @return array{status: string, post_id: int, error: string, retry_in_minutes?: int}
     */
    protected function applyFailureStrategy(ScheduledPost $post, string $error): array
    {
        $newRetryCount = $post->retry_count + 1;
        $maxAttempts = max(1, (int) $post->max_attempts);

        if ($newRetryCount < $maxAttempts) {
            $backoff = array_values((array) config('larapost.retry.backoff_minutes', [1, 5, 15]));
            $index = min($newRetryCount - 1, max(0, count($backoff) - 1));
            $minutes = max(1, (int) ($backoff[$index] ?? 1));

            $post->forceFill([
                'status' => ScheduledPost::STATUS_PENDING,
                'retry_count' => $newRetryCount,
                'scheduled_for' => now()->addMinutes($minutes),
                'error_message' => $error,
            ])->save();

            return [
                'status' => 'retrying',
                'post_id' => $post->id,
                'error' => $error,
                'retry_in_minutes' => $minutes,
            ];
        }

        $post->forceFill([
            'status' => ScheduledPost::STATUS_FAILED,
            'retry_count' => $newRetryCount,
            'error_message' => $error,
        ])->save();

        event(new PostFailed($post, $error));

        return [
            'status' => 'failed',
            'post_id' => $post->id,
            'error' => $error,
        ];
    }
}
