<?php

namespace SocialSync\Mcp;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use SocialSync\Mcp\Tools\CancelScheduledPost;
use SocialSync\Mcp\Tools\CreatePost;
use SocialSync\Mcp\Tools\GetBusinessContext;
use SocialSync\Mcp\Tools\GetCapabilities;
use SocialSync\Mcp\Tools\ListAccounts;
use SocialSync\Mcp\Tools\ListPosts;

#[Name('LaraPost')]
#[Version('2.0.0')]
#[Instructions('Use LaraPost to inspect connected social accounts and content history, understand the configured business context, and publish or schedule content. Read business context before drafting when available. Never expose credentials or tokens. Treat create_post and cancel_scheduled_post as side effects and only call them when the user clearly asked to change publishing state.')]
class LaraPostServer extends Server
{
    protected array $tools = [
        GetBusinessContext::class,
        GetCapabilities::class,
        ListAccounts::class,
        ListPosts::class,
        CreatePost::class,
        CancelScheduledPost::class,
    ];
}
