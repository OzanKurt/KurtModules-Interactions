<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Notifications;

use Illuminate\Database\Eloquent\Model;

final class NewFollowerNotification extends InteractionNotification
{
    public function __construct(public readonly Model $follower) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_follower',
            'follower_type' => $this->follower->getMorphClass(),
            'follower_id' => $this->follower->getKey(),
        ];
    }
}
