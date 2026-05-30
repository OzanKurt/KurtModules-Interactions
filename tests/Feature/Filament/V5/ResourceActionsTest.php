<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Interactions\Filament\V5\Resources\CommentResource;
use Kurt\Modules\Interactions\Filament\V5\Resources\CommentResource\Pages\ListComments;
use Kurt\Modules\Interactions\Filament\V5\Resources\FriendshipResource;

beforeEach(function () {
    if (FilamentVersion::major() !== 5) {
        $this->markTestSkipped('Filament v5 is not installed.');
    }
});

it('exposes approve + mark-spam moderation actions on comments', function () {
    expect(tableActionNames(CommentResource::class, ListComments::class))
        ->toContain('approve', 'markSpam');
});

it('disables creating comments and friendships through the panel', function () {
    expect(CommentResource::canCreate())->toBeFalse();
    expect(FriendshipResource::canCreate())->toBeFalse();
});
