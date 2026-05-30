<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Comments\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Kurt\Modules\Interactions\Comments\Enums\CommentStatus;
use Kurt\Modules\Interactions\Comments\Models\Comment;

/**
 * @mixin Model
 */
trait Commentable
{
    /**
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function commentsCount(): int
    {
        return $this->comments()->where('status', CommentStatus::Published->value)->count();
    }
}
