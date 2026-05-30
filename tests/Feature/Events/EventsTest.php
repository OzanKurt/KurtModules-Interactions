<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Interactions\Events\Commented;
use Kurt\Modules\Interactions\Events\CommentReplied;
use Kurt\Modules\Interactions\Events\Followed;
use Kurt\Modules\Interactions\Events\FriendRequestAccepted;
use Kurt\Modules\Interactions\Events\FriendRequested;
use Kurt\Modules\Interactions\Events\Liked;
use Kurt\Modules\Interactions\Events\Rated;
use Kurt\Modules\Interactions\Events\Reacted;
use Kurt\Modules\Interactions\Events\UserMentioned;
use Kurt\Modules\Interactions\Events\Voted;
use Kurt\Modules\Interactions\Tests\Stubs\Post;
use Kurt\Modules\Interactions\Tests\Stubs\User;

it('dispatches an event for every interaction verb', function () {
    Event::fake([
        Followed::class, Liked::class, Voted::class, Rated::class, Reacted::class,
        Commented::class, CommentReplied::class, UserMentioned::class,
        FriendRequested::class, FriendRequestAccepted::class,
    ]);

    $alice = User::create(['name' => 'A', 'email' => 'eva@x.test', 'username' => 'eva']);
    $bob = User::create(['name' => 'B', 'email' => 'evb@x.test', 'username' => 'evb']);
    $post = Post::create(['title' => 'X']);

    $alice->like($post);
    $alice->upvote($post);
    $alice->rate($post, 5);
    $alice->reactWith($post, '🎉');
    $alice->follow($bob);
    $root = $alice->comment($post, 'hi @evb');
    $alice->comment($post, 'a reply', $root);
    $alice->befriend($bob);
    $bob->acceptFriendRequest($alice);

    Event::assertDispatched(Liked::class);
    Event::assertDispatched(Voted::class);
    Event::assertDispatched(Rated::class);
    Event::assertDispatched(Reacted::class);
    Event::assertDispatched(Followed::class);
    Event::assertDispatched(Commented::class);
    Event::assertDispatched(CommentReplied::class);
    Event::assertDispatched(UserMentioned::class);
    Event::assertDispatched(FriendRequested::class);
    Event::assertDispatched(FriendRequestAccepted::class);
});
