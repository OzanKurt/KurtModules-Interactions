<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Notifications;

use Kurt\Modules\Interactions\Comments\Models\Comment;

final class CommentReplyNotification extends InteractionNotification
{
    public function __construct(public readonly Comment $reply) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'comment_reply',
            'comment_id' => $this->reply->getKey(),
            'parent_id' => $this->reply->parent_id,
        ];
    }
}
