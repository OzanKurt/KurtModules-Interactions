<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Interactions\Engagement\Models\Reaction;
use Kurt\Modules\Interactions\Engagement\Models\ReactionCounter;

/**
 * Keeps the denormalized interactions_reaction_counts table in step with the
 * reactions table, so ReactionManager::summary() reads a cached per-emoji tally
 * instead of a live groupBy aggregate. This mirrors CounterSync for engagement
 * and honours the same knobs:
 *
 *   - top-level `interactions.counters.driver`: when not 'table' the cache is
 *     skipped entirely and summary() falls back to a live aggregate query.
 *   - `interactions.engagement.counters.driver`: the write strategy shared with
 *     engagement counters.
 *       'recompute' (default) – re-run a scoped COUNT(*) after each mutation.
 *          O(n) per write but self-healing.
 *       'atomic'    – in-transaction increment/decrement of the stored tally.
 *          O(1) per write; bounded against drift by interactions:reconcile.
 */
final class ReactionCounterSync
{
    /**
     * Record a newly added reaction against the counter for (reactable, emoji).
     */
    public function increment(Model $reactable, string $emoji): void
    {
        $this->apply($reactable, $emoji, 1);
    }

    /**
     * Record a removed reaction against the counter for (reactable, emoji).
     */
    public function decrement(Model $reactable, string $emoji): void
    {
        $this->apply($reactable, $emoji, -1);
    }

    /**
     * Rewrite the counter for (reactable, emoji) from a live COUNT(*). This is
     * the self-healing path used by the recompute driver.
     */
    public function sync(Model $reactable, string $emoji): void
    {
        if (! $this->maintainsTable()) {
            return;
        }

        ReactionCounter::query()->updateOrCreate(
            [
                'reactable_type' => $reactable->getMorphClass(),
                'reactable_id' => $reactable->getKey(),
                'emoji' => $emoji,
            ],
            ['count' => $this->liveCount($reactable, $emoji)],
        );
    }

    /**
     * Cached per-emoji counts for a reactable, e.g. ['🎉' => 5, ':party:' => 2].
     * When the cache is not maintained this recomputes live so the return shape
     * is identical either way. Zero-valued rows are omitted, matching the live
     * aggregate which only ever surfaces emoji that are actually held.
     *
     * @return array<string, int>
     */
    public function summary(Model $reactable): array
    {
        if (! $this->maintainsTable()) {
            return $this->liveSummary($reactable);
        }

        $counts = ReactionCounter::query()
            ->where('reactable_type', $reactable->getMorphClass())
            ->where('reactable_id', $reactable->getKey())
            ->where('count', '>', 0)
            ->pluck('count', 'emoji');

        $summary = [];

        foreach ($counts as $emoji => $count) {
            $summary[(string) $emoji] = (int) $count;
        }

        return $summary;
    }

    /**
     * Rebuild every reaction counter from a live aggregate over the reactions
     * table. Called by interactions:reconcile to bound atomic drift or repair
     * rows after a bulk import that bypassed the write path.
     *
     * @return int the number of (reactable, emoji) groups reconciled
     */
    public function reconcile(): int
    {
        $table = (new Reaction)->getTable();

        $groups = DB::table($table)
            ->selectRaw('reactable_type, reactable_id, emoji, COUNT(*) as aggregate')
            ->groupBy('reactable_type', 'reactable_id', 'emoji')
            ->get();

        DB::transaction(function () use ($groups): void {
            // Zero everything first so counters whose reactions were all removed
            // (or drifted atomic rows) settle back to 0.
            ReactionCounter::query()->update(['count' => 0, 'updated_at' => now()]);

            foreach ($groups as $group) {
                ReactionCounter::query()->updateOrCreate(
                    [
                        'reactable_type' => $group->reactable_type,
                        'reactable_id' => $group->reactable_id,
                        'emoji' => $group->emoji,
                    ],
                    ['count' => (int) $group->aggregate],
                );
            }
        });

        return $groups->count();
    }

    /**
     * Dispatch a mutation to the configured write strategy. The recompute driver
     * ignores the delta and re-counts; the atomic driver applies the delta in a
     * transaction.
     */
    private function apply(Model $reactable, string $emoji, int $delta): void
    {
        if (! $this->maintainsTable()) {
            return;
        }

        if ($this->isAtomic()) {
            $this->applyDelta($reactable, $emoji, $delta);

            return;
        }

        $this->sync($reactable, $emoji);
    }

    /**
     * O(1) in-transaction adjustment of the stored tally. Falls back to creating
     * the row when it does not exist yet.
     */
    private function applyDelta(Model $reactable, string $emoji, int $delta): void
    {
        $keys = [
            'reactable_type' => $reactable->getMorphClass(),
            'reactable_id' => $reactable->getKey(),
            'emoji' => $emoji,
        ];

        DB::transaction(function () use ($keys, $delta): void {
            $affected = ReactionCounter::query()
                ->where($keys)
                ->update([
                    'count' => DB::raw('count + ('.$delta.')'),
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                ReactionCounter::query()->create($keys + ['count' => max(0, $delta)]);
            }
        });
    }

    private function liveCount(Model $reactable, string $emoji): int
    {
        return Reaction::query()
            ->where('reactable_type', $reactable->getMorphClass())
            ->where('reactable_id', $reactable->getKey())
            ->where('emoji', $emoji)
            ->count();
    }

    /**
     * @return array<string, int>
     */
    private function liveSummary(Model $reactable): array
    {
        $counts = Reaction::query()
            ->where('reactable_type', $reactable->getMorphClass())
            ->where('reactable_id', $reactable->getKey())
            ->groupBy('emoji')
            ->selectRaw('emoji, COUNT(*) as aggregate')
            ->pluck('aggregate', 'emoji');

        $summary = [];

        foreach ($counts as $emoji => $count) {
            $summary[(string) $emoji] = (int) $count;
        }

        return $summary;
    }

    private function maintainsTable(): bool
    {
        return config('interactions.counters.driver') === 'table';
    }

    private function isAtomic(): bool
    {
        return config('interactions.engagement.counters.driver', 'recompute') === 'atomic';
    }
}
