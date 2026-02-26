<?php

namespace SocialSync\Tests\Feature;

use SocialSync\Facades\SocialMedia;
use SocialSync\Models\ScheduledPost;
use SocialSync\Models\SocialAccount;
use SocialSync\Tests\TestCase;

class PostBuilderTest extends TestCase
{
    public function test_it_publishes_to_active_accounts(): void
    {
        $activeAccount = $this->createAccount('facebook', true, 'page-1');
        $this->createAccount('facebook', false, 'page-2');

        $results = SocialMedia::post()
            ->content('Hello world')
            ->platforms(['facebook'])
            ->publish();

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]['success']);
        $this->assertSame($activeAccount->id, $results[0]['account_id']);

        $scheduled = ScheduledPost::query()->first();

        $this->assertNotNull($scheduled);
        $this->assertSame(ScheduledPost::STATUS_PUBLISHED, $scheduled->status);
    }

    public function test_it_schedules_posts_for_future(): void
    {
        $account = $this->createAccount('facebook', true, 'page-3');

        $results = SocialMedia::post()
            ->content('Scheduled message')
            ->platforms(['facebook'])
            ->scheduleFor(now()->addHour())
            ->publish();

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]['scheduled']);
        $this->assertSame($account->id, $results[0]['account_id']);

        $scheduled = ScheduledPost::query()->first();

        $this->assertNotNull($scheduled);
        $this->assertSame(ScheduledPost::STATUS_PENDING, $scheduled->status);
        $this->assertNotNull($scheduled->scheduled_for);
    }

    public function test_it_marks_failure_when_driver_errors(): void
    {
        $this->createAccount('facebook', true, 'page-4');

        $results = SocialMedia::post()
            ->content('Please fail this post')
            ->platforms(['facebook'])
            ->publish();

        $this->assertCount(1, $results);
        $this->assertFalse($results[0]['success']);

        $scheduled = ScheduledPost::query()->first();

        $this->assertNotNull($scheduled);
        $this->assertSame(ScheduledPost::STATUS_FAILED, $scheduled->status);
    }

    protected function createAccount(string $platform, bool $active, string $platformId): SocialAccount
    {
        return SocialAccount::query()->create([
            'platform' => $platform,
            'account_name' => ucfirst($platform) . ' Account',
            'account_username' => 'user-' . $platformId,
            'account_id_on_platform' => $platformId,
            'credentials' => [
                'access_token' => 'token-' . $platformId,
                'page_id' => $platformId,
                'instagram_business_account_id' => 'ig-' . $platformId,
                'person_urn' => 'urn:li:person:' . $platformId,
                'refresh_token' => 'refresh-' . $platformId,
            ],
            'is_active' => $active,
            'metadata' => [],
        ]);
    }
}
