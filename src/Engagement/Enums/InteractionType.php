<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Engagement\Enums;

enum InteractionType: string
{
    case Like = 'like';
    case Dislike = 'dislike';
    case Vote = 'vote';
    case Favorite = 'favorite';
    case Subscribe = 'subscribe';
    case Follow = 'follow';
}
