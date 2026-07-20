<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Exceptions;

use InvalidArgumentException;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;

/**
 * Thrown when a user attempts to interact with themselves (e.g. self-follow,
 * self-like, self-vote), which would inflate engagement counters.
 *
 * Self-interaction can be permitted by setting
 * config('interactions.engagement.allow_self_interaction') to true.
 */
final class SelfInteractionException extends InvalidArgumentException
{
    public static function for(InteractionType $type): self
    {
        return new self("A user cannot {$type->value} themselves.");
    }

    public static function forRating(): self
    {
        return new self('A user cannot rate themselves.');
    }
}
