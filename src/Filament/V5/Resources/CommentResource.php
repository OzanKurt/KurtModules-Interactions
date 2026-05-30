<?php

declare(strict_types=1);

namespace Kurt\Modules\Interactions\Filament\V5\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Kurt\Modules\Interactions\Comments\Enums\CommentStatus;
use Kurt\Modules\Interactions\Comments\Models\Comment;
use Kurt\Modules\Interactions\Filament\V5\Resources\CommentResource\Pages;

class CommentResource extends Resource
{
    /** @var array<string, string> */
    public const STATUS_OPTIONS = [
        'published' => 'Published',
        'pending' => 'Pending',
        'spam' => 'Spam',
    ];

    protected static ?string $model = Comment::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|\UnitEnum|null $navigationGroup = 'Interactions';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')->required()->rows(4)->columnSpanFull(),
            Select::make('status')->options(self::STATUS_OPTIONS)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')->label('Author')->sortable(),
                TextColumn::make('commentable_type')->label('On')->toggleable(),
                TextColumn::make('body')->limit(50)->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (CommentStatus $state): string => ucfirst($state->value))
                    ->color(fn (CommentStatus $state): string => match ($state) {
                        CommentStatus::Published => 'success',
                        CommentStatus::Pending => 'warning',
                        CommentStatus::Spam => 'danger',
                    }),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::STATUS_OPTIONS),
            ])
            ->actions([
                Action::make('approve')
                    ->color('success')
                    ->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (Comment $record): bool => $record->status !== CommentStatus::Published)
                    ->action(fn (Comment $record) => $record->update(['status' => CommentStatus::Published->value])),
                Action::make('markSpam')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->visible(fn (Comment $record): bool => $record->status !== CommentStatus::Spam)
                    ->action(fn (Comment $record) => $record->update(['status' => CommentStatus::Spam->value])),
                EditAction::make(),
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
            'index' => Pages\ListComments::route('/'),
            'edit' => Pages\EditComment::route('/{record}/edit'),
        ];
    }
}
