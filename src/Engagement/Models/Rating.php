<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;

/**
 * One user's score for one subject. Unique (user, subject) — a user has a
 * single, updatable rating per subject; the subject's rating is the average.
 *
 * @property int $id
 * @property int $user_id
 * @property string $subject_type
 * @property int $subject_id
 * @property int $score
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Rating extends Model
{
    use ResolvesUser;

    protected $table = 'interactions_ratings';

    /** @var list<string> */
    protected $fillable = ['user_id', 'subject_type', 'subject_id', 'score'];

    /** @var array<string, string> */
    protected $casts = [
        'score' => 'integer',
    ];

    /**
     * @return BelongsTo<Model, $this>
     */
    public function user(): BelongsTo
    {
        return $this->userBelongsTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
