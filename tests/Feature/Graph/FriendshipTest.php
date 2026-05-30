<?php

declare(strict_types=1);

use Kurt\Modules\Interactions\Graph\Enums\FriendshipStatus;
use Kurt\Modules\Interactions\Graph\Models\Friendship;
use Kurt\Modules\Interactions\Tests\Stubs\User;

function person(string $name): User
{
    return User::create([
        'name' => ucfirst($name),
        'email' => $name.'@graph.test',
        'username' => $name.'g',
    ]);
}

it('sends and accepts a friend request, becoming mutual', function () {
    $alice = person('alice');
    $bob = person('bob');

    $alice->befriend($bob);
    expect($alice->isFriendWith($bob))->toBeFalse(); // still pending

    expect($bob->acceptFriendRequest($alice))->toBeTrue();
    expect($alice->isFriendWith($bob))->toBeTrue();
    expect($bob->isFriendWith($alice))->toBeTrue();
});

it('denies a friend request', function () {
    $alice = person('alice2');
    $bob = person('bob2');

    $alice->befriend($bob);
    expect($bob->denyFriendRequest($alice))->toBeTrue();
    expect($alice->isFriendWith($bob))->toBeFalse();
});

it('unfriends in either direction', function () {
    $alice = person('alice3');
    $bob = person('bob3');

    $alice->befriend($bob);
    $bob->acceptFriendRequest($alice);

    expect($bob->unfriend($alice))->toBeTrue();
    expect($alice->isFriendWith($bob))->toBeFalse();
});

it('blocks a user', function () {
    $alice = person('alice4');
    $bob = person('bob4');

    $alice->blockFriend($bob);

    expect($alice->isFriendWith($bob))->toBeFalse();
    expect(Friendship::query()->where('status', FriendshipStatus::Blocked->value)->count())->toBe(1);
});
