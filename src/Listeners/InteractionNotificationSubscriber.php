<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Notification;
use Kurt\Modules\Interactions\Events\CommentReplied;
use Kurt\Modules\Interactions\Events\Followed;
use Kurt\Modules\Interactions\Events\FriendRequested;
use Kurt\Modules\Interactions\Events\UserMentioned;
use Kurt\Modules\Interactions\Notifications\CommentReplyNotification;
use Kurt\Modules\Interactions\Notifications\FriendRequestNotification;
use Kurt\Modules\Interactions\Notifications\MentionedNotification;
use Kurt\Modules\Interactions\Notifications\NewFollowerNotification;

/**
 * Translates domain events into the bundled notifications. Registered only when
 * config('interactions.notifications.enabled') is true, so the module is
 * event-only by default. Recipients are notified only when they are Notifiable.
 */
final class InteractionNotificationSubscriber
{
    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Followed::class => 'onFollowed',
            UserMentioned::class => 'onMentioned',
            CommentReplied::class => 'onCommentReplied',
            FriendRequested::class => 'onFriendRequested',
        ];
    }

    public function onFollowed(Followed $event): void
    {
        $this->notify($event->followed, new NewFollowerNotification($event->follower));
    }

    public function onMentioned(UserMentioned $event): void
    {
        $this->notify($event->mentioned, new MentionedNotification($event->source));
    }

    public function onCommentReplied(CommentReplied $event): void
    {
        $this->notify($event->parent->author, new CommentReplyNotification($event->reply));
    }

    public function onFriendRequested(FriendRequested $event): void
    {
        $this->notify($event->recipient, new FriendRequestNotification($event->sender));
    }

    private function notify(?object $notifiable, Notification $notification): void
    {
        if ($notifiable !== null && method_exists($notifiable, 'notify')) {
            $notifiable->notify($notification);
        }
    }
}
