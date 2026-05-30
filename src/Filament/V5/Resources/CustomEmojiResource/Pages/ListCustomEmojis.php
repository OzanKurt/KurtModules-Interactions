<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Filament\V5\Resources\CustomEmojiResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Interactions\Filament\V5\Resources\CustomEmojiResource;

class ListCustomEmojis extends ListRecords
{
    protected static string $resource = CustomEmojiResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
