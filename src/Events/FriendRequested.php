<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Events;

use Illuminate\Database\Eloquent\Model;

final class FriendRequested
{
    public function __construct(
        public readonly Model $sender,
        public readonly Model $recipient,
    ) {}
}
