<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Kurt\Modules\Core\Concerns\ResolvesUser;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;

/**
 * A single polymorphic engagement row: one user's like / dislike / vote /
 * favorite / subscribe / follow on one subject. `value` carries a vote weight
 * (±1) and is null for the valueless types. The unique
 * (user, subject, type) index makes every verb idempotent.
 *
 * @property int $id
 * @property int $user_id
 * @property string $subject_type
 * @property int $subject_id
 * @property InteractionType $type
 * @property int|null $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Interaction extends Model
{
    use ResolvesUser;

    protected $table = 'interactions_interactions';

    /** @var list<string> */
    protected $fillable = ['user_id', 'subject_type', 'subject_id', 'type', 'value'];

    /** @var array<string, string> */
    protected $casts = [
        'type' => InteractionType::class,
        'value' => 'integer',
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

    /**
     * @param  Builder<Interaction>  $query
     */
    public function scopeOfType(Builder $query, InteractionType $type): void
    {
        $query->where('type', $type->value);
    }
}
