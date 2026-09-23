<?php

namespace SocialSync\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Get the business and brand context that should guide social content, without exposing credentials.')]
#[IsReadOnly]
class GetBusinessContext extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::json([
            'business' => array_filter((array) config('larapost.mcp.business', []), static fn ($value) => $value !== null && $value !== ''),
            'timezone' => config('app.timezone', 'UTC'),
            'guidance' => [
                'Use this context as editorial guidance, not as permission to publish.',
                'Never invent business claims, prices, offers, or facts that are not present in the context or user request.',
                'Ask for confirmation when the publishing intent, destination, timing, or material business claim is ambiguous.',
            ],
        ]);
    }
}
