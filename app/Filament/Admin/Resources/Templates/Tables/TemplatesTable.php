<?php

namespace App\Filament\Admin\Resources\Templates\Tables;

use App\Models\Template;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('thumbnail')
                    ->collection('thumbnail')
                    ->label('Preview')
                    ->toggleable(),
                TextColumn::make('name')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->placeholder('N/A')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('restaurants_count')
                    ->label('Restaurants')
                    ->counts('restaurants')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                ToggleColumn::make('is_premium')
                    ->label('Premium'),
                TextColumn::make('created_at')
                    ->placeholder('N/A')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(fn (Template $record): string => self::deleteWarning($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /** What deleting a design does to the menus that use it. */
    public static function deleteWarning(Template $template): string
    {
        $using = $template->restaurants()->count();

        return $using === 0
            ? 'No restaurant uses this design. It and its preview image are deleted for good.'
            : "{$using} ".str('restaurant')->plural($using).' use this design. Their menus go offline until they choose another design. The design and its preview image are deleted for good.';
    }
}
