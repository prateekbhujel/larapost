<?php

namespace SocialSync\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use SocialSync\Facades\SocialMedia;
use SocialSync\Jobs\PublishScheduledPost;
use SocialSync\Models\ScheduledPost;
use SocialSync\Models\SocialAccount;
use SocialSync\Tests\TestCase;

class QueuePublishingTest extends TestCase
{
    public function test_it_persists_before_dispatching_an_immediate_queued_post(): void
    {
        Queue::fake();

        $account = SocialAccount::query()->create([
            'platform' => 'facebook',
            'account_name' => 'Queued Page',
            'account_id_on_platform' => 'queued-page',
            'credentials' => [
                'access_token' => 'token',
                'page_id' => 'queued-page',
            ],
            'is_active' => true,
        ]);

        $results = SocialMedia::post()
            ->content('Queued message')
            ->platform('facebook')
            ->queue();

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]['queued']);
        $this->assertSame($account->id, $results[0]['account_id']);

        $post = ScheduledPost::query()->firstOrFail();

        $this->assertSame(ScheduledPost::STATUS_PENDING, $post->status);
        $this->assertSame('Queued message', $post->content);

        Queue::assertPushed(
            PublishScheduledPost::class,
            fn (PublishScheduledPost $job): bool => $job->scheduledPostId === $post->id
        );
    }

    public function test_future_queue_call_waits_for_scheduler_instead_of_dispatching_early(): void
    {
        Queue::fake();

        SocialAccount::query()->create([
            'platform' => 'facebook',
            'account_name' => 'Scheduled Page',
            'account_id_on_platform' => 'scheduled-page',
            'credentials' => [
                'access_token' => 'token',
                'page_id' => 'scheduled-page',
            ],
            'is_active' => true,
        ]);

        $results = SocialMedia::post()
            ->content('Later message')
            ->platform('facebook')
            ->scheduleFor(now()->addHour())
            ->queue();

        $this->assertTrue($results[0]['scheduled']);
        $this->assertFalse($results[0]['queued']);
        Queue::assertNothingPushed();
    }
}
