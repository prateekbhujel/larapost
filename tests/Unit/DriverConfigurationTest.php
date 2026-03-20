<?php

namespace SocialSync\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SocialSync\Drivers\FacebookDriver;
use SocialSync\Drivers\InstagramDriver;
use SocialSync\Drivers\LinkedInDriver;
use SocialSync\Drivers\TwitterDriver;

class DriverConfigurationTest extends TestCase
{
    public function test_facebook_driver_normalizes_numeric_api_versions(): void
    {
        $driver = new FacebookDriver([
            'app_id' => 'app-id',
            'app_secret' => 'app-secret',
            'api_version' => '25',
        ]);

        $url = $driver->getAuthorizationUrl('https://example.com/callback');

        $this->assertStringContainsString('https://www.facebook.com/v25.0/dialog/oauth?', $url);
    }

    public function test_instagram_driver_normalizes_numeric_api_versions(): void
    {
        $driver = new InstagramDriver([
            'app_id' => 'app-id',
            'app_secret' => 'app-secret',
            'api_version' => '25',
        ]);

        $url = $driver->getAuthorizationUrl('https://example.com/callback');

        $this->assertStringContainsString('https://www.facebook.com/v25.0/dialog/oauth?', $url);
    }

    public function test_twitter_driver_uses_basic_auth_for_confidential_clients(): void
    {
        $driver = new InspectableTwitterDriver([
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'api_version' => '2',
        ]);

        $options = $driver->exposedTokenRequestOptions([
            'grant_type' => 'authorization_code',
            'client_id' => 'client-id',
            'code' => 'abc123',
        ]);

        $this->assertSame(['client-id', 'client-secret'], $options['auth']);
        $this->assertArrayNotHasKey('client_id', $options['form_params']);
    }

    public function test_twitter_driver_keeps_client_id_for_public_clients(): void
    {
        $driver = new InspectableTwitterDriver([
            'client_id' => 'client-id',
            'api_version' => '2',
        ]);

        $options = $driver->exposedTokenRequestOptions([
            'grant_type' => 'authorization_code',
            'client_id' => 'client-id',
            'code' => 'abc123',
        ]);

        $this->assertArrayNotHasKey('auth', $options);
        $this->assertSame('client-id', $options['form_params']['client_id']);
    }

    public function test_twitter_driver_can_generate_authorization_url_without_laravel_context(): void
    {
        $driver = new TwitterDriver([
            'client_id' => 'client-id',
            'api_version' => '2',
        ]);

        $url = $driver->getAuthorizationUrl('https://example.com/callback');

        $this->assertStringContainsString('https://twitter.com/i/oauth2/authorize?', $url);
        $this->assertStringContainsString('client_id=client-id', $url);
        $this->assertStringContainsString('code_challenge=', $url);
        $this->assertStringContainsString('state=', $url);
    }

    public function test_linkedin_driver_can_generate_authorization_url_without_laravel_context(): void
    {
        $driver = new LinkedInDriver([
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
        ]);

        $url = $driver->getAuthorizationUrl('https://example.com/callback');

        $this->assertStringContainsString('https://www.linkedin.com/oauth/v2/authorization?', $url);
        $this->assertStringContainsString('client_id=client-id', $url);
        $this->assertStringContainsString('state=', $url);
    }
}

class InspectableTwitterDriver extends TwitterDriver
{
    public function exposedTokenRequestOptions(array $params): array
    {
        return $this->tokenRequestOptions($params);
    }
}
