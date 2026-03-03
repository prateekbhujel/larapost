<?php

namespace SocialSync;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use SocialSync\Events\PostFailed;
use SocialSync\Events\PostPublished;
use SocialSync\Exceptions\SocialSyncException;
use SocialSync\Models\ScheduledPost;
use SocialSync\Models\SocialAccount;

class PostBuilder
{
    protected SocialMediaManager $manager;

    protected ?string $content = null;

    /**
     * @var array<int, string>
     */
    protected array $platforms = [];

    /**
     * @var array<string, array<int, int>|string>
     */
    protected array $accountFilters = [];

    /**
     * @var array<int, array{type: string, path: string}>
     */
    protected array $media = [];

    protected array $metadata = [];

    protected ?Carbon $scheduledFor = null;

    public function __construct(SocialMediaManager $manager)
    {
        $this->manager = $manager;
    }

    public function content(string $content): self
    {
        $this->content = trim($content);

        return $this;
    }

    public function platforms(array $platforms): self
    {
        $normalized = array_map(static fn ($platform) => strtolower(trim((string) $platform)), $platforms);
        $normalized = array_values(array_filter(array_unique($normalized)));

        if ($normalized === []) {
            throw new SocialSyncException('You must provide at least one platform.');
        }

        $unsupported = array_diff($normalized, $this->manager->supportedPlatforms());

        if ($unsupported !== []) {
            throw new SocialSyncException('Unsupported platform(s): ' . implode(', ', $unsupported));
        }

        $this->platforms = $normalized;

        return $this;
    }

    public function platform(string $platform): self
    {
        return $this->platforms([$platform]);
    }

    public function accounts(array $accounts): self
    {
        $this->accountFilters = $accounts;

        return $this;
    }

    public function image(string|array $path): self
    {
        return $this->media($path, 'image');
    }

    public function video(string|array $path): self
    {
        return $this->media($path, 'video');
    }

    public function media(string|array $paths, string $type = 'image'): self
    {
        $entries = is_array($paths) ? $paths : [$paths];

        foreach ($entries as $path) {
            $path = trim((string) $path);

            if ($path === '') {
                continue;
            }

            $this->media[] = [
                'type' => $type,
                'path' => $path,
            ];
        }

        return $this;
    }

    public function metadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function scheduleFor(DateTimeInterface|string $when): self
    {
        $date = $when instanceof DateTimeInterface ? Carbon::instance($when) : Carbon::parse($when);

        if ($date->isPast()) {
            throw new SocialSyncException('Scheduled time must be in the future.');
        }

        $this->scheduledFor = $date;

        return $this;
    }

    public function publish(): array
    {
        $this->validateBeforePublish();

        $accounts = $this->resolveAccounts();

        if ($accounts->isEmpty()) {
            throw new SocialSyncException('No active accounts matched your platform and account filters.');
        }

        if ($this->scheduledFor !== null) {
            return $this->schedulePosts($accounts);
        }

        return $this->publishNow($accounts);
    }

    protected function validateBeforePublish(): void
    {
        if ($this->content === null || $this->content === '') {
            throw new SocialSyncException('Post content is required.');
        }

        if ($this->platforms === []) {
            $this->platforms = [$this->manager->defaultPlatform()];
        }
    }

    protected function resolveAccounts(): Collection
    {
        $accounts = SocialAccount::query()
            ->active()
            ->whereIn('platform', $this->platforms)
            ->orderBy('platform')
            ->orderBy('id')
            ->get();

        return $accounts->filter(function (SocialAccount $account): bool {
            $filter = $this->accountFilters[$account->platform] ?? null;

            if ($filter === null || $filter === 'all') {
                return true;
            }

            $allowedIds = is_array($filter) ? $filter : [$filter];
            $allowedIds = array_map(static fn ($id) => (int) $id, $allowedIds);

            return in_array($account->id, $allowedIds, true);
        })->values();
    }

    protected function payload(): array
    {
        return [
            'content' => $this->content,
            'media' => $this->media,
            'metadata' => $this->metadata,
        ];
    }

    protected function schedulePosts(Collection $accounts): array
    {
        $payload = $this->payload();
        $maxAttempts = (int) config('larapost.retry.max_attempts', 3);

        return $accounts->map(function (SocialAccount $account) use ($payload, $maxAttempts): array {
            $scheduledPost = ScheduledPost::query()->create([
                'account_id' => $account->id,
                'content' => $payload['content'],
                'media' => $payload['media'],
                'metadata' => $payload['metadata'],
                'status' => ScheduledPost::STATUS_PENDING,
                'retry_count' => 0,
                'max_attempts' => $maxAttempts,
                'scheduled_for' => $this->scheduledFor,
            ]);

            return [
                'success' => true,
                'scheduled' => true,
                'platform' => $account->platform,
                'account_id' => $account->id,
                'scheduled_post_id' => $scheduledPost->id,
                'scheduled_for' => $this->scheduledFor?->toIso8601String(),
            ];
        })->all();
    }

    protected function publishNow(Collection $accounts): array
    {
        $payload = $this->payload();

        return $accounts->map(function (SocialAccount $account) use ($payload): array {
            $scheduledPost = ScheduledPost::query()->create([
                'account_id' => $account->id,
                'content' => $payload['content'],
                'media' => $payload['media'],
                'metadata' => $payload['metadata'],
                'status' => ScheduledPost::STATUS_PENDING,
                'retry_count' => 0,
                'max_attempts' => (int) config('larapost.retry.max_attempts', 3),
                'scheduled_for' => now(),
            ]);

            $result = $this->manager->publish($account->id, $payload);

            if (($result['success'] ?? false) === true) {
                $scheduledPost->forceFill([
                    'status' => ScheduledPost::STATUS_PUBLISHED,
                    'published_at' => now(),
                    'published_response' => $result['response'] ?? null,
                    'error_message' => null,
                ])->save();

                event(new PostPublished($scheduledPost, $result));

                return $result;
            }

            $scheduledPost->forceFill([
                'status' => ScheduledPost::STATUS_FAILED,
                'error_message' => $result['error'] ?? 'Publishing failed.',
            ])->save();

            event(new PostFailed($scheduledPost, $result['error'] ?? 'Publishing failed.'));

            return array_merge($result, [
                'platform' => $account->platform,
                'account_id' => $account->id,
            ]);
        })->all();
    }
}
