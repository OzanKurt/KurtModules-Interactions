<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Events;

use Illuminate\Database\Eloquent\Model;

final class Followed
{
    public function __construct(
        public readonly Model $follower,
        public readonly Model $followed,
    ) {}
}
