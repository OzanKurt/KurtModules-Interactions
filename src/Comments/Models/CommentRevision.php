<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Comments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An append-only previous-body snapshot for a comment.
 *
 * @property int $id
 * @property int $comment_id
 * @property string $body
 * @property int|null $edited_by
 * @property Carbon|null $created_at
 */
class CommentRevision extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'interactions_comment_revisions';

    /** @var list<string> */
    protected $fillable = ['comment_id', 'body', 'edited_by'];

    /** @var array<string, string> */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }
}
