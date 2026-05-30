<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Exceptions;

use RuntimeException;

final class InvalidReaction extends RuntimeException
{
    public static function empty(): self
    {
        return new self('A reaction emoji cannot be empty.');
    }

    public static function unicodeDisabled(): self
    {
        return new self('Unicode emoji reactions are disabled.');
    }

    public static function customDisabled(): self
    {
        return new self('Custom emoji reactions are disabled.');
    }

    public static function unknownCustom(string $emoji): self
    {
        return new self("Unknown or inactive custom emoji [{$emoji}].");
    }

    public static function maxReached(int $max): self
    {
        return new self("You may use at most {$max} distinct reaction(s) here.");
    }
}
