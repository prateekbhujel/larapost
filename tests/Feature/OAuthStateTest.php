<?php

namespace SocialSync\Tests\Feature;

use SocialSync\Drivers\FacebookDriver;
use SocialSync\Drivers\LinkedInDriver;
use SocialSync\Drivers\TwitterDriver;
use SocialSync\Facades\SocialMedia;
use SocialSync\Tests\TestCase;

class OAuthStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('larapost.routes.operator_middleware', ['web']);
        config()->set('larapost.platforms.facebook.app_id', 'app-id');
        config()->set('larapost.platforms.facebook.app_secret', 'app-secret');
        config()->set('larapost.platforms.twitter.client_id', 'client-id');
        config()->set('larapost.platforms.linkedin.client_id', 'client-id');
        config()->set('larapost.platforms.linkedin.client_secret', 'client-secret');

        require dirname(__DIR__, 2) . '/routes/web.php';

        SocialMedia::extend('facebook', FacebookDriver::class);
        SocialMedia::extend('twitter', TwitterDriver::class);
        SocialMedia::extend('linkedin', LinkedInDriver::class);
    }

    public function test_provider_authorization_urls_contain_state(): void
    {
        foreach (['facebook', 'twitter', 'linkedin'] as $platform) {
            $response = $this->get('/larapost/connect/' . $platform);

            $response->assertRedirect();

            $location = $response->headers->get('Location');

            $this->assertIsString($location);
            $this->assertStringContainsString('state=', $location);
        }
    }

    public function test_callbacks_reject_missing_state_before_token_exchange(): void
    {
        $messages = [
            'facebook' => 'Facebook returned an invalid OAuth state. Start OAuth again.',
            'twitter' => 'Missing Twitter OAuth state context. Start OAuth again.',
            'linkedin' => 'Missing LinkedIn OAuth state context. Start OAuth again.',
        ];

        foreach ($messages as $platform => $message) {
            $this->get('/larapost/connect/' . $platform);

            $response = $this->get('/larapost/callback/' . $platform . '?code=fake-code');

            $response->assertRedirect(route('larapost.dashboard'));
            $response->assertSessionHas('error', $message);
        }
    }
}
