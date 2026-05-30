<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Events;

use Kurt\Modules\Interactions\Comments\Models\Comment;

final class CommentReplied
{
    public function __construct(
        public readonly Comment $reply,
        public readonly Comment $parent,
    ) {}
}
