<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Concerns;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;

/**
 * @mixin Model
 */
trait Likeable
{
    use HasInteractions;

    public function likesCount(): int
    {
        return $this->interactionCount(InteractionType::Like);
    }

    public function dislikesCount(): int
    {
        return $this->interactionCount(InteractionType::Dislike);
    }

    public function isLikedBy(Model $user): bool
    {
        return $this->hasInteractionFrom($user, InteractionType::Like);
    }

    public function isDislikedBy(Model $user): bool
    {
        return $this->hasInteractionFrom($user, InteractionType::Dislike);
    }
}
