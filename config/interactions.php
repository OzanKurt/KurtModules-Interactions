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
    | User model + mention pool
    |--------------------------------------------------------------------------
    |
    | `pool` lists the models @mentions resolve against and the column to match
    | a handle on (e.g. ['model' => User::class, 'column' => 'username']).
    |
    */
    'user_model' => 'App\\Models\\User',

    'mentions' => [
        // Match @handle: the mention character followed by 1-50 handle chars.
        'pattern' => '/(?<!\\w)@([A-Za-z0-9_.\\-]{1,50})/',
        'pool' => [
            ['model' => 'App\\Models\\User', 'column' => 'username'],
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
