<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;
use Kurt\Modules\Interactions\Engagement\Models\Interaction;
use Kurt\Modules\Interactions\Engagement\Models\Rating;
use Kurt\Modules\Interactions\Events\Followed;
use Kurt\Modules\Interactions\Events\Liked;
use Kurt\Modules\Interactions\Events\Rated;
use Kurt\Modules\Interactions\Events\Voted;
use Kurt\Modules\Interactions\Exceptions\InvalidRatingException;
use Kurt\Modules\Interactions\Exceptions\SelfInteractionException;

/**
 * Single write path for engagement. Every verb is idempotent via
 * updateOrCreate against the (user, subject, type) unique key, and counters are
 * resynced after each mutation.
 */
final class InteractionManager
{
    public function __construct(private readonly CounterSync $counters) {}

    public function add(Model $user, Model $subject, InteractionType $type, ?int $value = null): Interaction
    {
        if ($this->isSelfInteraction($user, $subject)) {
            throw SelfInteractionException::for($type);
        }

        $interaction = Interaction::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'type' => $type->value,
            ],
            ['value' => $value],
        );

        $this->counters->sync($subject, $type);

        if ($interaction->wasRecentlyCreated) {
            match ($type) {
                InteractionType::Follow => event(new Followed($user, $subject)),
                InteractionType::Like => event(new Liked($user, $subject)),
                default => null,
            };
        }

        // Only fire Voted when the vote is newly cast or its value actually
        // changed; re-casting an identical vote is a no-op and must stay silent.
        if ($type === InteractionType::Vote && ($interaction->wasRecentlyCreated || $interaction->wasChanged('value'))) {
            event(new Voted($user, $subject, (int) $value));
        }

        return $interaction;
    }

    public function remove(Model $user, Model $subject, InteractionType $type): bool
    {
        $deleted = Interaction::query()
            ->where('user_id', $user->getKey())
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('type', $type->value)
            ->delete();

        if ($deleted > 0) {
            $this->counters->sync($subject, $type);
        }

        return $deleted > 0;
    }

    public function has(Model $user, Model $subject, InteractionType $type): bool
    {
        return Interaction::query()
            ->where('user_id', $user->getKey())
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('type', $type->value)
            ->exists();
    }

    public function rate(Model $user, Model $subject, int $score): Rating
    {
        $this->guardRatingRange($score);

        $rating = Rating::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
            ],
            ['score' => $score],
        );

        event(new Rated($user, $subject, $score));

        return $rating;
    }

    public function removeRating(Model $user, Model $subject): bool
    {
        return Rating::query()
            ->where('user_id', $user->getKey())
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->delete() > 0;
    }

    public function ratingBy(Model $user, Model $subject): ?int
    {
        $score = Rating::query()
            ->where('user_id', $user->getKey())
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->value('score');

        return $score === null ? null : (int) $score;
    }

    /**
     * Whether the actor is interacting with itself. Disallowed by default (it
     * inflates counters), but can be permitted via config.
     */
    private function isSelfInteraction(Model $user, Model $subject): bool
    {
        if ((bool) config('interactions.engagement.allow_self_interaction', false)) {
            return false;
        }

        return $user->is($subject);
    }

    /**
     * Reject scores outside the configured inclusive range before they reach the
     * unsignedTinyInteger column (where out-of-range values overflow/corrupt).
     */
    private function guardRatingRange(int $score): void
    {
        $min = (int) config('interactions.engagement.rating.min', 1);
        $max = (int) config('interactions.engagement.rating.max', 5);

        if ($score < $min || $score > $max) {
            throw InvalidRatingException::outOfRange($score, $min, $max);
        }
    }
}
