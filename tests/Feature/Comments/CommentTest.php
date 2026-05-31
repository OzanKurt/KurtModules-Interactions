<?php

declare(strict_types=1);

use Kurt\Modules\Interactions\Comments\CommentManager;
use Kurt\Modules\Interactions\Comments\Enums\CommentStatus;
use Kurt\Modules\Interactions\Comments\Models\Comment;
use Kurt\Modules\Interactions\Tests\Stubs\Post;
use Kurt\Modules\Interactions\Tests\Stubs\User;

function author(string $name = 'Al'): User
{
    return User::create([
        'name' => $name,
        'email' => strtolower($name).'@comment.test',
        'username' => strtolower($name).'c',
    ]);
}

it('creates a comment and renders markdown', function () {
    $user = author();
    $post = Post::create(['title' => 'X']);

    $comment = $user->comment($post, 'Nice **post**');

    expect($post->commentsCount())->toBe(1);
    expect($comment->author->is($user))->toBeTrue();
    expect($comment->renderedBody())->toContain('<strong>post</strong>');
});

it('records the moderator and timestamp when moderating', function () {
    $moderator = author('Mod');
    $post = Post::create(['title' => 'X']);
    $comment = $moderator->comment($post, 'spammy');

    app(CommentManager::class)->moderate($comment, CommentStatus::Spam, $moderator);
    $comment->refresh();

    expect($comment->status)->toBe(CommentStatus::Spam);
    expect($comment->moderated_by)->toBe($moderator->id);
    expect($comment->moderated_at)->not->toBeNull();
    expect($comment->moderatedBy->is($moderator))->toBeTrue();
});

it('threads replies under a parent', function () {
    $user = author();
    $post = Post::create(['title' => 'X']);

    $root = $user->comment($post, 'root');
    $reply = $user->comment($post, 'reply', $root);

    expect($reply->parent_id)->toBe($root->id);
    expect($root->replies()->count())->toBe(1);
});

it('snapshots a revision and stamps edited_at on edit', function () {
    $user = author();
    $post = Post::create(['title' => 'X']);
    $comment = $user->comment($post, 'first');

    app(CommentManager::class)->edit($comment, 'second', $user);
    $comment->refresh();

    expect($comment->body)->toBe('second');
    expect($comment->edited_at)->not->toBeNull();
    expect($comment->revisions()->count())->toBe(1);
    expect($comment->revisions()->first()?->body)->toBe('first');
});

it('moderates a comment out of the published counts', function () {
    $user = author();
    $post = Post::create(['title' => 'X']);
    $comment = $user->comment($post, 'spammy');

    app(CommentManager::class)->moderate($comment, CommentStatus::Spam);

    expect($post->commentsCount())->toBe(0);
    expect(Comment::query()->visible()->count())->toBe(0);
});

it('soft-deletes a comment', function () {
    $user = author();
    $post = Post::create(['title' => 'X']);
    $comment = $user->comment($post, 'bye');

    app(CommentManager::class)->delete($comment);

    expect(Comment::query()->count())->toBe(0);
    expect(Comment::withTrashed()->count())->toBe(1);
});

it('lets a comment itself be reacted to', function () {
    $user = author();
    $post = Post::create(['title' => 'X']);
    $comment = $user->comment($post, 'react to me');

    $user->reactWith($comment, '👍');

    expect($comment->reactionCount('👍'))->toBe(1);
});
