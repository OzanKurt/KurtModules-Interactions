<?php

declare(strict_types=1);

use Kurt\Modules\Interactions\Engagement\Models\Reaction;
use Kurt\Modules\Interactions\Engagement\Models\ReactionCounter;
use Kurt\Modules\Interactions\Engagement\ReactionManager;
use Kurt\Modules\Interactions\Tests\Stubs\Post;
use Kurt\Modules\Interactions\Tests\Stubs\User;

function rcUser(string $name): User
{
    return User::create([
        'name' => $name,
        'email' => strtolower($name).'@rcount.test',
        'username' => strtolower($name).'rc',
    ]);
}

function rcPost(): Post
{
    return Post::create(['title' => 'Counted']);
}

function cachedReactionCount(Post $post, string $emoji): ?int
{
    return ReactionCounter::query()
        ->where('reactable_type', $post->getMorphClass())
        ->where('reactable_id', $post->getKey())
        ->where('emoji', $emoji)
        ->value('count');
}

function liveReactionSummary(Post $post): array
{
    return $post->reactions()
        ->groupBy('emoji')
        ->selectRaw('emoji, COUNT(*) as aggregate')
        ->pluck('aggregate', 'emoji')
        ->map(fn ($count) => (int) $count)
        ->all();
}

it('maintains the reaction-count cache on react and unreact', function () {
    $alice = rcUser('Alice');
    $bob = rcUser('Bob');
    $post = rcPost();

    $alice->reactWith($post, '🎉');
    $bob->reactWith($post, '🎉');

    expect(cachedReactionCount($post, '🎉'))->toBe(2);

    $alice->unreact($post, '🎉');
    expect(cachedReactionCount($post, '🎉'))->toBe(1);

    $bob->unreact($post, '🎉');
    expect(cachedReactionCount($post, '🎉'))->toBe(0);
});

it('does not double-count an idempotent re-react', function () {
    $alice = rcUser('Alice');
    $post = rcPost();

    $alice->reactWith($post, '👍');
    $alice->reactWith($post, '👍'); // idempotent

    expect(cachedReactionCount($post, '👍'))->toBe(1);
});

it('serves summary from the cache and it matches a live count', function () {
    $alice = rcUser('Alice');
    $bob = rcUser('Bob');
    $cid = rcUser('Cid');
    $post = rcPost();

    $alice->reactWith($post, '🎉');
    $bob->reactWith($post, '🎉');
    $alice->reactWith($post, '❤️');
    $cid->reactWith($post, '🔥');

    $summary = $post->reactionSummary();

    expect($summary)->toEqual(['🎉' => 2, '❤️' => 1, '🔥' => 1]);
    expect($summary)->toEqual(liveReactionSummary($post));
});

it('omits emoji whose cached count fell to zero from the summary', function () {
    $alice = rcUser('Alice');
    $post = rcPost();

    $alice->reactWith($post, '😄');
    $alice->unreact($post, '😄');

    // The cache row survives at zero, but summary must not surface it.
    expect(cachedReactionCount($post, '😄'))->toBe(0);
    expect($post->reactionSummary())->toEqual([]);
});

it('maintains the cache under the atomic driver across many users and emoji', function () {
    config()->set('interactions.engagement.counters.driver', 'atomic');

    $post = rcPost();
    $users = collect(['Ann', 'Ben', 'Cid', 'Dan', 'Eve'])->map(fn ($n) => rcUser($n));

    $users->each(fn (User $u) => $u->reactWith($post, '🎉'));
    $users->take(3)->each(fn (User $u) => $u->reactWith($post, '🔥'));

    expect($post->reactionSummary())->toEqual(['🎉' => 5, '🔥' => 3]);

    $users[0]->unreact($post, '🎉');
    $users[1]->unreact($post, '🎉');

    expect($post->reactionSummary())->toEqual(['🎉' => 3, '🔥' => 3]);
    expect($post->reactionSummary())->toEqual(liveReactionSummary($post));
});

it('reconciles reaction counters drifted out of band', function () {
    config()->set('interactions.engagement.counters.driver', 'atomic');

    $post = rcPost();
    rcUser('Rex')->reactWith($post, '🎉');
    rcUser('Ryo')->reactWith($post, '🎉');

    // Simulate drift: hand-corrupt the cached tally.
    ReactionCounter::query()
        ->where('reactable_id', $post->getKey())
        ->where('emoji', '🎉')
        ->update(['count' => 99]);

    $this->artisan('interactions:reconcile')->assertSuccessful();

    expect(cachedReactionCount($post, '🎉'))->toBe(2);
    expect($post->reactionSummary())->toEqual(['🎉' => 2]);
});

it('reconcile rebuilds the cache after reactions inserted and removed out of band', function () {
    $post = rcPost();
    $alice = rcUser('Al');
    $bob = rcUser('Bo');

    $alice->reactWith($post, '🎉');

    // Insert a reaction directly, bypassing the manager (no cache update).
    Reaction::query()->create([
        'user_id' => $bob->getKey(),
        'reactable_type' => $post->getMorphClass(),
        'reactable_id' => $post->getKey(),
        'emoji' => '🎉',
    ]);

    // Delete the managed reaction directly, leaving the cache stale-high.
    Reaction::query()
        ->where('user_id', $alice->getKey())
        ->where('reactable_id', $post->getKey())
        ->delete();

    $this->artisan('interactions:reconcile')->assertSuccessful();

    expect($post->reactionSummary())->toEqual(['🎉' => 1]);
    expect($post->reactionSummary())->toEqual(liveReactionSummary($post));
});

it('falls back to a live aggregate when the counter table is disabled', function () {
    config()->set('interactions.counters.driver', 'none');

    $alice = rcUser('Alice');
    $bob = rcUser('Bob');
    $post = rcPost();

    $alice->reactWith($post, '🎉');
    $bob->reactWith($post, '🎉');
    $alice->reactWith($post, '❤️');

    // No cache is maintained under the 'none' driver.
    expect(ReactionCounter::query()->count())->toBe(0);

    // summary() still returns correct per-emoji counts from a live query.
    expect($post->reactionSummary())->toEqual(['🎉' => 2, '❤️' => 1]);
    expect(app(ReactionManager::class)->summary($post))->toEqual(liveReactionSummary($post));
});
