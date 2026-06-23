<?php

namespace SocialSync\Tests\Feature;

use Carbon\Carbon;
use SocialSync\Models\PlatformCredential;
use SocialSync\Models\ScheduledPost;
use SocialSync\Models\SocialAccount;
use SocialSync\Tests\TestCase;

class DashboardFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        require dirname(__DIR__, 2) . '/routes/web.php';
    }

    public function test_dashboard_renders(): void
    {
        config()->set('app.timezone', 'Asia/Kathmandu');

        $response = $this->get('/larapost/dashboard');

        $response->assertOk();
        $response->assertSee('LaraPost Dashboard');
        $response->assertSee('Provider Connection');
        $response->assertSee('Bulk Composer');
        $response->assertSee('Connected Platforms');
        $response->assertSee('Timezone · Asia/Kathmandu');
    }

    public function test_dashboard_hides_legacy_accounts_for_unsupported_platforms(): void
    {
        SocialAccount::query()->create([
            'platform' => 'legacy-provider',
            'account_name' => 'Legacy Unsupported Account',
            'account_id_on_platform' => 'legacy-unsupported',
            'credentials' => ['access_token' => 'legacy-token'],
            'is_active' => true,
        ]);

        $response = $this->get('/larapost/dashboard');

        $response->assertOk();
        $response->assertDontSee('Legacy Unsupported Account');
        $response->assertDontSee('legacy-unsupported');
    }

    public function test_it_saves_platform_credentials_and_overrides_config(): void
    {
        config()->set('larapost.platforms.twitter.client_id', 'env-client');
        config()->set('larapost.platforms.twitter.client_secret', 'env-secret');

        $response = $this->post('/larapost/settings/twitter', [
            'client_id' => 'db-client',
            'client_secret' => 'db-secret',
            'api_version' => '2',
        ]);

        $response->assertRedirect();

        $record = PlatformCredential::query()->where('platform', 'twitter')->first();

        $this->assertNotNull($record);
        $this->assertSame('db-client', $record->credentials['client_id']);

        $platformConfig = app('social-media')->platformConfig('twitter');

        $this->assertSame('db-client', $platformConfig['client_id']);
        $this->assertSame('db-secret', $platformConfig['client_secret']);
    }

    public function test_dashboard_supports_xquik_backend_configuration(): void
    {
        config()->set('larapost.platforms.twitter.backend', 'xquik');
        config()->set('larapost.platforms.twitter.xquik_api_key', 'env-key');
        config()->set('larapost.platforms.twitter.xquik_account', '@env-account');

        $response = $this->get('/larapost/dashboard');

        $response->assertOk();
        $response->assertSee('Manual account setup');
        $response->assertSee('Create active accounts manually for this backend.');
        $response->assertDontSee('Login with Twitter / X');
    }

    public function test_it_saves_xquik_dashboard_credentials(): void
    {
        $response = $this->post('/larapost/settings/twitter', [
            'backend' => 'xquik',
            'xquik_api_key' => 'db-key',
            'xquik_account' => '@db-account',
            'xquik_api_base_url' => 'https://xquik.com/api/v1',
        ]);

        $response->assertRedirect();

        $record = PlatformCredential::query()->where('platform', 'twitter')->first();

        $this->assertNotNull($record);
        $this->assertSame('xquik', $record->credentials['backend']);
        $this->assertSame('db-key', $record->credentials['xquik_api_key']);
        $this->assertSame('@db-account', $record->credentials['xquik_account']);
    }

    public function test_it_preserves_saved_secret_credentials_when_inputs_are_blank(): void
    {
        PlatformCredential::query()->create([
            'platform' => 'twitter',
            'credentials' => [
                'backend' => 'xquik',
                'xquik_api_key' => 'saved-key',
                'xquik_account' => '@saved-account',
            ],
        ]);

        $response = $this->post('/larapost/settings/twitter', [
            'backend' => 'xquik',
            'xquik_api_key' => '',
            'xquik_account' => '@updated-account',
            'xquik_api_base_url' => 'https://xquik.com/api/v1',
        ]);

        $response->assertRedirect();

        $record = PlatformCredential::query()->where('platform', 'twitter')->first();

        $this->assertNotNull($record);
        $this->assertSame('saved-key', $record->credentials['xquik_api_key']);
        $this->assertSame('@updated-account', $record->credentials['xquik_account']);
    }

    public function test_it_publishes_from_dashboard_form(): void
    {
        SocialAccount::query()->create([
            'platform' => 'facebook',
            'account_name' => 'Demo Account',
            'account_id_on_platform' => 'demo-page',
            'credentials' => [
                'access_token' => 'token',
                'page_id' => 'demo-page',
            ],
            'is_active' => true,
        ]);

        $response = $this->post('/larapost/publish', [
            'content' => 'Dashboard publish test',
            'platforms' => ['facebook'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $post = ScheduledPost::query()->first();

        $this->assertNotNull($post);
        $this->assertSame(ScheduledPost::STATUS_PUBLISHED, $post->status);
    }

    public function test_it_publishes_only_to_selected_accounts_from_dashboard_form(): void
    {
        $firstAccount = SocialAccount::query()->create([
            'platform' => 'facebook',
            'account_name' => 'First Page',
            'account_id_on_platform' => 'page-1',
            'credentials' => [
                'access_token' => 'token-1',
                'page_id' => 'page-1',
            ],
            'is_active' => true,
        ]);

        SocialAccount::query()->create([
            'platform' => 'facebook',
            'account_name' => 'Second Page',
            'account_id_on_platform' => 'page-2',
            'credentials' => [
                'access_token' => 'token-2',
                'page_id' => 'page-2',
            ],
            'is_active' => true,
        ]);

        $response = $this->post('/larapost/publish', [
            'content' => 'Selected account publish test',
            'account_ids' => [$firstAccount->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $posts = ScheduledPost::query()->get();

        $this->assertCount(1, $posts);
        $this->assertSame($firstAccount->id, $posts->first()->account_id);
    }

    public function test_bulk_composer_publishes_different_content_to_different_accounts(): void
    {
        $firstAccount = SocialAccount::query()->create([
            'platform' => 'facebook',
            'account_name' => 'Page One',
            'account_id_on_platform' => 'page-1',
            'credentials' => [
                'access_token' => 'token-1',
                'page_id' => 'page-1',
            ],
            'is_active' => true,
        ]);

        $secondAccount = SocialAccount::query()->create([
            'platform' => 'facebook',
            'account_name' => 'Page Two',
            'account_id_on_platform' => 'page-2',
            'credentials' => [
                'access_token' => 'token-2',
                'page_id' => 'page-2',
            ],
            'is_active' => true,
        ]);

        $response = $this->post('/larapost/publish-bulk', [
            'entries' => [
                [
                    'account_id' => $firstAccount->id,
                    'content' => 'Status for page one',
                    'media_url' => '',
                    'media_type' => 'image',
                    'schedule_for' => '',
                ],
                [
                    'account_id' => $secondAccount->id,
                    'content' => 'Status for page two',
                    'media_url' => '',
                    'media_type' => 'image',
                    'schedule_for' => '',
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'Bulk composer processed 2 row(s): 2 published, 0 scheduled, 0 failed.'));

        $posts = ScheduledPost::query()->orderBy('account_id')->get();

        $this->assertCount(2, $posts);
        $this->assertSame(ScheduledPost::STATUS_PUBLISHED, $posts[0]->status);
        $this->assertSame(ScheduledPost::STATUS_PUBLISHED, $posts[1]->status);
        $this->assertSame('Status for page one', $posts[0]->content);
        $this->assertSame('Status for page two', $posts[1]->content);
        $this->assertSame($firstAccount->id, $posts[0]->account_id);
        $this->assertSame($secondAccount->id, $posts[1]->account_id);
    }

    public function test_bulk_composer_schedules_rows_using_application_timezone(): void
    {
        config()->set('app.timezone', 'Asia/Kathmandu');

        $account = SocialAccount::query()->create([
            'platform' => 'facebook',
            'account_name' => 'Nepal Page',
            'account_id_on_platform' => 'page-nepal',
            'credentials' => [
                'access_token' => 'token',
                'page_id' => 'page-nepal',
            ],
            'is_active' => true,
        ]);

        $scheduleFor = Carbon::now('Asia/Kathmandu')->addHour()->format('Y-m-d\TH:i');

        $response = $this->post('/larapost/publish-bulk', [
            'entries' => [
                [
                    'account_id' => $account->id,
                    'content' => 'Scheduled in Nepal time',
                    'media_url' => '',
                    'media_type' => 'image',
                    'schedule_for' => $scheduleFor,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', fn (string $message): bool => str_contains($message, 'Bulk composer processed 1 row(s): 0 published, 1 scheduled, 0 failed.'));

        $post = ScheduledPost::query()->first();

        $this->assertNotNull($post);
        $this->assertSame(ScheduledPost::STATUS_PENDING, $post->status);
        $this->assertSame($scheduleFor, $post->scheduled_for?->format('Y-m-d\TH:i'));
    }

    public function test_connect_route_supports_popup_mode(): void
    {
        $response = $this->get('/larapost/connect/facebook?mode=popup');

        $response->assertRedirect();

        $location = (string) $response->headers->get('Location', '');

        $this->assertStringContainsString('/larapost/callback/facebook', $location);
        $this->assertStringContainsString('oauth=fake', $location);
    }

    public function test_callback_popup_success_returns_popup_completion_page(): void
    {
        $this->get('/larapost/connect/facebook?mode=popup');

        $response = $this->get('/larapost/callback/facebook?code=fake-code');

        $response->assertOk();
        $response->assertSee('Account Connected');
        $response->assertSee('window.opener.postMessage', false);
        $response->assertSee('larapost-oauth', false);

        $this->assertSame(1, SocialAccount::query()->count());
    }

    public function test_callback_syncs_all_facebook_pages_from_single_oauth_response(): void
    {
        $this->get('/larapost/connect/facebook?mode=popup');

        $response = $this->get('/larapost/callback/facebook?code=multi-page');

        $response->assertOk();
        $response->assertSee('2 account(s) synced');

        $accounts = SocialAccount::query()->orderBy('account_id_on_platform')->get();

        $this->assertCount(2, $accounts);
        $this->assertSame('Fake Page One', $accounts[0]->account_name);
        $this->assertSame('Fake Page Two', $accounts[1]->account_name);
        $this->assertSame('fake-page-1', $accounts[0]->credentials['page_id']);
        $this->assertSame('fake-page-2', $accounts[1]->credentials['page_id']);
    }

    public function test_callback_popup_error_returns_popup_error_page(): void
    {
        $this->get('/larapost/connect/facebook?mode=popup');

        $response = $this->get('/larapost/callback/facebook');

        $response->assertStatus(422);
        $response->assertSee('Connection Failed');
        $response->assertSee('Missing OAuth authorization code.');
        $response->assertSee('window.opener.postMessage', false);
    }
}
