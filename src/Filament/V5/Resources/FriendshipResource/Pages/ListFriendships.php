<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Filament\V5\Resources\FriendshipResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Interactions\Filament\V5\Resources\FriendshipResource;

class ListFriendships extends ListRecords
{
    protected static string $resource = FriendshipResource::class;
}
