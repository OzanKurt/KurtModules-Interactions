<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Engagement\Enums\InteractionType;
use Kurt\Modules\Interactions\Engagement\Models\Counter;
use Kurt\Modules\Interactions\Engagement\Models\Interaction;

/**
 * Keeps the denormalized interactions_counters table in step with the
 * interactions table. When the driver is 'none' the counter table is skipped
 * and reads fall back to a live aggregate query.
 */
final class CounterSync
{
    public function sync(Model $subject, InteractionType $type): void
    {
        if (config('interactions.counters.driver') !== 'table') {
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
        if (config('interactions.counters.driver') === 'table') {
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

    private function liveCount(Model $subject, InteractionType $type): int
    {
        return Interaction::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('type', $type->value)
            ->count();
    }
}
