<?php

declare(strict_types=1);

use Kurt\Modules\Interactions\Graph\GroupManager;
use Kurt\Modules\Interactions\Tests\Stubs\User;

function member(string $name): User
{
    return User::create([
        'name' => ucfirst($name),
        'email' => $name.'@group.test',
        'username' => $name.'grp',
    ]);
}

it('creates an owner-scoped friend group with a slug', function () {
    $owner = member('owner');

    $group = $owner->createFriendGroup('Close Friends');

    expect($group->slug)->toStartWith('close-friends-');
    expect($group->user_id)->toBe($owner->id);
});

it('adds and removes members idempotently', function () {
    $owner = member('owner2');
    $f1 = member('f1');
    $f2 = member('f2');
    $manager = app(GroupManager::class);

    $group = $owner->createFriendGroup('Work');
    $manager->addMember($group, $f1);
    $manager->addMember($group, $f2);
    $manager->addMember($group, $f1); // idempotent

    expect($group->memberIds())->toHaveCount(2);

    expect($manager->removeMember($group, $f1))->toBeTrue();
    expect($group->fresh()->memberIds())->toBe([$f2->id]);
});
