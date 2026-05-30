<?php

declare(strict_types=1);

use Kurt\Modules\Interactions\Engagement\Models\Counter;
use Kurt\Modules\Interactions\Tests\Stubs\Post;
use Kurt\Modules\Interactions\Tests\Stubs\User;

function makeUser(string $name = 'Alice'): User
{
    return User::create([
        'name' => $name,
        'email' => strtolower($name).'@example.test',
        'username' => strtolower($name),
    ]);
}

function makePost(): Post
{
    return Post::create(['title' => 'Hello']);
}

it('likes and unlikes with idempotent counts', function () {
    $user = makeUser();
    $post = makePost();

    $user->like($post);
    $user->like($post); // idempotent

    expect($post->likesCount())->toBe(1);
    expect($post->isLikedBy($user))->toBeTrue();
    expect($user->hasLiked($post))->toBeTrue();

    expect($user->unlike($post))->toBeTrue();
    expect($post->fresh()->likesCount())->toBe(0);
});

it('makes like and dislike mutually exclusive', function () {
    $user = makeUser();
    $post = makePost();

    $user->dislike($post);
    expect($post->dislikesCount())->toBe(1);

    $user->like($post);
    expect($post->likesCount())->toBe(1);
    expect($post->dislikesCount())->toBe(0);
});

it('votes up/down with a net score and cancel', function () {
    $alice = makeUser('Alice');
    $bob = makeUser('Bob');
    $post = makePost();

    $alice->upvote($post);
    $bob->downvote($post);
    expect($post->votesCount())->toBe(2);
    expect($post->votesScore())->toBe(0);

    $bob->upvote($post); // switch Bob to up
    expect($post->votesScore())->toBe(2);

    expect($alice->cancelVote($post))->toBeTrue();
    expect($post->fresh()->votesCount())->toBe(1);
});

it('favorites, subscribes and follows', function () {
    $user = makeUser();
    $post = makePost();

    $user->favorite($post);
    $user->subscribe($post);
    $user->follow($post);

    expect($post->favoritesCount())->toBe(1);
    expect($post->subscribersCount())->toBe(1);
    expect($post->followersCount())->toBe(1);
    expect($post->isFollowedBy($user))->toBeTrue();
    expect($user->isFollowing($post))->toBeTrue();
});

it('rates with an updatable average', function () {
    $alice = makeUser('Alice');
    $bob = makeUser('Bob');
    $post = makePost();

    $alice->rate($post, 4);
    $bob->rate($post, 2);
    expect($post->averageRating())->toBe(3.0);
    expect($post->ratingsCount())->toBe(2);
    expect($alice->ratingForMe($post))->toBe(4);

    $alice->rate($post, 5); // update, not duplicate
    expect($post->ratingsCount())->toBe(2);
    expect($post->averageRating())->toBe(3.5);

    expect($alice->unrate($post))->toBeTrue();
    expect($post->ratingsCount())->toBe(1);
});

it('maintains the denormalized counter table', function () {
    $user = makeUser();
    $post = makePost();

    $user->like($post);

    $count = Counter::query()
        ->where('subject_id', $post->id)
        ->where('type', 'like')
        ->value('count');

    expect((int) $count)->toBe(1);
});
