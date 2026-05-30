<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Filament\V3\Resources\CustomEmojiResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kurt\Modules\Interactions\Filament\V3\Resources\CustomEmojiResource;

class EditCustomEmoji extends EditRecord
{
    protected static string $resource = CustomEmojiResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
