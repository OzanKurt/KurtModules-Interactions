<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Events;

use Illuminate\Database\Eloquent\Model;

final class UserMentioned
{
    public function __construct(
        public readonly Model $mentioned,
        public readonly Model $source,
    ) {}
}
