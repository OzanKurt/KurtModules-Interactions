<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Kurt\Modules\Interactions\Engagement\CounterSync;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;
use Kurt\Modules\Interactions\Engagement\Models\Counter;
use Kurt\Modules\Interactions\Engagement\Models\Interaction;
use Kurt\Modules\Interactions\Engagement\Models\Rating;
use Kurt\Modules\Interactions\Engagement\Models\Reaction;

/**
 * Base for any model that receives engagement. Provides the polymorphic
 * relation plus count/has helpers the named target traits build on.
 *
 * @mixin Model
 */
trait HasInteractions
{
    /**
     * Purge every row keyed to this subject (interactions, ratings, reactions
     * and denormalized counters) when it is deleted, so a removed subject never
     * leaves orphaned engagement behind. The polymorphic rows carry no foreign
     * key to the host table, so this cleanup is the only thing that reclaims
     * them. Soft-deletes are skipped: the subject still exists, so its
     * engagement is only reclaimed on a real (force) delete.
     */
    public static function bootHasInteractions(): void
    {
        static::deleting(function (Model $subject): void {
            // A soft-delete leaves the subject in place; keep its engagement.
            if (method_exists($subject, 'isForceDeleting') && ! $subject->isForceDeleting()) {
                return;
            }

            $morphType = $subject->getMorphClass();
            $key = $subject->getKey();

            Interaction::query()
                ->where('subject_type', $morphType)
                ->where('subject_id', $key)
                ->delete();

            Rating::query()
                ->where('subject_type', $morphType)
                ->where('subject_id', $key)
                ->delete();

            Reaction::query()
                ->where('reactable_type', $morphType)
                ->where('reactable_id', $key)
                ->delete();

            Counter::query()
                ->where('subject_type', $morphType)
                ->where('subject_id', $key)
                ->delete();
        });
    }

    /**
     * @return MorphMany<Interaction, $this>
     */
    public function receivedInteractions(): MorphMany
    {
        return $this->morphMany(Interaction::class, 'subject');
    }

    protected function interactionCount(InteractionType $type): int
    {
        return app(CounterSync::class)->get($this, $type);
    }

    protected function hasInteractionFrom(Model $user, InteractionType $type): bool
    {
        return $this->receivedInteractions()
            ->where('type', $type->value)
            ->where('user_id', $user->getKey())
            ->exists();
    }
}
