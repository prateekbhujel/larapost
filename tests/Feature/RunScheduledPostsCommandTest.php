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

        $this->artisan('social-sync:run-scheduled')
            ->expectsOutput('Published scheduled post #' . $post->id . '.')
            ->assertExitCode(0);

        $post->refresh();

        $this->assertSame(ScheduledPost::STATUS_PUBLISHED, $post->status);
        $this->assertNotNull($post->published_at);
    }
}
