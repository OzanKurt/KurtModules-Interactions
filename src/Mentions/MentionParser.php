<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Mentions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Core\Contracts\UserResolver;
use Kurt\Modules\Interactions\Mentions\Models\Mention;

/**
 * Extracts @handles from text and resolves them against the configured pool of
 * models (default: the Core user model by `username`). syncFor() persists the
 * resolved mentions for a piece of content.
 */
final class MentionParser
{
    /**
     * @return array<int, Model> matched models keyed by primary key
     */
    public function parse(string $text): array
    {
        $pattern = (string) config('interactions.mentions.pattern', '/(?<!\w)@([A-Za-z0-9_.\-]{1,50})/');

        if (preg_match_all($pattern, $text, $matches) === false) {
            return [];
        }

        $handles = array_values(array_unique($matches[1] ?? []));

        if ($handles === []) {
            return [];
        }

        $users = [];
        $pool = config('interactions.mentions.pool', []);

        if (! is_array($pool)) {
            return [];
        }

        foreach ($pool as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $column = is_string($entry['column'] ?? null) ? $entry['column'] : 'username';
            $model = is_string($entry['model'] ?? null) ? $entry['model'] : null;

            foreach ($this->query($model)->whereIn($column, $handles)->get() as $user) {
                $users[(int) $user->getKey()] = $user;
            }
        }

        return array_values($users);
    }

    /**
     * @return array<int, Model> the users that were mentioned
     */
    public function syncFor(Model $mentionable, string $text): array
    {
        $users = $this->parse($text);
        $ids = array_map(static fn (Model $user): mixed => $user->getKey(), $users);

        $stale = Mention::query()
            ->where('mentionable_type', $mentionable->getMorphClass())
            ->where('mentionable_id', $mentionable->getKey());

        if ($ids !== []) {
            $stale->whereNotIn('mentioned_user_id', $ids);
        }

        $stale->delete();

        foreach ($users as $user) {
            Mention::query()->updateOrCreate([
                'mentionable_type' => $mentionable->getMorphClass(),
                'mentionable_id' => $mentionable->getKey(),
                'mentioned_user_id' => $user->getKey(),
            ]);
        }

        return $users;
    }

    /**
     * @return Builder<Model>
     */
    private function query(?string $model): Builder
    {
        if ($model !== null && is_subclass_of($model, Model::class)) {
            return $model::query();
        }

        return app(UserResolver::class)->newQuery();
    }
}
