<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a rating score falls outside the configured inclusive range
 * (config('interactions.engagement.rating.min'/'max')). Guarding here prevents
 * out-of-range values from overflowing the unsignedTinyInteger score column and
 * corrupting averageRating().
 */
final class InvalidRatingException extends InvalidArgumentException
{
    public static function outOfRange(int $score, int $min, int $max): self
    {
        return new self("Rating score {$score} is out of range; expected {$min}..{$max}.");
    }
}
