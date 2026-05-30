<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Graph;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Events\FriendRequestAccepted;
use Kurt\Modules\Interactions\Events\FriendRequested;
use Kurt\Modules\Interactions\Graph\Enums\FriendshipStatus;
use Kurt\Modules\Interactions\Graph\Models\Friendship;

/**
 * Friend-request lifecycle. Requests are directed (sender → recipient);
 * acceptance makes the relationship mutual. "Are these two friends?" and
 * unfriend check both directions.
 */
final class FriendshipManager
{
    public function befriend(Model $sender, Model $recipient): Friendship
    {
        $friendship = Friendship::query()->firstOrCreate(
            ['sender_id' => $sender->getKey(), 'recipient_id' => $recipient->getKey()],
            ['status' => FriendshipStatus::Pending->value],
        );

        if ($friendship->wasRecentlyCreated) {
            event(new FriendRequested($sender, $recipient));
        }

        return $friendship;
    }

    public function accept(Model $recipient, Model $sender): bool
    {
        $accepted = $this->pending($sender, $recipient)->update([
            'status' => FriendshipStatus::Accepted->value,
            'accepted_at' => now(),
        ]) > 0;

        if ($accepted) {
            event(new FriendRequestAccepted($sender, $recipient));
        }

        return $accepted;
    }

    public function deny(Model $recipient, Model $sender): bool
    {
        return $this->pending($sender, $recipient)->update(['status' => FriendshipStatus::Denied->value]) > 0;
    }

    public function unfriend(Model $a, Model $b): bool
    {
        return $this->between($a, $b)->delete() > 0;
    }

    public function block(Model $blocker, Model $blocked): Friendship
    {
        $this->between($blocker, $blocked)->delete();

        return Friendship::query()->create([
            'sender_id' => $blocker->getKey(),
            'recipient_id' => $blocked->getKey(),
            'status' => FriendshipStatus::Blocked->value,
        ]);
    }

    public function areFriends(Model $a, Model $b): bool
    {
        return $this->between($a, $b)->where('status', FriendshipStatus::Accepted->value)->exists();
    }

    /**
     * @return Builder<Friendship>
     */
    private function pending(Model $sender, Model $recipient): Builder
    {
        return Friendship::query()
            ->where('sender_id', $sender->getKey())
            ->where('recipient_id', $recipient->getKey())
            ->where('status', FriendshipStatus::Pending->value);
    }

    /**
     * @return Builder<Friendship>
     */
    private function between(Model $a, Model $b): Builder
    {
        // Wrap the OR in a single nested group so callers can safely append an
        // AND (e.g. ->where('status', …)) without operator-precedence bugs.
        return Friendship::query()->where(function (Builder $query) use ($a, $b): void {
            $query->where(function (Builder $inner) use ($a, $b): void {
                $inner->where('sender_id', $a->getKey())->where('recipient_id', $b->getKey());
            })->orWhere(function (Builder $inner) use ($a, $b): void {
                $inner->where('sender_id', $b->getKey())->where('recipient_id', $a->getKey());
            });
        });
    }
}
