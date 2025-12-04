<?php

namespace SocialSync\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SocialSync\Models\ScheduledPost;

class PostFailed
{
    use Dispatchable, SerializesModels;

    public $scheduledPost;
    public $errorMessage;

    /**
     * Create a new event instance.
     *
     * @param ScheduledPost $scheduledPost
     * @param string $errorMessage
     */
    public function __construct(ScheduledPost $scheduledPost, string $errorMessage)
    {
        $this->scheduledPost = $scheduledPost;
        $this->errorMessage = $errorMessage;
    }
}
