<?php

namespace SocialSync\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use SocialSync\Models\ScheduledPost;

#[Description('Cancel a pending LaraPost scheduled post before it is claimed for publishing.')]
#[IsDestructive]
class CancelScheduledPost extends Tool
{
    public function handle(Request $request): Response
    {
        $postId = (int) $request->get('post_id', 0);

        if ($postId < 1) {
            return Response::error('post_id must be a positive integer.');
        }

        $updated = ScheduledPost::query()
            ->whereKey($postId)
            ->where('status', ScheduledPost::STATUS_PENDING)
            ->update([
                'status' => ScheduledPost::STATUS_CANCELLED,
                'error_message' => null,
            ]);

        if ($updated === 0) {
            return Response::error('The post does not exist or is no longer pending.');
        }

        return Response::json([
            'post_id' => $postId,
            'status' => ScheduledPost::STATUS_CANCELLED,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'post_id' => $schema->integer()->min(1)->description('The LaraPost scheduled post ID to cancel.')->required(),
        ];
    }
}
