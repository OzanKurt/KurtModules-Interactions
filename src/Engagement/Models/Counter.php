<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Denormalized per-(subject, type) tally maintained by CounterSync so hosts can
 * read engagement totals without an aggregate query and without adding columns
 * to their own tables.
 *
 * @property int $id
 * @property string $subject_type
 * @property int $subject_id
 * @property string $type
 * @property int $count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Counter extends Model
{
    protected $table = 'interactions_counters';

    /** @var list<string> */
    protected $fillable = ['subject_type', 'subject_id', 'type', 'count'];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'integer',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
