<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Filament\V5\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Kurt\Modules\Interactions\Filament\V5\Resources\FriendshipResource\Pages;
use Kurt\Modules\Interactions\Graph\Enums\FriendshipStatus;
use Kurt\Modules\Interactions\Graph\Models\Friendship;

class FriendshipResource extends Resource
{
    /** @var array<string, string> */
    public const STATUS_OPTIONS = [
        'pending' => 'Pending',
        'accepted' => 'Accepted',
        'denied' => 'Denied',
        'blocked' => 'Blocked',
    ];

    protected static ?string $model = Friendship::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Interactions';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sender_id')->label('From')->sortable(),
                TextColumn::make('recipient_id')->label('To')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (FriendshipStatus $state): string => ucfirst($state->value))
                    ->color(fn (FriendshipStatus $state): string => match ($state) {
                        FriendshipStatus::Accepted => 'success',
                        FriendshipStatus::Pending => 'warning',
                        FriendshipStatus::Denied => 'gray',
                        FriendshipStatus::Blocked => 'danger',
                    }),
                TextColumn::make('accepted_at')->dateTime()->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::STATUS_OPTIONS),
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * @return array<class-string, mixed>
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFriendships::route('/'),
        ];
    }
}
