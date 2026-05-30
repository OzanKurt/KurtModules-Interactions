<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Notifications;

use Illuminate\Database\Eloquent\Model;

final class FriendRequestNotification extends InteractionNotification
{
    public function __construct(public readonly Model $sender) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'friend_request',
            'sender_type' => $this->sender->getMorphClass(),
            'sender_id' => $this->sender->getKey(),
        ];
    }
}
