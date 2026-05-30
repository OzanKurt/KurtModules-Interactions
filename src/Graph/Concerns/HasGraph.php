<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Graph\Concerns;

use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Interactions\Graph\FriendshipManager;
use Kurt\Modules\Interactions\Graph\GroupManager;
use Kurt\Modules\Interactions\Graph\Models\Friendship;
use Kurt\Modules\Interactions\Graph\Models\Group;

/**
 * Social-graph actor verbs: friend requests, blocking and friend groups.
 * Folded into Interactor so a user gains them alongside the engagement verbs.
 *
 * @mixin Model
 */
trait HasGraph
{
    protected function friendshipManager(): FriendshipManager
    {
        return app(FriendshipManager::class);
    }

    protected function groupManager(): GroupManager
    {
        return app(GroupManager::class);
    }

    public function befriend(Model $user): Friendship
    {
        return $this->friendshipManager()->befriend($this, $user);
    }

    public function acceptFriendRequest(Model $user): bool
    {
        return $this->friendshipManager()->accept($this, $user);
    }

    public function denyFriendRequest(Model $user): bool
    {
        return $this->friendshipManager()->deny($this, $user);
    }

    public function unfriend(Model $user): bool
    {
        return $this->friendshipManager()->unfriend($this, $user);
    }

    public function blockFriend(Model $user): Friendship
    {
        return $this->friendshipManager()->block($this, $user);
    }

    public function isFriendWith(Model $user): bool
    {
        return $this->friendshipManager()->areFriends($this, $user);
    }

    public function createFriendGroup(string $name): Group
    {
        return $this->groupManager()->create($this, $name);
    }
}
