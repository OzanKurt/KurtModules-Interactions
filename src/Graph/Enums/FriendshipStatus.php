<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Graph\Enums;

enum FriendshipStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Denied = 'denied';
    case Blocked = 'blocked';
}
