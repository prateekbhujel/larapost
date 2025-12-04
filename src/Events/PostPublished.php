<?php

namespace SocialSync\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SocialSync\Models\ScheduledPost;

class PostPublished
{
    use Dispatchable, SerializesModels;

    public $scheduledPost;
    public $result;

    /**
     * Create a new event instance.
     *
     * @param ScheduledPost $scheduledPost
     * @param array $result
     */
    public function __construct(ScheduledPost $scheduledPost, array $result)
    {
        $this->scheduledPost = $scheduledPost;
        $this->result = $result;
    }
}
