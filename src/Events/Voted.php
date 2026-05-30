<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Events;

use Illuminate\Database\Eloquent\Model;

final class Voted
{
    public function __construct(
        public readonly Model $user,
        public readonly Model $subject,
        public readonly int $value,
    ) {}
}
