<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Interactions\Filament\V5\Resources\CommentResource;
use Kurt\Modules\Interactions\Filament\V5\Resources\CustomEmojiResource;
use Kurt\Modules\Interactions\Filament\V5\Resources\FriendshipResource;

beforeEach(function () {
    if (FilamentVersion::major() !== 5) {
        $this->markTestSkipped('Filament v5 is not installed.');
    }
});

/**
 * @return array<string, array{0: class-string, 1: string, 2: bool, 3: array<int, string>}>
 */
dataset('interactions-resources', [
    'Comment' => [CommentResource::class, 'ListComments', true, ['body', 'status']],
    'CustomEmoji' => [CustomEmojiResource::class, 'ListCustomEmojis', true, ['shortcode', 'is_active']],
    'Friendship' => [FriendshipResource::class, 'ListFriendships', false, ['sender_id', 'status']],
]);

it('registers an index page and exposes key table columns', function (string $resource, string $listClass, bool $hasForm, array $columns) {
    $pageClass = $resource.'\\Pages\\'.$listClass;

    expect(array_keys($resource::getPages()))->toContain('index');
    expect(tableColumnNames($resource, $pageClass))->toContain(...$columns);
})->with('interactions-resources');

it('builds a non-empty form for editable resources', function (string $resource, string $listClass, bool $hasForm, array $columns) {
    if (! $hasForm) {
        expect(true)->toBeTrue();

        return;
    }

    $pageClass = $resource.'\\Pages\\'.$listClass;
    expect(formFieldNames($resource, $pageClass))->not->toBeEmpty();
})->with('interactions-resources');

it('filters comments and friendships by status', function () {
    expect(tableFilterNames(CommentResource::class, CommentResource::class.'\\Pages\\ListComments'))->toContain('status');
    expect(tableFilterNames(FriendshipResource::class, FriendshipResource::class.'\\Pages\\ListFriendships'))->toContain('status');
});
