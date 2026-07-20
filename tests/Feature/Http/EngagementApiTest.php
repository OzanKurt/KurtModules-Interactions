<?php

declare(strict_types=1);

use Kurt\Modules\Interactions\Tests\Stubs\Post;
use Kurt\Modules\Interactions\Tests\Stubs\User;

beforeEach(fn () => enableInteractionsApi());
afterEach(fn () => resetInteractionsMorphMap());

function engUser(string $name = 'Alice'): User
{
    return User::create([
        'name' => $name,
        'email' => strtolower($name).'@example.test',
        'username' => strtolower($name),
    ]);
}

function engPost(): Post
{
    return Post::create(['title' => 'Hello']);
}

it('toggles a like on and off, updating the counts', function () {
    $user = engUser();
    $post = engPost();

    $this->actingAs($user)
        ->postJson("/api/interactions/post/{$post->id}/engagement/like")
        ->assertOk()
        ->assertJsonPath('data.kind', 'like')
        ->assertJsonPath('data.active', true)
        ->assertJsonPath('data.counts.likes', 1);

    // Toggling again removes it.
    $this->actingAs($user)
        ->postJson("/api/interactions/post/{$post->id}/engagement/like")
        ->assertOk()
        ->assertJsonPath('data.active', false)
        ->assertJsonPath('data.counts.likes', 0);
});

it('keeps like and dislike mutually exclusive', function () {
    $user = engUser();
    $post = engPost();

    $this->actingAs($user)->postJson("/api/interactions/post/{$post->id}/engagement/dislike")
        ->assertJsonPath('data.counts.dislikes', 1);

    $this->actingAs($user)->postJson("/api/interactions/post/{$post->id}/engagement/like")
        ->assertJsonPath('data.counts.likes', 1)
        ->assertJsonPath('data.counts.dislikes', 0);
});

it('toggles a vote with an explicit value into the score', function () {
    $user = engUser();
    $post = engPost();

    $this->actingAs($user)
        ->postJson("/api/interactions/post/{$post->id}/engagement/vote", ['value' => -1])
        ->assertOk()
        ->assertJsonPath('data.counts.votes.count', 1)
        ->assertJsonPath('data.counts.votes.score', -1);
});

it('reports the acting user state and counts', function () {
    $alice = engUser('Alice');
    $bob = engUser('Bob');
    $post = engPost();

    $this->actingAs($alice)->postJson("/api/interactions/post/{$post->id}/engagement/favorite");
    $this->actingAs($bob)->postJson("/api/interactions/post/{$post->id}/engagement/favorite");

    $this->actingAs($alice)
        ->getJson("/api/interactions/post/{$post->id}/engagement")
        ->assertOk()
        ->assertJsonPath('data.state.favorite', true)
        ->assertJsonPath('data.state.follow', false)
        ->assertJsonPath('data.counts.favorites', 2);
});

it('exposes the public counts endpoint to a guest', function () {
    $user = engUser();
    $post = engPost();
    $this->actingAs($user)->postJson("/api/interactions/post/{$post->id}/engagement/follow");

    $this->getJson("/api/interactions/post/{$post->id}/counts")
        ->assertOk()
        ->assertJsonPath('data.followers', 1)
        ->assertJsonPath('data.reactions', []);
});

it('blocks a guest from toggling engagement with 401', function () {
    $post = engPost();

    $this->postJson("/api/interactions/post/{$post->id}/engagement/like")
        ->assertUnauthorized();

    // The per-user state read is authenticated too.
    $this->getJson("/api/interactions/post/{$post->id}/engagement")
        ->assertUnauthorized();
});

it('rejects an unsupported engagement kind with 422', function () {
    $post = engPost();

    $this->actingAs(engUser())
        ->postJson("/api/interactions/post/{$post->id}/engagement/teleport")
        ->assertStatus(422);
});

it('returns 404 when engaging an unmapped morph type', function () {
    $this->actingAs(engUser())
        ->postJson('/api/interactions/ghost/1/engagement/like')
        ->assertNotFound();
});
