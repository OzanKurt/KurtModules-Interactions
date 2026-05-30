<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Notifications;

use Illuminate\Database\Eloquent\Model;

final class MentionedNotification extends InteractionNotification
{
    public function __construct(public readonly Model $source) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'mentioned',
            'source_type' => $this->source->getMorphClass(),
            'source_id' => $this->source->getKey(),
        ];
    }
}
