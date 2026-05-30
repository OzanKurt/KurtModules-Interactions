<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Concerns;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;

/**
 * @mixin Model
 */
trait Favoritable
{
    use HasInteractions;

    public function favoritesCount(): int
    {
        return $this->interactionCount(InteractionType::Favorite);
    }

    public function isFavoritedBy(Model $user): bool
    {
        return $this->hasInteractionFrom($user, InteractionType::Favorite);
    }
}
