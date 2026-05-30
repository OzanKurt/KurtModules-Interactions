<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Concerns;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;

/**
 * @mixin Model
 */
trait Voteable
{
    use HasInteractions;

    public function votesCount(): int
    {
        return $this->interactionCount(InteractionType::Vote);
    }

    /** Net score = sum of vote weights (+1 up, -1 down). */
    public function votesScore(): int
    {
        return (int) $this->receivedInteractions()
            ->where('type', InteractionType::Vote->value)
            ->sum('value');
    }

    public function isVotedBy(Model $user): bool
    {
        return $this->hasInteractionFrom($user, InteractionType::Vote);
    }
}
