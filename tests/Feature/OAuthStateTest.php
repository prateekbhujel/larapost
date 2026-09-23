<?php

namespace SocialSync\Tests\Feature;

use SocialSync\Drivers\FacebookDriver;
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

        require dirname(__DIR__, 2) . '/routes/web.php';

        SocialMedia::extend('facebook', FacebookDriver::class);
    }

    public function test_facebook_authorization_contains_state(): void
    {
        $response = $this->get('/larapost/connect/facebook');

        $response->assertRedirect();

        $location = $response->headers->get('Location');

        $this->assertIsString($location);
        $this->assertStringContainsString('state=', $location);
    }

    public function test_facebook_callback_rejects_missing_state_before_token_exchange(): void
    {
        $this->get('/larapost/connect/facebook');

        $response = $this->get('/larapost/callback/facebook?code=fake-code');

        $response->assertRedirect(route('larapost.dashboard'));
        $response->assertSessionHas('error', 'Facebook returned an invalid OAuth state. Start OAuth again.');
    }
}
