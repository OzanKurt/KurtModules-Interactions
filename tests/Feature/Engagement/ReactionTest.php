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

it('enforces reactions.max_per_user across distinct emoji', function () {
    config()->set('interactions.reactions.max_per_user', 2);

    $alice = reactor();
    $post = Post::create(['title' => 'Hi']);

    $alice->reactWith($post, '👍');
    $alice->reactWith($post, '🔥');

    // Re-reacting with an emoji already held stays under the cap.
    $alice->reactWith($post, '👍');
    expect($post->reactionSummary())->toEqual(['👍' => 1, '🔥' => 1]);

    // A third distinct emoji breaches the cap.
    expect(fn () => $alice->reactWith($post, '🎉'))->toThrow(InvalidReaction::class);
    expect($post->reactionCount('🎉'))->toBe(0);
});

it('lets a second user react past another user\'s cap', function () {
    config()->set('interactions.reactions.max_per_user', 1);

    $alice = reactor('Alice');
    $bob = reactor('Bob');
    $post = Post::create(['title' => 'Hi']);

    $alice->reactWith($post, '👍');
    $bob->reactWith($post, '🔥'); // Bob has his own budget

    expect($post->reactionSummary())->toEqual(['👍' => 1, '🔥' => 1]);
});

it('summarizes an empty target and reflects unreacting', function () {
    $alice = reactor('Alice');
    $bob = reactor('Bob');
    $post = Post::create(['title' => 'Hi']);

    expect($post->reactionSummary())->toEqual([]);

    $alice->reactWith($post, '🎉');
    $bob->reactWith($post, '🎉');
    expect($post->reactionSummary())->toEqual(['🎉' => 2]);

    $alice->unreact($post, '🎉');
    expect($post->reactionSummary())->toEqual(['🎉' => 1]);

    $bob->unreact($post, '🎉');
    expect($post->reactionSummary())->toEqual([]);
});

it('reflects toggle on then off in the per-emoji count', function () {
    $alice = reactor();
    $post = Post::create(['title' => 'Hi']);

    expect($alice->toggleReaction($post, '😄'))->toBeTrue();
    expect($post->reactionCount('😄'))->toBe(1);
    expect($post->reactionSummary())->toEqual(['😄' => 1]);

    expect($alice->toggleReaction($post, '😄'))->toBeFalse();
    expect($post->reactionCount('😄'))->toBe(0);
    expect($post->reactionSummary())->toEqual([]);
});
