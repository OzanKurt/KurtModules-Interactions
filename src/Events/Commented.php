<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Events;

use Kurt\Modules\Interactions\Comments\Models\Comment;

final class Commented
{
    public function __construct(
        public readonly Comment $comment,
    ) {}
}
