<?php

declare(strict_types=1);

use Kurt\Modules\Interactions\Tests\Stubs\Post;
use Kurt\Modules\Interactions\Tests\Stubs\User;

beforeEach(fn () => enableInteractionsApi());
afterEach(fn () => resetInteractionsMorphMap());

function apiUser(string $name = 'Alice'): User
{
    return User::create([
        'name' => $name,
        'email' => strtolower($name).'@example.test',
        'username' => strtolower($name),
    ]);
}

function apiPost(): Post
{
    return Post::create(['title' => 'Hello']);
}

it('reacts and the summary reflects it', function () {
    $post = apiPost();

    $response = $this->actingAs(apiUser())
        ->postJson("/api/interactions/post/{$post->id}/reactions", ['emoji' => '🎉']);

    $response->assertCreated()
        ->assertJsonPath('data.summary.🎉', 1);

    // The public summary endpoint reads the same denormalized tally.
    $this->getJson("/api/interactions/post/{$post->id}/reactions/summary")
        ->assertOk()
        ->assertJsonPath('data.summary.🎉', 1);
});

it('unreacts and drops the emoji from the summary', function () {
    $user = apiUser();
    $post = apiPost();

    $this->actingAs($user)->postJson("/api/interactions/post/{$post->id}/reactions", ['emoji' => '🎉']);

    $this->actingAs($user)
        ->deleteJson("/api/interactions/post/{$post->id}/reactions", ['emoji' => '🎉'])
        ->assertOk()
        ->assertJsonPath('data.removed', true)
        ->assertJsonPath('data.summary', []);
});

it('rejects an invalid reaction with 422', function () {
    $post = apiPost();

    $this->actingAs(apiUser())
        ->postJson("/api/interactions/post/{$post->id}/reactions", ['emoji' => ''])
        ->assertStatus(422);
});

it('blocks a guest from reacting with 401', function () {
    $post = apiPost();

    $this->postJson("/api/interactions/post/{$post->id}/reactions", ['emoji' => '🎉'])
        ->assertUnauthorized();
});

it('serves the reaction summary publicly to a guest', function () {
    $user = apiUser();
    $post = apiPost();
    $this->actingAs($user)->postJson("/api/interactions/post/{$post->id}/reactions", ['emoji' => '👍']);

    $this->getJson("/api/interactions/post/{$post->id}/reactions/summary")
        ->assertOk()
        ->assertJsonPath('data.summary.👍', 1);
});

it('returns 404 for an unmapped morph type', function () {
    $this->actingAs(apiUser())
        ->postJson('/api/interactions/ghost/1/reactions', ['emoji' => '🎉'])
        ->assertNotFound();
});

it('returns 404 for a missing subject id', function () {
    $this->getJson('/api/interactions/post/999999/reactions/summary')
        ->assertNotFound();
});
