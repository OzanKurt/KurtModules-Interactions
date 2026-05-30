<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Kurt\Modules\Interactions\Listeners\InteractionNotificationSubscriber;
use Kurt\Modules\Interactions\Notifications\CommentReplyNotification;
use Kurt\Modules\Interactions\Notifications\FriendRequestNotification;
use Kurt\Modules\Interactions\Notifications\MentionedNotification;
use Kurt\Modules\Interactions\Notifications\NewFollowerNotification;
use Kurt\Modules\Interactions\Tests\Stubs\Post;
use Kurt\Modules\Interactions\Tests\Stubs\User;

beforeEach(function () {
    // The subscriber is normally registered in the provider when
    // interactions.notifications.enabled is true; register it directly here so
    // the test exercises the event → notification mapping.
    Event::subscribe(InteractionNotificationSubscriber::class);
    Notification::fake();
});

it('notifies the followed user', function () {
    $alice = User::create(['name' => 'A', 'email' => 'na@x.test', 'username' => 'na']);
    $bob = User::create(['name' => 'B', 'email' => 'nb@x.test', 'username' => 'nb']);

    $alice->follow($bob);

    Notification::assertSentTo($bob, NewFollowerNotification::class);
});

it('notifies a mentioned user and the parent comment author', function () {
    $alice = User::create(['name' => 'A', 'email' => 'na2@x.test', 'username' => 'na2']);
    $bob = User::create(['name' => 'B', 'email' => 'nb2@x.test', 'username' => 'nb2']);
    $post = Post::create(['title' => 'X']);

    $root = $alice->comment($post, 'root comment');
    $bob->comment($post, 'reply pinging @na2', $root);

    Notification::assertSentTo($alice, MentionedNotification::class);
    Notification::assertSentTo($alice, CommentReplyNotification::class);
});

it('notifies the recipient of a friend request', function () {
    $alice = User::create(['name' => 'A', 'email' => 'na3@x.test', 'username' => 'na3']);
    $bob = User::create(['name' => 'B', 'email' => 'nb3@x.test', 'username' => 'nb3']);

    $alice->befriend($bob);

    Notification::assertSentTo($bob, FriendRequestNotification::class);
});
