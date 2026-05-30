<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Concerns;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;

/**
 * @mixin Model
 */
trait Subscribable
{
    use HasInteractions;

    public function subscribersCount(): int
    {
        return $this->interactionCount(InteractionType::Subscribe);
    }

    public function isSubscribedBy(Model $user): bool
    {
        return $this->hasInteractionFrom($user, InteractionType::Subscribe);
    }
}
