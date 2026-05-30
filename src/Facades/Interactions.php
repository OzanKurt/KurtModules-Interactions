<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Facades;

use Illuminate\Support\Facades\Facade;
use Kurt\Modules\Interactions\Comments\CommentManager;
use Kurt\Modules\Interactions\Engagement\InteractionManager;
use Kurt\Modules\Interactions\Engagement\ReactionManager;
use Kurt\Modules\Interactions\Graph\FriendshipManager;
use Kurt\Modules\Interactions\Graph\GroupManager;
use Kurt\Modules\Interactions\Mentions\MentionParser;

/**
 * @method static InteractionManager interactions()
 * @method static ReactionManager reactions()
 * @method static CommentManager comments()
 * @method static MentionParser mentions()
 * @method static FriendshipManager friendships()
 * @method static GroupManager groups()
 *
 * @see \Kurt\Modules\Interactions\Support\Interactions
 */
final class Interactions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Kurt\Modules\Interactions\Support\Interactions::class;
    }
}
