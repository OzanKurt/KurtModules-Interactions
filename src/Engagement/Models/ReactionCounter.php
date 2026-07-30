<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Denormalized per-(reactable, emoji) tally maintained by ReactionCounterSync so
 * ReactionManager::summary() can serve per-emoji counts without an aggregate
 * groupBy over the reactions table on every read.
 *
 * @property int $id
 * @property string $reactable_type
 * @property int $reactable_id
 * @property string $emoji
 * @property int $count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ReactionCounter extends Model
{
    protected $table = 'interactions_reaction_counts';

    /** @var list<string> */
    protected $fillable = ['reactable_type', 'reactable_id', 'emoji', 'count'];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'integer',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }
}
