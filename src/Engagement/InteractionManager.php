<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;
use Kurt\Modules\Interactions\Engagement\Models\Interaction;
use Kurt\Modules\Interactions\Engagement\Models\Rating;

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
        return Rating::query()->updateOrCreate(
            [
                'user_id' => $user->getKey(),
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
            ],
            ['score' => $score],
        );
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
}
