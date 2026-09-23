<?php

namespace SocialSync\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use SocialSync\Jobs\PublishScheduledPost;
use SocialSync\Models\ScheduledPost;
use SocialSync\Models\SocialAccount;
use SocialSync\Tests\TestCase;

class QueuedScheduledCommandTest extends TestCase
{
    public function test_due_posts_are_dispatched_when_queue_mode_is_enabled(): void
    {
        Queue::fake();
        config()->set('larapost.queue.enabled', true);

        $account = SocialAccount::query()->create([
            'platform' => 'facebook',
            'account_name' => 'Queued scheduled page',
            'account_id_on_platform' => 'queued-scheduled-page',
            'credentials' => [
                'access_token' => 'token',
                'page_id' => 'queued-scheduled-page',
            ],
            'is_active' => true,
        ]);

        $post = ScheduledPost::query()->create([
            'account_id' => $account->id,
            'content' => 'Due queued content',
            'status' => ScheduledPost::STATUS_PENDING,
            'retry_count' => 0,
            'max_attempts' => 3,
            'scheduled_for' => now()->subMinute(),
        ]);

        $this->artisan('larapost:run-scheduled')
            ->expectsOutput('Queued scheduled post #' . $post->id . '.')
            ->assertExitCode(0);

        $this->assertSame(ScheduledPost::STATUS_PENDING, $post->fresh()->status);

        Queue::assertPushed(
            PublishScheduledPost::class,
            fn (PublishScheduledPost $job): bool => $job->scheduledPostId === $post->id
        );
    }
}
