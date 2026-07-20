<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\Interactions\Engagement\Models\Counter;
use Kurt\Modules\Interactions\Engagement\Models\Interaction;

/**
 * Rewrites every denormalized counter from a live COUNT(*) over the
 * interactions table. Run periodically when the atomic counter driver is in use
 * to bound any drift, or after a bulk import that bypassed the write path.
 */
final class ReconcileCountersCommand extends Command
{
    protected $signature = 'interactions:reconcile';

    protected $description = 'Rewrite all engagement counters from live interaction counts.';

    public function handle(): int
    {
        $table = (new Interaction)->getTable();

        $groups = DB::table($table)
            ->selectRaw('subject_type, subject_id, type, COUNT(*) as aggregate')
            ->groupBy('subject_type', 'subject_id', 'type')
            ->get();

        DB::transaction(function () use ($groups): void {
            // Zero everything first so counters with no surviving interactions
            // (fully removed subjects, drifted atomic rows) settle back to 0.
            Counter::query()->update(['count' => 0, 'updated_at' => now()]);

            foreach ($groups as $group) {
                Counter::query()->updateOrCreate(
                    [
                        'subject_type' => $group->subject_type,
                        'subject_id' => $group->subject_id,
                        'type' => $group->type,
                    ],
                    ['count' => (int) $group->aggregate],
                );
            }
        });

        $this->info("Reconciled {$groups->count()} counter(s) from live interaction counts.");

        return self::SUCCESS;
    }
}
