<?php

namespace SocialSync\Tests\Unit;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use SocialSync\Support\TokenCredentials;

class TokenCredentialsTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_it_stamps_access_and_refresh_expiry_times(): void
    {
        CarbonImmutable::setTestNow('2026-09-23T08:00:00+00:00');

        $credentials = TokenCredentials::normalize([], [
            'access_token' => 'access',
            'refresh_token' => 'refresh',
            'expires_in' => 3600,
            'refresh_expires_in' => 7200,
        ]);

        $this->assertSame('2026-09-23T09:00:00+00:00', $credentials['expires_at']);
        $this->assertSame('2026-09-23T10:00:00+00:00', $credentials['refresh_expires_at']);
    }

    public function test_it_detects_tokens_that_are_close_to_expiry(): void
    {
        CarbonImmutable::setTestNow('2026-09-23T08:00:00+00:00');

        $this->assertTrue(TokenCredentials::expiresSoon([
            'expires_at' => '2026-09-23T08:05:00+00:00',
        ], 600));

        $this->assertFalse(TokenCredentials::expiresSoon([
            'expires_at' => '2026-09-23T09:00:00+00:00',
        ], 600));
    }

    public function test_replacing_a_token_without_expiry_drops_stale_expiry_metadata(): void
    {
        $credentials = TokenCredentials::normalize([
            'access_token' => 'old',
            'expires_at' => '2020-01-01T00:00:00+00:00',
        ], [
            'access_token' => 'new',
        ]);

        $this->assertSame('new', $credentials['access_token']);
        $this->assertArrayNotHasKey('expires_at', $credentials);
    }
}
