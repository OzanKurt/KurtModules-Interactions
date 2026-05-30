<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Mentions\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Kurt\Modules\Interactions\Mentions\Models\Mention;

/**
 * For content that can contain @mentions (a comment, a chat message, …).
 *
 * @mixin Model
 */
trait Mentionable
{
    /**
     * @return MorphMany<Mention, $this>
     */
    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }

    /**
     * @return list<int>
     */
    public function mentionedUserIds(): array
    {
        $ids = $this->mentions()->pluck('mentioned_user_id')->all();

        return array_values(array_map(static fn ($id): int => (int) $id, $ids));
    }
}
