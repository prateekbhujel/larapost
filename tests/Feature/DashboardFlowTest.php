<?php

namespace SocialSync\Tests\Feature;

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
        $response = $this->get('/larapost/dashboard');

        $response->assertOk();
        $response->assertSee('LaraPost Dashboard');
        $response->assertSee('Provider Connection');
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
