<?php

namespace SocialSync\Tests\Feature;

use SocialSync\Tests\TestCase;

class DoctorCommandTest extends TestCase
{
    public function test_doctor_fails_when_mcp_is_enabled_without_a_token(): void
    {
        config()->set('larapost.mcp.enabled', true);
        config()->set('larapost.mcp.token', '');

        $this->artisan('larapost:doctor')
            ->assertExitCode(1);
    }

    public function test_doctor_fails_for_an_invalid_tiktok_publish_mode(): void
    {
        config()->set('larapost.enabled_platforms', ['tiktok']);
        config()->set('larapost.platforms.tiktok.publish_mode', 'magic');

        $this->artisan('larapost:doctor')
            ->assertExitCode(1);
    }
}
