<?php

namespace SocialSync\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Models\SocialAccount;
use SocialSync\SocialMediaManager;

#[Description('Publish or schedule social content through LaraPost. This changes application and external social-platform state.')]
#[IsDestructive]
#[IsOpenWorld]
class CreatePost extends Tool
{
    public function __construct(private readonly SocialMediaManager $manager)
    {
    }

    public function handle(Request $request): Response
    {
        $data = $request->validate([
            'content' => ['nullable', 'string', 'max:5000'],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => ['string', 'max:40'],
            'account_ids' => ['nullable', 'array'],
            'account_ids.*' => ['integer', 'min:1'],
            'media_urls' => ['nullable', 'array', 'max:35'],
            'media_urls.*' => ['string', 'max:4096'],
            'media_type' => ['nullable', 'in:image,video'],
            'schedule_at' => ['nullable', 'date'],
            'queue' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ]);

        $accountIds = array_values(array_unique(array_map('intval', (array) ($data['account_ids'] ?? []))));
        $platforms = array_values(array_unique(array_map(
            static fn ($platform): string => strtolower(trim((string) $platform)),
            (array) ($data['platforms'] ?? [])
        )));

        $builder = $this->manager->post()
            ->content((string) ($data['content'] ?? ''))
            ->metadata((array) ($data['metadata'] ?? []));

        if ($accountIds !== []) {
            $accounts = SocialAccount::query()->active()->whereIn('id', $accountIds)->get();

            if ($accounts->count() !== count($accountIds)) {
                throw new SocialSyncException('One or more requested social accounts are missing or inactive.');
            }

            $platforms = $accounts->pluck('platform')->unique()->values()->all();
            $filters = $accounts->groupBy('platform')->map(
                static fn ($group): array => $group->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all()
            )->all();

            $builder->accounts($filters);
        }

        if ($platforms === []) {
            throw new SocialSyncException('Choose at least one platform or account.');
        }

        $builder->platforms($platforms);

        $mediaUrls = (array) ($data['media_urls'] ?? []);

        if ($mediaUrls !== []) {
            $builder->media($mediaUrls, (string) ($data['media_type'] ?? 'image'));
        }

        if (!empty($data['schedule_at'])) {
            $builder->scheduleFor((string) $data['schedule_at']);
        }

        $results = !empty($data['queue'])
            ? $builder->queue()
            : $builder->publish();

        return Response::json([
            'results' => $results,
            'message' => !empty($data['schedule_at'])
                ? 'Content scheduled.'
                : (!empty($data['queue']) ? 'Content queued.' : 'Publish request completed.'),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'content' => $schema->string()->max(5000)->description('Post copy. TikTok requires media; other platforms may publish text.'),
            'platforms' => $schema->array()->items($schema->string())->description('Platforms to target when account_ids is not provided.'),
            'account_ids' => $schema->array()->items($schema->integer())->description('Exact connected LaraPost account IDs to target.'),
            'media_urls' => $schema->array()->items($schema->string())->description('Optional media URLs. TikTok requires public HTTPS URLs from a verified domain or URL prefix.'),
            'media_type' => $schema->string()->description('image or video. Applies to every media_urls entry.'),
            'schedule_at' => $schema->string()->description('Optional ISO 8601 date/time. Omit to publish immediately.'),
            'queue' => $schema->boolean()->description('Dispatch immediate publishing through Laravel queue instead of doing it inline.'),
            'metadata' => $schema->object()->description('Optional provider-specific settings, for example metadata.tiktok.privacy_level.'),
        ];
    }
}
