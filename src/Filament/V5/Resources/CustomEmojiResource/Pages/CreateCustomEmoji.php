<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Filament\V5\Resources\CustomEmojiResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kurt\Modules\Interactions\Filament\V5\Resources\CustomEmojiResource;

class CreateCustomEmoji extends CreateRecord
{
    protected static string $resource = CustomEmojiResource::class;
}
