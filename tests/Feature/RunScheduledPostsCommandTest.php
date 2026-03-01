<?php

namespace SocialSync\Tests\Feature;

use SocialSync\Models\ScheduledPost;
use SocialSync\Models\SocialAccount;
use SocialSync\Tests\TestCase;

class RunScheduledPostsCommandTest extends TestCase
{
    public function test_it_processes_due_scheduled_posts(): void
    {
        $account = SocialAccount::query()->create([
            'platform' => 'facebook',
            'account_name' => 'Demo Account',
            'account_id_on_platform' => 'demo-page',
            'credentials' => [
                'access_token' => 'token',
                'page_id' => 'demo-page',
            ],
            'is_active' => true,
        ]);

        $post = ScheduledPost::query()->create([
            'account_id' => $account->id,
            'content' => 'Queued content',
            'status' => ScheduledPost::STATUS_PENDING,
            'retry_count' => 0,
            'max_attempts' => 3,
            'scheduled_for' => now()->subMinute(),
        ]);

        $this->artisan('larapost:run-scheduled')
            ->expectsOutput('Published scheduled post #' . $post->id . '.')
            ->assertExitCode(0);

        $post->refresh();

        $this->assertSame(ScheduledPost::STATUS_PUBLISHED, $post->status);
        $this->assertNotNull($post->published_at);
    }

    public function test_it_treats_non_positive_limit_as_one(): void
    {
        $account = SocialAccount::query()->create([
            'platform' => 'facebook',
            'account_name' => 'Demo Account',
            'account_id_on_platform' => 'demo-page',
            'credentials' => [
                'access_token' => 'token',
                'page_id' => 'demo-page',
            ],
            'is_active' => true,
        ]);

        $firstPost = ScheduledPost::query()->create([
            'account_id' => $account->id,
            'content' => 'First queued content',
            'status' => ScheduledPost::STATUS_PENDING,
            'retry_count' => 0,
            'max_attempts' => 3,
            'scheduled_for' => now()->subMinutes(2),
        ]);

        $secondPost = ScheduledPost::query()->create([
            'account_id' => $account->id,
            'content' => 'Second queued content',
            'status' => ScheduledPost::STATUS_PENDING,
            'retry_count' => 0,
            'max_attempts' => 3,
            'scheduled_for' => now()->subMinute(),
        ]);

        $this->artisan('larapost:run-scheduled --limit=0')
            ->expectsOutput('Published scheduled post #' . $firstPost->id . '.')
            ->assertExitCode(0);

        $firstPost->refresh();
        $secondPost->refresh();

        $this->assertSame(ScheduledPost::STATUS_PUBLISHED, $firstPost->status);
        $this->assertSame(ScheduledPost::STATUS_PENDING, $secondPost->status);
    }
}
