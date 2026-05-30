<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\Interactions\Filament\InteractionsPlugin;
use Kurt\Modules\Interactions\Filament\V5\Resources\CommentResource;
use Kurt\Modules\Interactions\Filament\V5\Resources\CustomEmojiResource;
use Kurt\Modules\Interactions\Filament\V5\Resources\FriendshipResource;

beforeEach(function () {
    if (FilamentVersion::major() !== 5) {
        $this->markTestSkipped('Filament v5 is not installed.');
    }
});

it('dispatches the facade to the v5 plugin', function () {
    expect(InteractionsPlugin::make())->toBeInstanceOf(Kurt\Modules\Interactions\Filament\V5\InteractionsPlugin::class)
        ->and(InteractionsPlugin::make()->getId())->toBe('kurtmodules-interactions');
});

it('registers the three resources on the panel', function () {
    $resources = Filament::getPanel('admin')->getResources();

    expect($resources)
        ->toContain(CommentResource::class)
        ->toContain(CustomEmojiResource::class)
        ->toContain(FriendshipResource::class);
});

it('registers a list route for each resource', function () {
    $uris = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($route) => $route->uri())
        ->all();

    expect($uris)
        ->toContain('admin/'.CommentResource::getSlug())
        ->toContain('admin/'.CustomEmojiResource::getSlug())
        ->toContain('admin/'.FriendshipResource::getSlug());
});
