<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Filament\V3;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Kurt\Modules\Interactions\Filament\V3\Resources\CommentResource;
use Kurt\Modules\Interactions\Filament\V3\Resources\CustomEmojiResource;
use Kurt\Modules\Interactions\Filament\V3\Resources\FriendshipResource;

final class InteractionsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'kurtmodules-interactions';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            CommentResource::class,
            CustomEmojiResource::class,
            FriendshipResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        /** @var static */
        return app(self::class);
    }
}
