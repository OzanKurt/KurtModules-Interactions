<?php

declare(strict_types=1);

use Kurt\Modules\Interactions\Comments\Models\Comment;
use Kurt\Modules\Interactions\Emoji\Models\CustomEmoji;
use Kurt\Modules\Interactions\Engagement\Models\Interaction;
use Kurt\Modules\Interactions\Engagement\Models\Rating;
use Kurt\Modules\Interactions\Engagement\Models\Reaction;
use Kurt\Modules\Interactions\Graph\Models\Friendship;
use Kurt\Modules\Interactions\Graph\Models\Group;
use Kurt\Modules\Interactions\Mentions\Models\Mention;

return [

    /*
    |--------------------------------------------------------------------------
    | Mentions
    |--------------------------------------------------------------------------
    |
    | The actor user model comes from Core (kurtmodules.user_model). `pool`
    | lists the models @mentions resolve against and the column to match a
    | handle on. When `model` is omitted, the Core-resolved user model is used.
    |
    */
    'mentions' => [
        // Match @handle: the mention character followed by 1-50 handle chars.
        'pattern' => '/(?<!\\w)@([A-Za-z0-9_.\\-]{1,50})/',
        'pool' => [
            ['column' => 'username'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Engagement
    |--------------------------------------------------------------------------
    |
    | Rules for the single engagement write path (likes, votes, favorites,
    | follows, ratings, ...).
    |
    | 'allow_self_interaction' – when false (default) a user cannot
    |   follow/like/vote/favorite/... their own model; attempting to does not
    |   change any counters and raises a SelfInteractionException.
    | 'rating' – inclusive score bounds enforced by rate(); scores outside the
    |   range are rejected before they reach the storage column.
    | 'counters.driver' – how the denormalized counters are kept in step with a
    |   write (only relevant when the top-level 'counters.driver' is 'table'):
    |     'recompute' – re-run a full COUNT(*) after each mutation. O(n) per
    |        write but self-healing; the safe default.
    |     'atomic'    – in-transaction increment/decrement of the stored tally.
    |        O(1) per write; run `interactions:reconcile` periodically to bound
    |        any drift.
    |
    */
    'engagement' => [
        'allow_self_interaction' => false,
        'counters' => [
            'driver' => 'recompute',
        ],
        'rating' => [
            'min' => 1,
            'max' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reactions
    |--------------------------------------------------------------------------
    */
    'reactions' => [
        'allow_unicode' => true,
        'allow_custom' => true,
        // null = unlimited distinct emoji per user per target.
        'max_per_user' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Comments
    |--------------------------------------------------------------------------
    */
    'comments' => [
        'nesting' => true,
        'markdown' => true,
        'revisions' => true,
        // 'published' (live immediately) or 'pending' (require moderation).
        'default_status' => 'published',
    ],

    /*
    |--------------------------------------------------------------------------
    | Social graph
    |--------------------------------------------------------------------------
    */
    'graph' => [
        'friendships' => true,
        'groups' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Counters
    |--------------------------------------------------------------------------
    |
    | 'table'  – maintain a denormalized interactions_counters table (default).
    | 'none'   – skip counter maintenance; read counts via withCount on demand.
    |
    */
    'counters' => [
        'driver' => 'table',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | When enabled, the bundled Notification classes are dispatched on the
    | matching domain events. Disabled by default so the module is event-only
    | until you opt in and wire your channels.
    |
    */
    'notifications' => [
        'enabled' => false,
        'channels' => ['database'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model overrides
    |--------------------------------------------------------------------------
    */
    'models' => [
        'interaction' => Interaction::class,
        'rating' => Rating::class,
        'reaction' => Reaction::class,
        'comment' => Comment::class,
        'mention' => Mention::class,
        'friendship' => Friendship::class,
        'group' => Group::class,
        'custom_emoji' => CustomEmoji::class,
    ],
];
