<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Mentions\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * A record that one user was mentioned inside some content (a comment, or any
 * host model whose body is parsed for @handles).
 *
 * @property int $id
 * @property string $mentionable_type
 * @property int $mentionable_id
 * @property int $mentioned_user_id
 * @property Carbon|null $seen_at
 * @property Carbon|null $created_at
 */
class Mention extends Model
{
    use ResolvesUser;

    public const UPDATED_AT = null;

    protected $table = 'interactions_mentions';

    /** @var list<string> */
    protected $fillable = ['mentionable_type', 'mentionable_id', 'mentioned_user_id', 'seen_at'];

    /** @var array<string, string> */
    protected $casts = [
        'seen_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function mentionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function mentionedUser(): BelongsTo
    {
        return $this->userBelongsTo('mentioned_user_id');
    }

    public function markSeen(): void
    {
        if ($this->seen_at === null) {
            $this->forceFill(['seen_at' => now()])->save();
        }
    }

    /**
     * @param  Builder<Mention>  $query
     */
    public function scopeUnseen(Builder $query): void
    {
        $query->whereNull('seen_at');
    }
}
