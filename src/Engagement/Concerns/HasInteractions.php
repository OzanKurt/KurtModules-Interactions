<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Kurt\Modules\Interactions\Engagement\CounterSync;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;
use Kurt\Modules\Interactions\Engagement\Models\Interaction;

/**
 * Base for any model that receives engagement. Provides the polymorphic
 * relation plus count/has helpers the named target traits build on.
 *
 * @mixin Model
 */
trait HasInteractions
{
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
