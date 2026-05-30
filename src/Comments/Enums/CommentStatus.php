<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Comments\Enums;

enum CommentStatus: string
{
    case Published = 'published';
    case Pending = 'pending';
    case Spam = 'spam';

    public function isVisible(): bool
    {
        return $this === self::Published;
    }
}
