<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;
use Kurt\Modules\Interactions\Engagement\Models\Counter;
use Kurt\Modules\Interactions\Engagement\Models\Interaction;

/**
 * Keeps the denormalized interactions_counters table in step with the
 * interactions table. When the top-level counters driver is not 'table' the
 * counter table is skipped and reads fall back to a live aggregate query.
 *
 * Two write strategies are supported via `interactions.engagement.counters.driver`:
 *   - 'recompute' (default): re-run a full COUNT(*) after each mutation. O(n)
 *     per write but self-healing.
 *   - 'atomic': in-transaction increment/decrement of the stored tally. O(1)
 *     per write; bounded against drift by the interactions:reconcile command.
 */
final class CounterSync
{
    /**
     * Record a newly created interaction against the counter for (subject, type).
     */
    public function increment(Model $subject, InteractionType $type): void
    {
        $this->apply($subject, $type, 1);
    }

    /**
     * Record a removed interaction against the counter for (subject, type).
     */
    public function decrement(Model $subject, InteractionType $type): void
    {
        $this->apply($subject, $type, -1);
    }

    /**
     * Rewrite the counter for (subject, type) from a live COUNT(*). This is the
     * self-healing path used by the recompute driver and the reconcile command.
     */
    public function sync(Model $subject, InteractionType $type): void
    {
        if (! $this->maintainsTable()) {
            return;
        }

        Counter::query()->updateOrCreate(
            [
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'type' => $type->value,
            ],
            ['count' => $this->liveCount($subject, $type)],
        );
    }

    public function get(Model $subject, InteractionType $type): int
    {
        if ($this->maintainsTable()) {
            $count = Counter::query()
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', $subject->getKey())
                ->where('type', $type->value)
                ->value('count');

            if ($count !== null) {
                return (int) $count;
            }
        }

        return $this->liveCount($subject, $type);
    }

    /**
     * Dispatch a mutation to the configured write strategy. The recompute driver
     * ignores the delta and re-counts; the atomic driver applies the delta in a
     * transaction.
     */
    private function apply(Model $subject, InteractionType $type, int $delta): void
    {
        if (! $this->maintainsTable()) {
            return;
        }

        if ($this->isAtomic()) {
            $this->applyDelta($subject, $type, $delta);

            return;
        }

        $this->sync($subject, $type);
    }

    /**
     * O(1) in-transaction adjustment of the stored tally. Falls back to creating
     * the row when it does not exist yet.
     */
    private function applyDelta(Model $subject, InteractionType $type, int $delta): void
    {
        $keys = [
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'type' => $type->value,
        ];

        DB::transaction(function () use ($keys, $delta): void {
            $query = Counter::query()->where($keys);

            $affected = $delta >= 0
                ? $query->increment('count', $delta, ['updated_at' => now()])
                : $query->decrement('count', -$delta, ['updated_at' => now()]);

            if ($affected === 0) {
                Counter::query()->create($keys + ['count' => max(0, $delta)]);
            }
        });
    }

    private function liveCount(Model $subject, InteractionType $type): int
    {
        return Interaction::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('type', $type->value)
            ->count();
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
