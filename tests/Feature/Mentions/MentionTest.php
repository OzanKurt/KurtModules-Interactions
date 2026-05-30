<?php

declare(strict_types=1);

use Kurt\Modules\Interactions\Comments\CommentManager;
use Kurt\Modules\Interactions\Tests\Stubs\Post;
use Kurt\Modules\Interactions\Tests\Stubs\User;

function mentionUser(string $username): User
{
    return User::create([
        'name' => ucfirst($username),
        'email' => $username.'@mention.test',
        'username' => $username,
    ]);
}

it('records mentions parsed from a comment body', function () {
    $al = mentionUser('alm');
    $bob = mentionUser('bobm');
    $post = Post::create(['title' => 'X']);

    $comment = $al->comment($post, 'hey @bobm check this out');

    expect($comment->mentions()->count())->toBe(1);
    expect($comment->mentionedUserIds())->toBe([$bob->id]);
});

it('ignores unknown handles', function () {
    $al = mentionUser('alm2');
    $post = Post::create(['title' => 'X']);

    $comment = $al->comment($post, 'hi @nobodyhere');

    expect($comment->mentions()->count())->toBe(0);
});

it('reflects the current body when a comment is edited', function () {
    $al = mentionUser('alm3');
    $bob = mentionUser('bobm3');
    $carol = mentionUser('carolm3');
    $post = Post::create(['title' => 'X']);

    $comment = $al->comment($post, 'ping @bobm3');
    expect($comment->mentionedUserIds())->toBe([$bob->id]);

    app(CommentManager::class)->edit($comment, 'now pinging @carolm3', $al);
    $comment->refresh();

    expect($comment->mentionedUserIds())->toBe([$carol->id]);
});
