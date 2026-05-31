<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Comments\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Interactions\Comments\CommentRenderer;
use Kurt\Modules\Interactions\Comments\Enums\CommentStatus;
use Kurt\Modules\Interactions\Engagement\Concerns\Reactable;
use Kurt\Modules\Interactions\Mentions\Concerns\Mentionable;

/**
 * A polymorphic, threaded comment. Bodies are markdown carrying @mentions and
 * emoji; a comment is itself Reactable and Mentionable.
 *
 * @property int $id
 * @property int $user_id
 * @property string $commentable_type
 * @property int $commentable_id
 * @property int|null $parent_id
 * @property string $body
 * @property CommentStatus $status
 * @property int|null $moderated_by
 * @property Carbon|null $moderated_at
 * @property Carbon|null $edited_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Comment extends Model
{
    use Mentionable;
    use Reactable;
    use ResolvesUser;
    use SoftDeletes;

    protected $table = 'interactions_comments';

    /** @var list<string> */
    protected $fillable = ['user_id', 'commentable_type', 'commentable_id', 'parent_id', 'body', 'status', 'moderated_by', 'moderated_at', 'edited_at'];

    /** @var array<string, string> */
    protected $casts = [
        'status' => CommentStatus::class,
        'moderated_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Model, $this>
     */
    public function author(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    /**
     * The user who last moderated this comment (approved / marked spam / etc.).
     *
     * @return BelongsTo<Model, $this>
     */
    public function moderatedBy(): BelongsTo
    {
        return $this->userBelongsTo('moderated_by');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return HasMany<CommentRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(CommentRevision::class);
    }

    public function renderedBody(): string
    {
        return app(CommentRenderer::class)->toHtml($this->body);
    }

    /**
     * @param  Builder<Comment>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('status', CommentStatus::Published->value);
    }
}
