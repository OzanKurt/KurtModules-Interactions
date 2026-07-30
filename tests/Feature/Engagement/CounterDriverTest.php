<?php

declare(strict_types=1);

use Kurt\Modules\Interactions\Engagement\Models\Counter;
use Kurt\Modules\Interactions\Engagement\Models\Interaction;
use Kurt\Modules\Interactions\Engagement\Models\Rating;
use Kurt\Modules\Interactions\Engagement\Models\Reaction;
use Kurt\Modules\Interactions\Tests\Stubs\Post;
use Kurt\Modules\Interactions\Tests\Stubs\User;

function driverUser(string $name): User
{
    return User::create([
        'name' => $name,
        'email' => strtolower($name).'@driver.test',
        'username' => strtolower($name).'drv',
    ]);
}

function driverPost(): Post
{
    return Post::create(['title' => 'Driver']);
}

function likeCount(Post $post): ?int
{
    return Counter::query()
        ->where('subject_type', $post->getMorphClass())
        ->where('subject_id', $post->getKey())
        ->where('type', 'like')
        ->value('count');
}

it('keeps the counter accurate under the atomic driver across many users', function () {
    config()->set('interactions.engagement.counters.driver', 'atomic');

    $post = driverPost();
    $users = collect(['Ann', 'Ben', 'Cid', 'Dan', 'Eve'])->map(fn ($n) => driverUser($n));

    $users->each(fn (User $u) => $u->like($post));
    expect((int) likeCount($post))->toBe(5);

    // A repeated like from the same user must not double-count.
    $users->first()->like($post);
    expect((int) likeCount($post))->toBe(5);

    // Two users unlike; the tally follows one decrement each.
    $users[0]->unlike($post);
    $users[1]->unlike($post);
    expect((int) likeCount($post))->toBe(3);

    // The denormalized tally still matches a live count.
    expect((int) likeCount($post))->toBe($post->receivedInteractions()->where('type', 'like')->count());
});

it('does not move the counter when an atomic vote only changes value', function () {
    config()->set('interactions.engagement.counters.driver', 'atomic');

    $alice = driverUser('AliceV');
    $post = driverPost();

    $alice->upvote($post);
    $alice->downvote($post); // value flip on the same row

    $count = Counter::query()
        ->where('subject_id', $post->getKey())
        ->where('type', 'vote')
        ->value('count');

    expect((int) $count)->toBe(1);
});

it('reconciles drifted atomic counters from live counts', function () {
    config()->set('interactions.engagement.counters.driver', 'atomic');

    $post = driverPost();
    driverUser('Rex')->like($post);
    driverUser('Ryo')->like($post);

    // Simulate drift: hand-corrupt the stored tally.
    Counter::query()
        ->where('subject_id', $post->getKey())
        ->where('type', 'like')
        ->update(['count' => 99]);

    $this->artisan('interactions:reconcile')->assertSuccessful();

    expect((int) likeCount($post))->toBe(2);
});

it('reconcile zeroes counters whose interactions were removed out of band', function () {
    config()->set('interactions.engagement.counters.driver', 'atomic');

    $post = driverPost();
    driverUser('Zed')->like($post);

    // Delete the interaction rows directly, bypassing the manager (bulk delete).
    Interaction::query()->where('subject_id', $post->getKey())->delete();

    $this->artisan('interactions:reconcile')->assertSuccessful();

    expect((int) likeCount($post))->toBe(0);
});

it('purges interactions, ratings, reactions and counters when the subject is deleted', function () {
    $post = driverPost();
    $alice = driverUser('Al');
    $bob = driverUser('Bo');

    $alice->like($post);
    $bob->favorite($post);
    $alice->rate($post, 4);
    $bob->reactWith($post, '🎉');

    $subjectId = $post->getKey();
    $morph = $post->getMorphClass();

    expect(Interaction::query()->where('subject_id', $subjectId)->count())->toBe(2);
    expect(Counter::query()->where('subject_id', $subjectId)->count())->toBeGreaterThan(0);

    $post->delete();

    expect(Interaction::query()->where('subject_type', $morph)->where('subject_id', $subjectId)->count())->toBe(0);
    expect(Rating::query()->where('subject_type', $morph)->where('subject_id', $subjectId)->count())->toBe(0);
    expect(Reaction::query()->where('reactable_type', $morph)->where('reactable_id', $subjectId)->count())->toBe(0);
    expect(Counter::query()->where('subject_type', $morph)->where('subject_id', $subjectId)->count())->toBe(0);
});
