<?php

namespace SocialSync\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use SocialSync\Models\ScheduledPost;

#[Description('List recent, scheduled, published, failed, or cancelled LaraPost content without returning provider credentials.')]
#[IsReadOnly]
class ListPosts extends Tool
{
    public function handle(Request $request): Response
    {
        $limit = min(100, max(1, (int) $request->get('limit', 20)));
        $status = strtolower(trim((string) $request->get('status', '')));

        $query = ScheduledPost::query()->with('account')->latest();

        if ($status !== '') {
            $query->where('status', $status);
        }

        $posts = $query->limit($limit)->get()->map(static fn (ScheduledPost $post): array => [
            'id' => $post->id,
            'platform' => $post->account?->platform,
            'account_id' => $post->account_id,
            'account_name' => $post->account?->account_name,
            'status' => $post->status,
            'content' => $post->content,
            'media' => $post->media ?? [],
            'scheduled_for' => $post->scheduled_for?->toIso8601String(),
            'published_at' => $post->published_at?->toIso8601String(),
            'retry_count' => (int) $post->retry_count,
            'error' => $post->error_message,
        ])->values()->all();

        return Response::json(['posts' => $posts]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->max(24)->description('Optional status filter: pending, processing, published, failed, or cancelled.'),
            'limit' => $schema->integer()->min(1)->max(100)->description('Maximum posts to return. Defaults to 20.'),
        ];
    }
}
