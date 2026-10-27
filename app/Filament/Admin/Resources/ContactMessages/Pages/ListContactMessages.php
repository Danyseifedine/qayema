<?php

namespace App\Filament\Admin\Resources\ContactMessages\Pages;

use App\Filament\Admin\Actions\HandleMessageAction;
use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListContactMessages extends ListRecords
{
    protected static string $resource = ContactMessageResource::class;

    /**
     * @param  Builder<ContactMessage>  $query
     * @return Builder<ContactMessage>
     */
    private static function open(Builder $query): Builder
    {
        return $query->open();
    }

    /** What is still to answer first; the rest a tab away. */
    public function getTabs(): array
    {
        return [
            'open' => Tab::make('Open')
                ->icon(Heroicon::OutlinedInbox)
                ->badge(fn (): ?int => ContactMessageResource::openCount() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => self::open($query)),
            'requests' => Tab::make('Package requests')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('package_id')),
            'handled' => Tab::make('Handled')
                ->icon(Heroicon::OutlinedCheck)
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('handled_at')),
            'all' => Tab::make('All'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('package.name')
                    ->label('Requested package')
                    ->placeholder('-')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('message')
                    ->limit(60)
                    ->tooltip(fn (ContactMessage $record): string => $record->message)
                    ->searchable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable()
                    ->since(),

                TextColumn::make('handled_at')
                    ->label('Handled')
                    ->since()
                    ->placeholder('Open')
                    ->badge()
                    ->color(fn (mixed $state): string => blank($state) ? 'warning' : 'success')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('package_id')
                    ->label('Requested package')
                    ->relationship('package', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                HandleMessageAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    HandleMessageAction::bulk(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nothing here')
            ->emptyStateDescription('Messages from the contact form and package requests from owners land here.')
            ->defaultSort('created_at', 'desc');
    }
}
