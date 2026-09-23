<?php

namespace SocialSync\Tests\Feature;

use SocialSync\Tests\TestCase;

class RouteSecurityTest extends TestCase
{
    public function test_default_operator_routes_include_auth_middleware(): void
    {
        $config = require dirname(__DIR__, 2) . '/config/larapost.php';

        $this->assertContains('web', $config['routes']['operator_middleware']);
        $this->assertContains('auth', $config['routes']['operator_middleware']);
        $this->assertSame(['web'], $config['routes']['callback_middleware']);
    }
}
