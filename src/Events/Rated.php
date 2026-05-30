<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Events;

use Illuminate\Database\Eloquent\Model;

final class Rated
{
    public function __construct(
        public readonly Model $user,
        public readonly Model $subject,
        public readonly int $score,
    ) {}
}
