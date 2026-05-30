<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Kurt\Modules\Interactions\Engagement\Models\Reaction;
use Kurt\Modules\Interactions\Engagement\ReactionManager;

/**
 * @mixin Model
 */
trait Reactable
{
    /**
     * @return MorphMany<Reaction, $this>
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    /**
     * Per-emoji counts, e.g. ['🎉' => 5, ':party:' => 2].
     *
     * @return array<string, int>
     */
    public function reactionSummary(): array
    {
        return app(ReactionManager::class)->summary($this);
    }

    public function reactionCount(string $emoji): int
    {
        return $this->reactions()->where('emoji', $emoji)->count();
    }

    public function isReactedWithBy(Model $user, string $emoji): bool
    {
        return $this->reactions()
            ->where('emoji', $emoji)
            ->where('user_id', $user->getKey())
            ->exists();
    }
}
