<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Concerns;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;

/**
 * @mixin Model
 */
trait Followable
{
    use HasInteractions;

    public function followersCount(): int
    {
        return $this->interactionCount(InteractionType::Follow);
    }

    public function isFollowedBy(Model $user): bool
    {
        return $this->hasInteractionFrom($user, InteractionType::Follow);
    }
}
