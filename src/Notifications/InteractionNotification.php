<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Base for the bundled notifications: resolves delivery channels from
 * config('interactions.notifications.channels').
 */
abstract class InteractionNotification extends Notification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = config('interactions.notifications.channels', ['database']);

        if (! is_array($channels)) {
            return ['database'];
        }

        return array_values(array_filter($channels, 'is_string'));
    }
}
