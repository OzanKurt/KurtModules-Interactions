<?php

declare(strict_types=1);

use Kurt\Modules\Interactions\Emoji\Models\CustomEmoji;
use Kurt\Modules\Interactions\Engagement\Exceptions\InvalidReaction;
use Kurt\Modules\Interactions\Tests\Stubs\Post;
use Kurt\Modules\Interactions\Tests\Stubs\User;

function reactor(string $name = 'Alice'): User
{
    return User::create([
        'name' => $name,
        'email' => strtolower($name).'@react.test',
        'username' => strtolower($name).'react',
    ]);
}

it('reacts with unicode emoji and summarizes per-emoji counts', function () {
    $alice = reactor('Alice');
    $bob = reactor('Bob');
    $post = Post::create(['title' => 'Hi']);

    $alice->reactWith($post, '🎉');
    $bob->reactWith($post, '🎉');
    $alice->reactWith($post, '❤️');

    expect($post->reactionSummary())->toEqual(['🎉' => 2, '❤️' => 1]);
    expect($post->isReactedWithBy($alice, '🎉'))->toBeTrue();
    expect($alice->hasReactedWith($post, '❤️'))->toBeTrue();
});

it('lets one user hold multiple distinct emoji but dedupes the same one', function () {
    $alice = reactor();
    $post = Post::create(['title' => 'Hi']);

    $alice->reactWith($post, '👍');
    $alice->reactWith($post, '👍'); // idempotent
    $alice->reactWith($post, '🔥');

    expect($post->reactionCount('👍'))->toBe(1);
    expect($post->reactions()->where('user_id', $alice->id)->count())->toBe(2);
});

it('toggles a reaction on and off', function () {
    $alice = reactor();
    $post = Post::create(['title' => 'Hi']);

    expect($alice->toggleReaction($post, '😄'))->toBeTrue();
    expect($post->reactionCount('😄'))->toBe(1);
    expect($alice->toggleReaction($post, '😄'))->toBeFalse();
    expect($post->reactionCount('😄'))->toBe(0);
});

it('accepts a registered custom emoji and rejects an unknown one', function () {
    CustomEmoji::factory()->create(['shortcode' => 'party']);
    $alice = reactor();
    $post = Post::create(['title' => 'Hi']);

    $alice->reactWith($post, ':party:');
    expect($post->reactionCount(':party:'))->toBe(1);

    $alice->reactWith($post, ':ghost:');
})->throws(InvalidReaction::class);

it('links the custom emoji registry row on the reaction', function () {
    $emoji = CustomEmoji::factory()->create(['shortcode' => 'blob']);
    $alice = reactor();
    $post = Post::create(['title' => 'Hi']);

    $alice->reactWith($post, ':blob:');

    expect($post->reactions()->first()?->custom_emoji_id)->toBe($emoji->id);
});
