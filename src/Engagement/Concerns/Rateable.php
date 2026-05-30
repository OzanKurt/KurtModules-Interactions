<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Kurt\Modules\Interactions\Engagement\Models\Rating;

/**
 * @mixin Model
 */
trait Rateable
{
    /**
     * @return MorphMany<Rating, $this>
     */
    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'subject');
    }

    public function averageRating(): ?float
    {
        $average = $this->ratings()->avg('score');

        return $average === null ? null : (float) $average;
    }

    public function ratingsCount(): int
    {
        return $this->ratings()->count();
    }

    public function ratingBy(Model $user): ?int
    {
        $score = $this->ratings()->where('user_id', $user->getKey())->value('score');

        return $score === null ? null : (int) $score;
    }
}
