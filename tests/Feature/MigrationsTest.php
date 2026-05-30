<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates the engagement tables', function () {
    foreach (['interactions_interactions', 'interactions_ratings', 'interactions_counters'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("missing {$table}");
    }
});
