<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Events;

use Illuminate\Database\Eloquent\Model;

final class Reacted
{
    public function __construct(
        public readonly Model $user,
        public readonly Model $reactable,
        public readonly string $emoji,
    ) {}
}
