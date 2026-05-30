<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Support;

use Kurt\Modules\Interactions\Comments\CommentManager;
use Kurt\Modules\Interactions\Engagement\InteractionManager;
use Kurt\Modules\Interactions\Engagement\ReactionManager;
use Kurt\Modules\Interactions\Graph\FriendshipManager;
use Kurt\Modules\Interactions\Graph\GroupManager;
use Kurt\Modules\Interactions\Mentions\MentionParser;

/**
 * Aggregate entry point exposed via the Interactions facade, handing back the
 * underlying managers so host code can drive the module without resolving each
 * one by hand: Interactions::reactions()->react(...), etc.
 */
final class Interactions
{
    public function __construct(
        private readonly InteractionManager $interactions,
        private readonly ReactionManager $reactions,
        private readonly CommentManager $comments,
        private readonly MentionParser $mentions,
        private readonly FriendshipManager $friendships,
        private readonly GroupManager $groups,
    ) {}

    public function interactions(): InteractionManager
    {
        return $this->interactions;
    }

    public function reactions(): ReactionManager
    {
        return $this->reactions;
    }

    public function comments(): CommentManager
    {
        return $this->comments;
    }

    public function mentions(): MentionParser
    {
        return $this->mentions;
    }

    public function friendships(): FriendshipManager
    {
        return $this->friendships;
    }

    public function groups(): GroupManager
    {
        return $this->groups;
    }
}
