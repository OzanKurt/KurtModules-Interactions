<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Filament\V4\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Kurt\Modules\Interactions\Emoji\Models\CustomEmoji;
use Kurt\Modules\Interactions\Filament\V4\Resources\CustomEmojiResource\Pages;

class CustomEmojiResource extends Resource
{
    protected static ?string $model = CustomEmoji::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFaceSmile;

    protected static string|\UnitEnum|null $navigationGroup = 'Interactions';

    protected static ?string $recordTitleAttribute = 'shortcode';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('shortcode')
                ->required()
                ->maxLength(50)
                ->unique(ignoreRecord: true)
                ->helperText('Used in reactions/comments as :shortcode:'),
            TextInput::make('name')->maxLength(255),
            TextInput::make('url')->url()->maxLength(2048),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shortcode')->searchable()->sortable(),
                TextColumn::make('name')->toggleable(),
                TextColumn::make('url')->limit(40)->toggleable(),
                IconColumn::make('is_active')->boolean()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListCustomEmojis::route('/'),
            'create' => Pages\CreateCustomEmoji::route('/create'),
            'edit' => Pages\EditCustomEmoji::route('/{record}/edit'),
        ];
    }
}
