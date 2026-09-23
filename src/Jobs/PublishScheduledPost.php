<?php

namespace SocialSync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SocialSync\Services\ScheduledPostPublisher;

class PublishScheduledPost implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public function __construct(public readonly int $scheduledPostId)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->scheduledPostId;
    }

    public function handle(ScheduledPostPublisher $publisher): void
    {
        $publisher->publish($this->scheduledPostId);
    }
}
