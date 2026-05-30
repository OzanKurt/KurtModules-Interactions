<?php

declare(strict_types=1);

use Kurt\Modules\Interactions\Comments\CommentManager;
use Kurt\Modules\Interactions\Engagement\ReactionManager;
use Kurt\Modules\Interactions\Facades\Interactions;
use Kurt\Modules\Interactions\Graph\FriendshipManager;

it('exposes the managers through the Interactions facade', function () {
    expect(Interactions::reactions())->toBeInstanceOf(ReactionManager::class);
    expect(Interactions::comments())->toBeInstanceOf(CommentManager::class);
    expect(Interactions::friendships())->toBeInstanceOf(FriendshipManager::class);
});
