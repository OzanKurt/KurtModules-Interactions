<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Filament\V3\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Kurt\Modules\Interactions\Emoji\Models\CustomEmoji;
use Kurt\Modules\Interactions\Filament\V3\Resources\CustomEmojiResource\Pages;

class CustomEmojiResource extends Resource
{
    protected static ?string $model = CustomEmoji::class;

    protected static ?string $navigationIcon = 'heroicon-o-face-smile';

    protected static ?string $navigationGroup = 'Interactions';

    protected static ?string $recordTitleAttribute = 'shortcode';

    public static function form(Form $form): Form
    {
        return $form->schema([
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
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
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
